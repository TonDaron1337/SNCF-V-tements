<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index')]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $utilisateur = $this->getUser();
        $commandes = $commandeRepository->findBy(['utilisateur' => $utilisateur]);

        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/gestion', name: 'app_commande_gestion')]
    #[IsGranted('ROLE_DPX')]
    public function gestion(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findAll();

        return $this->render('commande/gestion.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/{id}/statut', name: 'app_commande_statut', methods: ['POST'])]
    #[IsGranted('ROLE_DPX')]
    public function changerStatut(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        $statut = $request->request->get('statut');
        if (in_array($statut, ['en_attente', 'acceptee', 'refusee'])) {
            $commande->setStatut($statut);
            $entityManager->flush();
            
            $notificationService->notifierChangementStatutCommande($commande);
        }

        return $this->redirectToRoute('app_commande_gestion');
    }
}