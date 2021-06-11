<?php
// Controller de gestion de l'inscrption et des actions liées à celle-ci (envoie de mails de confirmation...)
namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use Symfony\Component\Asset\Package;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use App\Dolibarr\Dolibarr;

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, Dolibarr $dolibarr/*LoginFormAuthentificator $login, GuardAuthenticatorHandler $guard*/): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        //Verifie si le form a été soumis et si il est complet 
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $user->getPassword()
                )
            );
        

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            //Création de compte dolibarr 
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
            //envoi d'un email de confirmation du compte
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@wiim-it.com', 'Mailer'))
                    ->to($user->getEmail())
                    ->subject('Confirmer votre adresse Email ')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('verify_email_check', 'Un email de confirmation vous a été envoyé pour valider votre compte');
            
            //Permet d'être connecter après l'inscription 
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            $this->container->get('session')->set('_security_main', serialize($token));
            
            return $this->redirectToRoute('home');
        }
        
        $package = new Package(new EmptyVersionStrategy());

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'image_back' => $package->getUrl('/assets/images/user/02.jpg'),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, Dolibarr $dolibarr): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', 'Vous devez etre connecté pour valider votre compte');

            return $this->redirectToRoute('home');
        }


        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        
        \Stripe\Stripe::setApiKey('sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl');

        $customer = \Stripe\Customer::create([
            'email' => $this->getUser()->getEmail(),

        ]);
        $user= $this->getUser();

        $newClient = [
            "name" 			=> $user->getSociety(),
            "email"			=> $user->getEmail(),
            "ref_ext" 		=> $customer->id,
            "phone" 		=> $user->getPhone(),
            "client" => 3,
    
        ];
        $newClientResult = $dolibarr->CallAPI("PUT", "societe", json_encode($newClient));
        $newClientResult = json_decode($newClientResult, true);
        $clientDoliId = $newClientResult;

        
        
        
        $user->setIdDoli($clientDoliId);
        $user->setStripeId($customer->id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        
        return $this->redirectToRoute('home');
    }
}