<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{
    #[Route('/home', name: 'home')]
    #[Route('/', name: 'site')]
    public function home(ArticleRepository $repository): Response
    {
        
        $articles = $repository->findAll();
        
        return $this->render('site/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/home_manager', name: 'home_manager')]
    public function homeAfterManager(ArticleRepository $repository): Response
    {
        //Met à jour les informations personnelles si elles ont été modifié sur le compte Stripe (ici que mail) 
        
        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');
    
        $customer = \Stripe\Customer::retrieve([
            'id' => $this->getUser()->getStripeId(),
        ]);
        if($this->getUser()->getEmail() != $customer->email){
            $user= $this->getUser();
            $user->setEmail($customer->email);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        
        $articles = $repository->findAll();
        
        return $this->render('site/index.html.twig', [
            'articles' => $articles,
        ]);
    }
}