<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Militaire
        $militaire = new User();
        $militaire->setEmail('militaire@example.com');
        $militaire->setPassword($this->hasher->hashPassword($militaire, 'militaire123'));
        $militaire->setRoles(['ROLE_MILITAIRE']);
        $militaire->setNom('Martin');
        $militaire->setPrenom('Jean');
        $militaire->setGrade('Caporal');
        $militaire->setUnite('2e REG');
        $militaire->setSoldePermissions(20);
        $militaire->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($militaire);

        // Officier
        $officier = new User();
        $officier->setEmail('officier@example.com');
        $officier->setPassword($this->hasher->hashPassword($officier, 'officier123'));
        $officier->setRoles(['ROLE_OFFICIER']);
        $officier->setNom('Dubois');
        $officier->setPrenom('Claire');
        $officier->setGrade('Lieutenant');
        $officier->setUnite('2e REG');
        $officier->setSoldePermissions(0);
        $officier->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($officier);

        // Administrateur RH
        $rh = new User();
        $rh->setEmail('rh@example.com');
        $rh->setPassword($this->hasher->hashPassword($rh, 'rh123'));
        $rh->setRoles(['ROLE_RH']);
        $rh->setNom('Diallo');
        $rh->setPrenom('Fatou');
        $rh->setGrade('Sergent-Chef');
        $rh->setUnite('RH Centrale');
        $rh->setSoldePermissions(0);
        $rh->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($rh);

        $manager->flush();
    }
}
