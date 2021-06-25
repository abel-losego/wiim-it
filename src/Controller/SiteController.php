<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Dolibarr\Dolibarr;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{
    #[Route('/home', name: 'home')]
    #[Route('/', name: 'site')]
    public function home(Dolibarr $dolibarr, ArticleRepository $repo): Response
    {

        
        //Recuperer tous les articles 

        $listProduits = [];

        $produitParam = ["limit" => 4, "sortfield" => "rowid"];
        $listProduitsResult = $dolibarr->CallAPI("GET", "products", $produitParam);
        
        $listProduitsResult = json_decode($listProduitsResult, true);

        if (isset($listProduitsResult["error"]) && $listProduitsResult["error"]["code"] >= "300") {
        } else {
            $sum=0;
            foreach ($listProduitsResult as $produit) {
                $article = $repo->findOneBy(["id_doli" => $produit["id"]]);
                $listProduits[intval($produit["id"])] = [
                    'ref' => html_entity_decode($produit["ref"], ENT_QUOTES), 
                    'price_ttc' => number_format(html_entity_decode($produit["price_ttc"], ENT_QUOTES), 2), 
                    'name' => html_entity_decode($produit["label"], ENT_QUOTES), 
                    'ref_ext' => html_entity_decode($produit["ref_ext"], ENT_QUOTES),
                    'id_bdd' => $article->getId(),
                    'best' => $article->getBest(),
                    'services' => $article->getServices(),
  
                    
                ];    
            }
        }

        /* nom de chaque variable à ce lien https://wiki.dolibarr.org/index.php?title=Table_llx_product */
        

        //Recuperer un artcle en particulier
        
        /*$nom_client = 'SCVAMSENS';

        $listProduits = [];
        $produitParam =  [
            "limit" => "1", 
            "sortfield" => "rowid",
            "sqlfilters" => "(t.ref:=:'".$nom_client."')"
            
        ];
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
        }*/
        
        
        
        return $this->render('site/index.html.twig', [
            'articles' => $listProduits,
        ]);
        //return new JsonResponse($listProduitsResult);
    }

    #[Route('/home_manager', name: 'home_manager')]
    public function homeAfterManager(Dolibarr $dolibarr): Response
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

        $listProduits = [];
        $produitParam = ["limit" => 4, "sortfield" => "rowid"];
        $listProduitsResult = $dolibarr->CallAPI("GET", "services", $produitParam);
        
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
        ;
        
        return $this->render('site/index.html.twig', [
            'articles' => $listProduits,
        ]);
    }

    
}