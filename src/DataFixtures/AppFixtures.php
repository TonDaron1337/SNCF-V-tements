<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Entity\Produit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        // Création des rôles
        $roleOperateur = new Role();
        $roleOperateur->setNom('Opérateur');
        $manager->persist($roleOperateur);

        $roleDPX = new Role();
        $roleDPX->setNom('DPX');
        $manager->persist($roleDPX);

        $roleDUO = new Role();
        $roleDUO->setNom('DUO');
        $manager->persist($roleDUO);

        // Création des utilisateurs de test
        $utilisateurs = [
            ['1234567A', 'operateur@sncf.fr', 'Dupont', 'Jean', $roleOperateur],
            ['2345678B', 'dpx@sncf.fr', 'Martin', 'Sophie', $roleDPX],
            ['3456789C', 'duo@sncf.fr', 'Dubois', 'Pierre', $roleDUO]
        ];

        foreach ($utilisateurs as [$numeroCp, $email, $nom, $prenom, $role]) {
            $user = new Utilisateur();
            $user->setNumeroCp($numeroCp)
                 ->setEmail($email)
                 ->setNom($nom)
                 ->setPrenom($prenom)
                 ->setRole($role);

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        // Création des produits
        $produits = [
            ['T-shirt Gris', 'T-shirt gris confortable', 'tshirt', ['M', 'L', 'XL', '2XL']],
            ['Veste HV', 'Veste haute visibilité', 'veste', ['M', 'L', 'XL', '2XL']],
            ['Pantalon HV', 'Pantalon haute visibilité', 'pantalon', ['M', 'L', 'XL', '2XL']]
        ];

        foreach ($produits as [$nom, $description, $categorie, $tailles]) {
            foreach ($tailles as $taille) {
                $produit = new Produit();
                $produit->setNom($nom)
                        ->setDescription($description)
                        ->setCategorie($categorie)
                        ->setTaille($taille)
                        ->setQuantite(50);
                
                $manager->persist($produit);
            }
        }

        $manager->flush();
    }
}