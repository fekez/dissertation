<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OthelloController extends AbstractController
{
    /**
     * @Route("/othello", name="app_othello")
     */
    public function index(): Response
    {
        return $this->render('othello/index.html.twig', [
            'controller_name' => 'OthelloController',
        ]);
    }
}
