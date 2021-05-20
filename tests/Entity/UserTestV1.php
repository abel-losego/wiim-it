<?php



use App\Entity\User;

use PHPUnit\Framework\TestCase ;

class UserTest extends TestCase
{
    public function testUri()
    {
        $user = new User();
        $email = "voiture@gmial.com";
        
        $user->setEmail($email);
        $this->assertEquals("voiture@gmial.com", $user->getEmail(), "Getter et Getter de email de User")
    }
}