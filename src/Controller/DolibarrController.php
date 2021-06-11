<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DolibarrController extends AbstractController
{
    #[Route('/dolibarr', name: 'dolibarr')]
    public function index(): Response
    {
        return $this->render('dolibarr/index.html.twig', [
            'controller_name' => 'DolibarrController',
        ]);
    }

    
}
