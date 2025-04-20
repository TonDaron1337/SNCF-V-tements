<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Produit;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function notifierStockBas(Produit $produit): void
    {
        $message = sprintf(
            'Le stock du produit %s (taille %s) est bas : %d pièces restantes.',
            $produit->getNom(),
            $produit->getTaille(),
            $produit->getQuantite()
        );

        if ($this->logger) {
            $this->logger->info('Notification de stock bas: ' . $message);
        }
    }

    public function notifierChangementStatutCommande(Commande $commande): void
    {
        $message = sprintf(
            'Le statut de la commande n°%d a été mis à jour : %s',
            $commande->getId(),
            $commande->getStatut()
        );

        if ($this->logger) {
            $this->logger->info('Notification de changement de statut: ' . $message);
        }
    }
}