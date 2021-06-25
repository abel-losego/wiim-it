<?php

namespace App\Controller;

use App\Dolibarr\Dolibarr;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function contact(Dolibarr $dolibarr): Response
    {
        return $this->render('contact/contact.html.twig');
    }
}
