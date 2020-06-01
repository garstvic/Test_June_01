<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsController extends AbstractController
{
    /**
     * @Route("/",name="app_homepage")
     */
    public function index()
    {
        return $this->render('news/index.html.twig', [
            'controller_name' => 'NewsController',
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
}
