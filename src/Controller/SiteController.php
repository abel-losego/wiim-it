<?php

namespace App\Controller;

use App\Dolibarr\Dolibarr;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{
    #[Route('/home', name: 'home')]
    #[Route('/', name: 'site')]
    public function home(Dolibarr $dolibarr): Response
    {
        
        //$articles = $repository->findAll();
        
        
        $listProduits = [];
        $produitParam = ["limit" => 3, "sortfield" => "rowid"];
        $listProduitsResult = $dolibarr->CallAPI("GET", "products", $produitParam);
        
        $listProduitsResult = json_decode($listProduitsResult, true);

            if (isset($listProduitsResult["error"]) && $listProduitsResult["error"]["code"] >= "300") {
            } else {
                foreach ($listProduitsResult as $produit) {
                    $listProduits[intval($produit["id"])] = [
                        'id' => html_entity_decode($produit["ref"], ENT_QUOTES), 
                        'price_ttc' => number_format(html_entity_decode($produit["price_ttc"], ENT_QUOTES), 2), 
                        'label' => html_entity_decode($produit["label"], ENT_QUOTES), 
                        'ref_ext' => html_entity_decode($produit["ref_ext"], ENT_QUOTES)
                    ];    
                }
            }
        /* nom de chaque variable à ce lien https://wiki.dolibarr.org/index.php?title=Table_llx_product */
        /*
        return new Response($listProduits[1]['price']);*/
        
        return $this->render('site/index.html.twig', [
            'articles' => $listProduits,
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

    #[Route('/test', name: 'test')]
    public function test(Dolibarr $dolibarr): Response
    {
        
        /*$newClient = [
            "ref" 			=> "tesffdsqsqst",
            "email"			=> "test@gmail.com",
            
            "phone" 		=> '636367637',
            "client" 		=> 1,
		    "code_client"	=> "CU2106-00003",
            "fournisseur"   => 0
    
        ];*/
        $newClient = [
            "name" 			=> "nom société client",
            "email"			=> "email société client",
            "client" 		=> "1",
            "code_client"	=> "-1",
            "phone" 		=> '636367637',
            "ref_ext" 		=> 'chfhfdvfhfvhf'
        ];
        $newClientResult = $dolibarr->CallAPI("POST", "thirdparties", json_encode($newClient));
        $newClientResult = json_decode($newClientResult, true);
        
        return new JsonResponse($newClientResult); ;
        //return $this->render('site/navbar.html.twig');
    }
    
    
}
/* Recuperer les comptes users 
$listProduits = [];
        $produitParam = ["limit" => 1, "sortfield" => "rowid"];
        $listProduitsResult = $dolibarr->CallAPI("GET", "thirdparties", $produitParam);
        
        $listProduitsResult = json_decode($listProduitsResult, true);

            if (isset($listProduitsResult["error"]) && $listProduitsResult["error"]["code"] >= "300") {
            } else {
                foreach ($listProduitsResult as $produit) {
                    $listProduits[intval($produit["id"])] = [
                        'id' => html_entity_decode($produit["ref"], ENT_QUOTES),
                        'name' => html_entity_decode($produit["name"], ENT_QUOTES) 
                    ];    
                }
            } */