<?php

namespace App\Service;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;


class RbcNewsParser
{
    private $client;
    private $crawler;
    private $url='https://www.rbc.ru';
    private $entity_manager;
    private $news_repository;
    
    public function __construct(EntityManagerInterface $entity_manager,NewsRepository $news_repository)
    {
        $this->client=new Client;
        $this->crawler=new Crawler(null,$this->url);
        $this->entity_manager=$entity_manager;
        $this->news_repository=$news_repository;
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
                    $data['article']=$news_text;
                    
                    $img_crawler=$news_crawler->filter('.article__main-image__wrap > img');

                    if($img_crawler->count()){
                        $data['img']=$img_crawler->attr('src');
                    }

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
                    
                    if($short_description->count()){
                        $data['short_description']=trim($short_description->text());
                    }

                    $news_text='';

                    $news_crawler->filter('.article__text > p')
                        ->each(function(Crawler $body_crawler) use (&$news_text){
                            $text=trim($body_crawler->text());
                            
                            if(strlen($text)){
                                $news_text.="<p>{$text}</p>";
                            }
                        });

                    $data['article']=$news_text;
                    
                    $img_crawler=$news_crawler->filter('.article__main-image__link > img');

                    if($img_crawler->count()){
                        $data['img']=$img_crawler->attr('src');
                    } else {
                        $img_crawler=$news_crawler->filter('.article__main-image__wrap > img');

                        if($img_crawler->count()){
                            $data['img']=$img_crawler->attr('src');
                        }
                    }
                }
                
                $this->saveNews($data);
                
                return $data;
        });
        
        return $news_list;
    }
    
    protected function saveNews($news)
    {
        if(!$this->news_repository->findOneBy(['title'=>$news['title']])){
            $create_news=new News;
            
            $create_news->setTitle($news['title']);
            
            if(isset($news['short_description'])){
                $create_news->setShortDescription($news['short_description']);
            }
            
            if(isset($news['article'])){
                $create_news->setArticle($news['article']);
            }
            
            if(isset($news['img'])){
                $create_news->setImg($news['img']);
            }
            
            $create_news->setHref($news['href']);
            
            $this->entity_manager->persist($create_news);
            
            $this->entity_manager->flush();
        }
    }
}