<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{
    private int $stockMinimum;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProduitRepository $produitRepository,
        private NotificationService $notificationService,
        int $stockMinimum
    ) {
        $this->stockMinimum = $stockMinimum;
    }

    public function ajusterStock(Produit $produit, int $quantite): void
    {
        $ancienneQuantite = $produit->getQuantite();
        $produit->setQuantite($quantite);
        
        $this->entityManager->persist($produit);
        $this->entityManager->flush();

        if ($quantite <= $this->stockMinimum) {
            $this->notificationService->notifierStockBas($produit);
        }
    }

    public function verifierDisponibilite(Produit $produit, int $quantiteDemandee): bool
    {
        return $produit->getQuantite() >= $quantiteDemandee;
    }

    public function getProduitsStockBas(): array
    {
        return $this->produitRepository->findStockBas($this->stockMinimum);
    }
}