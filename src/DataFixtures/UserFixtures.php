<?php
//Création de fausses données

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;


class UserFixtures extends Fixture
{
    private $passwordEncoder;

      public function __construct(UserPasswordEncoderInterface $passwordEncoder)
      {
          $this->passwordEncoder = $passwordEncoder;
      }
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('hey@gmail.com')
            ->setPassword($this->passwordEncoder->encodePassword(
                          $user,
                         'the_new_password'
                         ));
        $manager->persist($user);
        $manager->flush();
    }
}
