<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stock')]
#[IsGranted('ROLE_DPX')]
class StockController extends AbstractController
{
    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        $stocks = [];
        foreach (Produit::CATEGORIES as $code => $nom) {
            $stocks[$code] = [
                'nom' => $nom,
                'tailles' => []
            ];
            foreach (Produit::TAILLES as $taille) {
                $produit = $produitRepository->findOneBy([
                    'categorie' => $code,
                    'taille' => $taille
                ]);
                $stocks[$code]['tailles'][$taille] = $produit ? $produit->getQuantite() : 0;
            }
        }

        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
            'tailles' => Produit::TAILLES
        ]);
    }

    #[Route('/ajuster/{categorie}/{taille}', name: 'app_stock_ajuster', methods: ['POST'])]
    public function ajusterStock(
        string $categorie,
        string $taille,
        Request $request,
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository
    ): Response {
        $quantite = (int) $request->request->get('quantite', 0);
        
        $produit = $produitRepository->findOneBy([
            'categorie' => $categorie,
            'taille' => $taille
        ]);

        if (!$produit) {
            $produit = new Produit();
            $produit->setCategorie($categorie)
                   ->setTaille($taille)
                   ->setNom(Produit::CATEGORIES[$categorie])
                   ->setDescription('Stock initial');
        }

        $produit->setQuantite($quantite);
        $entityManager->persist($produit);
        $entityManager->flush();

        return $this->redirectToRoute('app_stock_index');
    }

    #[Route('/historique', name: 'app_stock_historique', methods: ['GET'])]
    public function historique(ProduitRepository $produitRepository): Response
    {
        return $this->render('stock/historique.html.twig', [
            'mouvements' => $produitRepository->findMouvementsStock()
        ]);
    }
}