<?php

namespace App\Controller;

use App\Service\RbcNewsParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsController extends AbstractController
{
    /**
     * @Route("/",name="app_homepage")
     */
    public function index(RbcNewsParser $parser)
    {
        $news_list=$parser->parse();

        return $this->render('news/index.html.twig', [
            'controller_name' => 'NewsController',
            'news_list'=>$news_list,
        ]);
    }
    
    /**
     * @Route("/news/{slug}",name="news_show")
     */
    public function show()
    {
        return $this->render('news/show.html.twig', [
            'controller_name' => 'NewsController',
        ]);        
    }
    
    /**
     * @Route("/ajax/get-news",name="get_news",methods={"POST"})
     */
    public function getNews()
    {
        $news_list=[];
        
        return $this->render('news/list.html.twig',[
            'controller_name'=>'NewsController',
            'news_list'=>$news_list,
        ]);
    }
}
