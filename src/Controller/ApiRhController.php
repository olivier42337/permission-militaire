<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Audit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/rh', name: 'api_rh_')]
class ApiRhController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em): JsonResponse
    {
        $totalUtilisateurs = $em->getRepository(User::class)->count([]);
        $totalAudits = $em->getRepository(Audit::class)->count([]);

        return $this->json([
            'message' => 'Bienvenue sur le tableau de bord RH',
            'utilisateurs_total' => $totalUtilisateurs,
            'audits_total' => $totalAudits
        ]);
    }

    #[Route('/utilisateurs', name: 'liste_utilisateurs', methods: ['GET'])]
    public function listeUtilisateurs(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $result = [];

        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'solde_permissions' => $user->getSoldePermissions(),
                'unite' => $user->getUnite(),
            ];
        }

        return $this->json($result);
    }

    #[Route('/utilisateur/creer', name: 'creer_utilisateur', methods: ['POST'])]
    public function creerUtilisateur(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['roles'], $data['nom'], $data['prenom'], $data['grade'], $data['unite'])) {
            return $this->json(['message' => 'Champs manquants'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRoles($data['roles']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setGrade($data['grade']);
        $user->setUnite($data['unite']);
        $user->setSoldePermissions($data['solde_permissions'] ?? 20);
        $user->setCreatedAt(new \DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur créé avec succès !'], Response::HTTP_CREATED);
    }

    #[Route('/utilisateur/{id}/solde', name: 'modifier_solde', methods: ['PATCH'])]
    public function modifierSolde(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        $data = json_decode($request->getContent(), true);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!isset($data['solde_permissions'])) {
            return $this->json(['message' => 'Solde non fourni'], Response::HTTP_BAD_REQUEST);
        }

        $user->setSoldePermissions($data['solde_permissions']);
        $em->flush();

        return $this->json(['message' => 'Solde mis à jour avec succès']);
    }

    #[Route('/audit', name: 'liste_audits', methods: ['GET'])]
    public function audits(EntityManagerInterface $em): JsonResponse
    {
        $audits = $em->getRepository(Audit::class)->findBy([], ['createdAt' => 'DESC']);
        $result = [];

        foreach ($audits as $audit) {
            $result[] = [
                'id' => $audit->getId(),
                'action' => $audit->getAction(),
                'user' => $audit->getUser()?->getEmail(),
                'date' => $audit->getCreatedAt()?->format('Y-m-d H:i'),
            ];
        }

        return $this->json($result);
    }

    #[Route('/conflits', name: 'voir_conflits', methods: ['GET'])]
    public function voirConflits(): JsonResponse
    {
        // Placeholder : à implémenter avec logique de détection réelle
        return $this->json([
            'message' => 'Détection automatique des conflits à venir',
            'conflits' => [] // Liste à générer plus tard
        ]);
    }
    
}
