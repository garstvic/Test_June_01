<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Service\RbcNewsParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsController extends AbstractController
{
    /**
     * @Route("/",name="app_homepage")
     */
    public function index(NewsRepository $news_repository)
    {
        return $this->render('news/index.html.twig', [
            'controller_name' => 'NewsController',
            'news_list'=>$news_repository->getNewsList(),
        ]);
    }
    
    /**
     * @Route("/news/{slug}",name="news_show")
     */
    public function show($slug,NewsRepository $news_repository)
    {
        return $this->render('news/show.html.twig', [
            'controller_name' => 'NewsController',
            'news'=>$news_repository->findOneBy(array('id'=>$slug)),
        ]);        
    }
    
    /**
     * @Route("/ajax/get-news",name="get_news",methods={"POST"})
     */
    public function getNews(RbcNewsParser $parser,NewsRepository $news_repository)
    {
        $news_list=$parser->parse();
        
        return $this->render('news/list.html.twig',[
            'controller_name'=>'NewsController',
            'news_list'=>$news_repository->getNewsList(),
        ]);
    }
}
