<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Article;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\InputBag;

class BuyNowController extends AbstractController
{
    #[Route('/buy/now', name: 'buy_now')]
    public function buyNow(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('buy_now/index.html.twig', [
            'controller_name' => 'BuyNowController',
        ]);
    }

    #[Route('/payment-error', name: 'payment-error')]
    public function paymentError(): Response
    {
        return $this->render('buy_now/payment-error.html.twig', [
        ]);
    }

    #[Route('/payment-success', name: 'payment-success')]
    public function paymentSuccess(): Response
    {
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
        
        
        
        return $this->render('buy_now/payment-success.html.twig', [
        ]);
    }

    #[Route('/create-checkout-session', name: 'first')]
    public function checkout(ArticleRepository $repo/*, Request $request, Response $response*/): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');
        
        //$id_article = $request->cookie->get('name');

        $id_article_voulu = $_COOKIE["article"];
        $article_voulu = $repo->find($id_article_voulu);
        //$response->headers->setCookie(Cookie::create('foo', 'bar'));

        $session = \Stripe\Checkout\Session::create([
              'payment_method_types' => ['card'],
              'line_items' => [
                [
                  'price' => $article_voulu->getIdStripe(),
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
        
    }

    #[Route('/add-payment-method', name: 'add_payment_method')]
    public function addPaymentMethod(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

    }

    
    


    #[Route('/manage-your-account', name: 'account_manager')]
    public function payment(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
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

        /* Commande qui ont servi à tester iframe
        
        return new JsonResponse([ 'url' => $session->url ]);
        
        
        return $this->render('buy_now/payment.html.twig', [
            'url' => $session->url,
        ]);*/
    }

    #[Route('/payment-test', name: 'payment-test')]
    public function paymentTest(): Response
    {
        return $this->render('buy_now/payment.html.twig', [

        ]);
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