<?php

namespace App\Controller;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/militaire', name: 'api_militaire_')]
class ApiMilitaireController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'message' => 'Bienvenue sur le tableau de bord militaire',
            'utilisateur' => [
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'solde_permissions' => $user->getSoldePermissions(),
            ]
        ]);
    }

    #[Route('/permission', name: 'demande_permission', methods: ['POST'])]
    public function demandePermission(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type'], $data['date_debut'], $data['date_fin'], $data['motif'])) {
            return $this->json(['message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        $permission = new Permission();
        $permission->setUser($user);
        $permission->setType($data['type']);
        $permission->setDateDebut(new \DateTime($data['date_debut']));
        $permission->setDateFin(new \DateTime($data['date_fin']));
        $permission->setStatut('en attente');
        $permission->setMotif($data['motif']);
        $permission->setCreatedAt(new \DateTimeImmutable());

        $em->persist($permission);
        $em->flush();

        return $this->json([
            'message' => 'Demande de permission soumise avec succès !',
            'permission_id' => $permission->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/mes-permissions', name: 'mes_permissions', methods: ['GET'])]
    public function mesPermissions(): JsonResponse
    {
        $user = $this->getUser();
        $permissions = $user->getPermissions();

        $data = [];

        foreach ($permissions as $permission) {
            $data[] = [
                'id' => $permission->getId(),
                'type' => $permission->getType(),
                'date_debut' => $permission->getDateDebut()->format('Y-m-d'),
                'date_fin' => $permission->getDateFin()->format('Y-m-d'),
                'statut' => $permission->getStatut(),
                'motif' => $permission->getMotif(),
            ];
        }

        return $this->json([
            'permissions' => $data
        ]);
    }
}
