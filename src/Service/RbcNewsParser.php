<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class RbcNewsParser
{
    private $client;
    private $crawler;
    private $url='https://www.rbc.ru';
    
    public function __construct()
    {
        $this->client=new Client;
        $this->crawler=new Crawler(null,$this->url);
    }
    
    public function parse()
    {
        $main_page=$this->client->request('GET',$this->url);

        $html=$main_page->getBody();

        $this->crawler->addHtmlContent($html,'UTF-8');

        $news_list=$this->crawler->filter('.js-news-feed-list > .news-feed__item')
            ->each(function(Crawler $node_crawler){
                $title=$node_crawler->filter('.news-feed__item__title');
                $href=$node_crawler->attr('href');

                $data=[
                    'title'=>trim($title->text()),
                    'href'=>mb_substr($href,0,strpos($href,'?')),
                ];                

                $news_page=$this->client->request('GET',$href)->getBody();

                $news_crawler=new Crawler(null,$href);
                $news_crawler->addHtmlContent($news_page);

                if (stripos($href,'/rbcfreenews/')!==false || 
                    stripos($href,'/society/')!==false){
                    $date=$news_crawler->filter('.article__header__date');

                    $short_description=$news_crawler->filter('.article__text__overview');

                    $data['date']=trim($date->text());

                    $news_text='';
                    $news_text_without_tags='';

                    $news_crawler->filter('.article__text > p')
                        ->each(function(Crawler $body_crawler) use (&$news_text,&$news_text_without_tags){
                            $text=trim($body_crawler->text());

                            if(strlen($text)){
                                $news_text.="<p>{$text}</p>";
                                $news_text_without_tags.=$text;
                            }
                        });

                    $data['short_description']=mb_substr($news_text_without_tags,0,200,'UTF-8');
                    $data['text']=$news_text;
                    
                    $img_crawler=$news_crawler->filter('.article__main-image__wrap > img');

                    if($img_crawler->count()){
                        $data['img']=$img_crawler->attr('src');
                    }

                    return $data;
                }

                if (stripos($href,'/sport.') || 
                    stripos($href,'/technology_and_media/') ||
                    stripos($href,'/politics/') ||
                    stripos($href,'/business/') ||
                    stripos($href,'/crypto/') ||
                    stripos($href,'/quote.') ||
                    stripos($href,'/pro.') ||
                    stripos($href,'/realty.')){
                    $date=$news_crawler->filter('.article__header__date');

                    $short_description=$news_crawler->filter('.article__text__overview');

                    if($date->count()){
                        $data['date']=trim($date->text());
                    }
                    
                    $data['short_description']=trim($short_description->text());

                    $news_text='';

                    $news_crawler->filter('.article__text > p')
                        ->each(function(Crawler $body_crawler) use (&$news_text){
                            $text=trim($body_crawler->text());
                            
                            if(strlen($text)){
                                $news_text.="<p>{$text}</p>";
                            }
                        });

                    $data['text']=$news_text;
                    
                    $img_crawler=$news_crawler->filter('.article__main-image__link > img');

                    if($img_crawler->count()){
                        $data['img']=$img_crawler->attr('src');
                    } else {
                        $img_crawler=$news_crawler->filter('.article__main-image__wrap > img');

                        if($img_crawler->count()){
                            $data['img']=$img_crawler->attr('src');
                        }
                    }

                    return $data;
                }

                return [
                    'title'=>trim($title->text()),
                    'href'=>mb_substr($href,0,strpos($href,'?')),
                ];
        });
        
        return $news_list;
    }
}