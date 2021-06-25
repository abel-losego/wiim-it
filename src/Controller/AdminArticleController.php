<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Dolibarr\Dolibarr;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminArticleController extends AbstractController
{
    #[Route('/admin/articles', name: 'admin_articles')]
    #[Route('/admin', name: 'admin_home')]
    public function indexArticles(Dolibarr $dolibarr, ArticleRepository $articleRepo): Response
    {
        $articles = $articleRepo->findAll();
        //Recuperer le compte Dolibarr de l'utilisateur par son email
        $listIdArticles = [];
        foreach($articles as $article) {
            $articleParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.rowid:=:'".$article->getIdDOli()."')"];
            $articleResult = $dolibarr->CallAPI("GET", "products", $articleParam);
            $articleResult = json_decode($articleResult, true);
        
            if (isset($articleResult["error"]) && $articleResult["error"]["code"] >= "300") {
                return new JsonResponse($articleResult);
            } else {
                $articleResult= $articleResult[0];
                $listIdArticles[intval($article->getId())] = [
                    
                    'id_doli' => html_entity_decode($articleResult["id"], ENT_QUOTES), 
                    'id_stripe' => html_entity_decode($articleResult["ref_ext"], ENT_QUOTES), 
                ];    
                
            }
        }
        return $this->render('admin/articles.html.twig', [
            'articles' => $articles,
            'id_articles' => $listIdArticles,
        ]);
    }

    /*#[Route('/admin/article/{id}/edit', name:"article_edit")]
    public function modifyArticle(Dolibarr $dolibarr, Article $article, EntityManagerInterface $manager): Response
    {   
        //Recuperer le compte Dolibarr de l'utilisateur par son email

        $articleParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.rowid:=:'".$article->getIdDoli()."')"];
        $listArticlesResult = $dolibarr->CallAPI("GET", "products", $articleParam);
        $newArticleResult = json_decode($listArticlesResult, true);
        $id_doli = $newArticleResult["id"];
        $id_stripe = $newArticleResult["ref_ext"];
        
        if ($_SERVER ['REQUEST_METHOD'] == 'POST'){

            $user->setSurname(stripslashes(trim($_POST['nom'])));
            $user->setName(stripslashes(trim($_POST['prenom'])));
            $user->setSociety(stripslashes(trim($_POST['societe'])));
            $user->setPhone(stripslashes(trim($_POST['telephone'])));
            $user->setEmail(stripslashes(trim($_POST['email'])));


            $manager->persist($user);
            $manager->flush();
            


            $stripe = new \Stripe\StripeClient(
                'sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl'
            );

            $stripe->customers->update(
                $id_stripe,
                [
                    'email' => $user->getEmail(),
                    'name' => $user->getSurname() ." ". $user->getName() ,
                    'phone' => $user->getPhone(),
                ]
              );
            if($user->getSociety()){
                
                $newUserResult["name"]= $user->getSociety();
                $newUserResult["email"]= $user->getEmail();

                $newUserResult["phone"]= $user->getPhone();

                } else {
                    //Mise à jour de compte dolibarr au nom du particulier
                    
                    $newUserResult["name"]= $user->getName() . " ". $user->getSurname();
                    $newUserResult["email"]= $user->getEmail();
                    $newUserResult["phone"]= $user->getPhone();
                }
            //Envoi du compte ds la bdd dolibarr
            $newClientResult = $dolibarr->CallAPI("PUT", "thirdparties", json_encode($newUserResult));
            $newClientResult = json_decode($newClientResult, true);
            $clientDoliId = $newClientResult;

            //envoi d'un email d'information au compte
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@wiim-it.com', 'Mailer'))
                    ->to($user->getEmail())
                    ->subject('Vos informations ont bien été modifiées')
                    ->htmlTemplate('registration/modification_email.html.twig')
            );

            // Ajout d'un message flash de confirmation
            $this->addFlash('modify_success', 'Les informations du compte ont bien été modifié');

            return $this->redirectToRoute('admin_users');
        }
        

        return $this->render('admin/modify_user.html.twig', [
            
            'section' => "Modifier un compte",
            'user' => $user, 
            'id_doli' => $id_doli,
            'id_stripe' => $id_stripe,       
            ]);
        
    }*/

    /*#[Route('/admin/article/{id}/delete', name:"article_desac")]
    public function deleteArticle(Dolibarr $dolibarr, Article $article, EntityManagerInterface $manager): Response 
    {     
        //Recuperer le compte dolibarr
        $test = 216;
        $productParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.rowid:=:'".$test."')"];
        $listProductsResult = $dolibarr->CallAPI("GET", "products", $productParam);
        $newProductResult = json_decode($listProductsResult, true);
        

        //Recuperation des id Stripe du compte
        $id_stripe = $newProductResult[0]["ref_ext"];

        return new JsonResponse($newProductResult);
        //Re dans dolibarr
        /*$userParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.email:=:'".$user->getEmail()."')"];
        $listUsersResult = $dolibarr->CallAPI("DELETE", "thirdparties", $userParam);
        $newUserResult = json_decode($listUsersResult, true);

        //Supprimer le compte dans stripe (les paiements sont conservées)
        $stripe = new \Stripe\StripeClient(
            'sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl'
        );
        $stripe->prices->retrieve(
            'prod_JZEz352kfI1H7j',
            ['active' => false]
          );

        $stripe->products->update(
            'prod_JZEz352kfI1H7j',
            ['active' => false]
          );

        //Supprimer le compte dans la BDD du site
        $manager->remove($user);
        $manager->flush();

        // Ajout d'un message flash de confirmation
        $this->addFlash('delete_success', 'Le compte a bien été supprimé');
        return $this->redirectToRoute('admin_users');
    }*/

    #[Route('/admin/article/new', name:"article_new")]
    public function createArticle(Dolibarr $dolibarr, Request $request, EntityManagerInterface $entityManager): Response 
    {     
        
        $article = new Article();
        $article->setIdStripe('Valeurtest');
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        //Verifie si le form a été soumis et si il est complet 
        if ($form->isSubmitted() && $form->isValid()) {
            
                $stripe = new \Stripe\StripeClient(
                    'sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl'
                );
                $produit = $stripe->products->create([
                'name' => $article->getName(),
                ]);
        
                $price = $stripe->prices->create([
                    'unit_amount' => $article->getPrice(),
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'month'],
                    'product' => $produit->id,
                ]);
                

                
            
                $newProduct = [
                    
                    "ref"	=> $article->getRef(),
                    "label"	=> $article->getName(),
                    "type" => 1,
                    "ref_ext" => $price->id,
                    "price_ttc" => $price->unit_amount/100,
                    "status" => 1,
                    //Represente le prix HT (en fr Taxes = 20%) et mis en euros (au préalable en centimes)
                    "price"=> $price->unit_amount/120,
                    "tva_tx"=>20.00,

                ];

                $newProductResult = $dolibarr->CallAPI("POST", "products", json_encode($newProduct));
                $newProductResult = json_decode($newProductResult, true);
                /*if (isset($newProductResult["error"]) && $newProductResult["error"]["code"] >= "300") {
                    // il y a eu une erreur
                        // Ajout d'un message flash de confirmation
                        $this->addFlash('new_article_error', 'Votre article n\'a pas été ajouté dans dolibarr');
                    }*/
                       
                $article->setIdStripe($price->id);
                $article->setIdDoli($newProductResult);
                
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($article);
                $entityManager->flush();

                return $this->redirectToRoute('admin_articles'); 
            }
        return $this->render('admin/new_article.html.twig', [
            'articleForm' => $form->createView(),
        ]);
    }
}
