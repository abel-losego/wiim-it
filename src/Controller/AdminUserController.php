<?php

namespace App\Controller;

use App\Entity\User;
use App\Dolibarr\Dolibarr;
use App\Form\ModifyUserForm;
use App\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users')]
    public function indexUsers(Dolibarr $dolibarr, UserRepository $userRepo): Response
    {
        $users = $userRepo->findBy(array('is_active' => true));
        //Recuperer le compte Dolibarr de l'utilisateur par son email
        $listIdUsers = [];
        foreach($users as $user) {
            $userParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.rowid:=:'".$user->getIdDOli()."')"];
            $UserResult = $dolibarr->CallAPI("GET", "thirdparties", $userParam);
            $UserResult = json_decode($UserResult, true);
        
            if (isset($UserResult["error"]) && $UserResult["error"]["code"] >= "300") {
                return new JsonResponse($UserResult);
            } else {
                $UserResult= $UserResult[0];
                $listIdUsers[intval($user->getId())] = [
                    
                    'id_doli' => html_entity_decode($UserResult["id"], ENT_QUOTES), 
                    'id_stripe' => html_entity_decode($UserResult["ref_ext"], ENT_QUOTES), 
                ];    
                
            }
        
        }

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'id_users' => $listIdUsers,
        ]);

    }

    #[Route('/admin/user/{id}/edit', name:"user_edit")]
    public function modifyUser(Dolibarr $dolibarr, User $user, Request $request, EntityManagerInterface $manager): Response
    {   
        //Recuperer le compte Dolibarr de l'utilisateur par son email

        $userParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.email:=:'".$user->getEmail()."')"];
        $listUsersResult = $dolibarr->CallAPI("GET", "thirdparties", $userParam);
        $newUserResult = json_decode($listUsersResult, true);
        //Recuperation du premier élément (qui est un tableau contenant les informations dolibarr du compte) du tableau contenant tous les comptes chargés (ici 1 seul)
        $newUserResult = $newUserResult[0];

        $id_doli = $newUserResult["id"];
        $id_stripe = $newUserResult["ref_ext"];
        
        if ($_SERVER ['REQUEST_METHOD'] == 'POST'){

            $user->setSurname(stripslashes(trim($_POST['nom'])));
            $user->setName(stripslashes(trim($_POST['prenom'])));
            $user->setSociety(stripslashes(trim($_POST['societe'])));
            $user->setPhone(stripslashes(trim($_POST['telephone'])));
            $user->setEmail(stripslashes(trim($_POST['email'])));

            
             //Mise à jour de compte dans la bdd du site
            $manager->persist($user);
            $manager->flush();
            


            /*$stripe = new \Stripe\StripeClient(
                'sk_test_51IvdmNKRg3070SDB42d5izgPSYy5U52wRjPT7LPwaPBheFBhmyvGmxjQ2dfITw0pGEDUgVaIX7AqgoLpgAECkze500XXleuhgl'
            );

            $stripe->customers->update(
                $id_stripe,
                [
                    'email' => $user->getEmail(),
                    'name' => $user->getSurname() ." ". $user->getName() ,
                    'phone' => $user->getPhone(),
                ]
              );*/

            //Mise à jour de compte dolibarr
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
            /*$this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@wiim-it.com', 'Mailer'))
                    ->to($user->getEmail())
                    ->subject('Vos informations ont bien été modifiées')
                    ->htmlTemplate('registration/modification_email.html.twig')
            );*/

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
        
    }

    #[Route('/admin/user/{id}/delete', name:"user_desac")]
    public function deleteArticle(Dolibarr $dolibarr, User $user, EntityManagerInterface $entityManager): Response 
    {     
        //Recuperer le compte Dolibarr de l'utilisateur par son email

        $userParam = ["limit" => 1, "sortfield" => "rowid", "sqlfilters" => "(t.email:=:'".$user->getEmail()."')"];
        $listUsersResult = $dolibarr->CallAPI("GET", "thirdparties", $userParam);
        $newUserResult = json_decode($listUsersResult, true);

        //Mise en mode inactif du compte doli (1 = actif, 0 = inactif)
        $newUserResult["status"]= 0;
            
        //Envoi du compte ds la bdd dolibarr
        $newClientResult = $dolibarr->CallAPI("PUT", "thirdparties", json_encode($newUserResult));
        $newClientResult = json_decode($newClientResult, true);

        //Faire en sorte que le compte soit inactif dans la BDD du site
        $user->setIsActive(false);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();     

        // Ajout d'un message flash de confirmation
        $this->addFlash('delete_success', 'Le compte a bien été supprimé');

        //envoi d'un email d'information au compte
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@wiim-it.com', 'Mailer'))
                    ->to($user->getEmail())
                    ->subject('Votre compte a bien été supprimé')
                    ->htmlTemplate('registration/deletion_account.html.twig')
            );

        return $this->redirectToRoute('admin_users');
    }
}
