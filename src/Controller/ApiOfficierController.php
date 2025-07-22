<?php

namespace App\Controller;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/officier', name: 'api_officier_')]
class ApiOfficierController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'message' => 'Bienvenue sur le tableau de bord officier',
            'nom' => $user->getNom(),
            'unite' => $user->getUnite(),
        ]);
    }

    #[Route('/demandes', name: 'liste_demandes', methods: ['GET'])]
    public function listeDemandes(EntityManagerInterface $em): JsonResponse
    {
        $demandes = $em->getRepository(Permission::class)->findBy([], ['createdAt' => 'DESC']);
        $resultats = [];

        foreach ($demandes as $permission) {
            $resultats[] = [
                'id' => $permission->getId(),
                'militaire' => $permission->getUser()->getNom() . ' ' . $permission->getUser()->getPrenom(),
                'type' => $permission->getType(),
                'date_debut' => $permission->getDateDebut()->format('Y-m-d'),
                'date_fin' => $permission->getDateFin()->format('Y-m-d'),
                'statut' => $permission->getStatut(),
                'motif' => $permission->getMotif(),
            ];
        }

        return $this->json([
            'demandes' => $resultats
        ]);
    }

    #[Route('/demande/{id}/traiter', name: 'traiter_demande', methods: ['POST'])]
    public function traiterDemande(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $permission = $em->getRepository(Permission::class)->find($id);

        if (!$permission) {
            return $this->json(['message' => 'Demande introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['action']) || !in_array($data['action'], ['accepter', 'refuser'])) {
            return $this->json(['message' => 'Action invalide'], Response::HTTP_BAD_REQUEST);
        }

        $permission->setStatut($data['action'] === 'accepter' ? 'acceptée' : 'refusée');
        $em->flush();

        return $this->json([
            'message' => 'Demande mise à jour avec succès',
            'statut' => $permission->getStatut()
        ]);
    }
}
