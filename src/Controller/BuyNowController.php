<?php

namespace App\Controller;

use App\Dolibarr\Dolibarr;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BuyNowController extends AbstractController
{
    
    #[Route('/payment-error', name: 'payment-error')]
    public function paymentError(): Response
    {
        return $this->render('buy_now/payment-error.html.twig', [
        ]);
    }

    #[Route('/payment-success', name: 'payment-success')]
    public function paymentSuccess(Dolibarr $dolibarr): Response
    {
        if (!$_COOKIE["article"]){
            return $this->render('buy_now/payment-success.html.twig');
        }
        if($_COOKIE["article"] == "0" ){
            return $this->render('buy_now/payment-success.html.twig');
        }
        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');
        


        //Recupération de l'utilisateur stripe par son id
        $customer = \Stripe\Customer::retrieve([
            'id' => $this->getUser()->getStripeId(),

        ]);

        //Mise à jour de l'email du client si il a été modifié lors de la commande sur stripe
        if($this->getUser()->getEmail() != $customer->email){
            $user= $this->getUser();
            $user->setEmail($customer->email);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        
        
        //Recuperer l'article Dolibarr par son id pour envoyer la commande à dolibarr
        $appli = $_COOKIE["article"];
        $userParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.ref_ext:=:'".$appli."')"];
        $productResult = $dolibarr->CallAPI("GET", "products", $userParam);
        $productResult = json_decode($productResult, true);

        $newCommandeLine = [];
        $productResult= $productResult[0];
        $newCommandeLine[] = [
            "desc"		=> $productResult["label"],
            "subprice"	=> $productResult["price"],
            "qty"		=> 1,
            "tva_tx"	=> $productResult["tva_tx"],
            "fk_product"=> $productResult["id"]
        ];


        if (count($newCommandeLine) > 0) {
            $newOrder = [
                
                "type" 			=> "0",
                "socid" 	    => $this->getUser()->getIdDoli(),
                "note_private"	=> "Commande effectuée automatiquement depuis le site internet de Wiim",
                "lines"			=> $newCommandeLine,
                "date"          => time()
            ];
        
            $newOrderResult = $dolibarr->CallAPI("POST", "orders", json_encode($newOrder));
            $newOrderResult = json_decode($newOrderResult, true);
        }
        setcookie("article", "0");
        
        return $this->render('buy_now/payment-success.html.twig', [
        ]);
    }

    #[Route('/create-checkout-session', name: 'first')]
    public function checkout( Dolibarr $dolibarr/*ArticleRepository $repo, Request $request, Response $response*/): Response
    {
        //on verifie que l'utilisateur est connecté
        if(!$this->getUser()) {
            $this->addFlash('stripe_customer_error', 'Vous devez être connecté pour effectuer une commande');
            return $this->redirectToRoute('app_login');
        }

        if($this->getUser()->getIsActive() == false) {
            
            $this->addFlash('account_desac', 'Votre compte a été désactivée, veuillez contacter le service client');
            return $this->redirectToRoute('contact');
        }

        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');
        

        //Recupération de l'id stripe de l'article que le client veut acheter via les cookies, retour à l'accueil si aucun n'a été transmis 
        $id_article_voulu = $_COOKIE["article"];
        if (!$id_article_voulu){
            return $this->redirectToRoute('home');
        }
        
        $stripe = new \Stripe\StripeClient(
            'sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl'
        );

        
        // Création d'un interface de paiement dans stripe
        $session = \Stripe\Checkout\Session::create([
              'payment_method_types' => ['card'],
              'line_items' => [
                [
                  'price' => $id_article_voulu,
                  //'price' => 'price_1Iw6VEKRg3070SDB9ar6TDPo',
                  'quantity' => 1,
                ],
            ],
              'mode' => 'subscription',
              'customer'=> $this->getUser()->getStripeId(),
              'success_url' => $this->generateUrl('payment-success', [], UrlGeneratorInterface::ABSOLUTE_URL),
              'cancel_url' => $this->generateUrl('payment-error', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            
        return new JsonResponse([ 'id' => $session->id ]);
        //return $this->render('contact/contact.html.twig');
    }
    
    //Permet au client de gérer son abonnement 
    #[Route('/manage-your-account', name: 'account_manager')]
    public function payment(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if($this->getUser()->getIsActive() == false) {
            $this->addFlash('account_desac', 'Votre compte a été désactivée, veuillez contacter le service client');
            return $this->redirectToRoute('contact');
            
        }
        
        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');
        
        //Verification que le compte a déjà commandé un service ( si il a deja un comptre client stripe et un moyen de paiement)
        if($this->getUser()->getStripeId()==null) {
            
        
            $this->addFlash('stripe_customer_error', 'Vous devez avoir réalisé une commande pour accèder à notre interface de facturation');
    
            return $this->redirectToRoute('home');
            
        }
        
        // Authenticate your user.
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $this->getUser()->getStripeId(),
            'return_url' => $this->generateUrl('home_manager', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        
        return $this->redirect($session->url);

    }


}

/*

#[Route('/Payment-Intent', name: 'Payment-Intent')]
    public function paymentIntent(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');

        $intent = \Stripe\PaymentIntent::create([
            'amount' => 1099,
            'currency' => 'usd',
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
          ]);


          return $this->render('buy_now/index.html.twig', [
            'paymentIntent' => $intent,
        ]);

          //return new JsonResponse([ 'id' => $session->id ]);
    }
*/