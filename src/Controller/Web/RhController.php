<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Repository\ConflitRepository;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class RhController extends AbstractController
{
    #[Route('/rh/dashboard', name: 'app_rh_dashboard')]
    public function dashboard(PermissionRepository $permissionRepo, UserRepository $userRepo): Response
    {
        $stats = [
            'permissions_total' => $permissionRepo->count([]),
            'militaires' => count($userRepo->findByRole('ROLE_MILITAIRE')),
            'officiers' => count($userRepo->findByRole('ROLE_OFFICIER')),
            'rhes' => count($userRepo->findByRole('ROLE_RH')),
        ];

        return $this->render('rh/dashboard.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats
        ]);
    }

    #[Route('/rh/permissions', name: 'app_rh_permissions')]
    public function voirPermissions(PermissionRepository $permissionRepository): Response
    {
        $permissions = $permissionRepository->findAll();

        return $this->render('rh/permissions.html.twig', [
            'permissions' => $permissions,
        ]);
    }

    #[Route('/rh/utilisateurs', name: 'app_rh_utilisateurs')]
    public function utilisateurs(UserRepository $userRepository): Response
    {
        $utilisateurs = $userRepository->findAll();

        return $this->render('rh/utilisateurs.html.twig', [
            'utilisateurs' => $utilisateurs
        ]);
    }

    #[Route('/rh/utilisateur/{id}/modifier-role', name: 'app_rh_modifier_role')]
    public function modifierRole(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $nouveauRole = $request->request->get('role');
            $user->setRoles([$nouveauRole]);
            $em->flush();

            $this->addFlash('success', 'Rôle modifié avec succès.');
            return $this->redirectToRoute('app_rh_utilisateurs');
        }

        return $this->render('rh/modifier_role.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/rh/utilisateur/{id}/ajuster-solde', name: 'app_rh_ajuster_solde')]
    public function ajusterSolde(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $solde = (int) $request->request->get('solde');
            $user->setSoldePermissions($solde);
            $em->flush();

            $this->addFlash('success', 'Solde ajusté avec succès.');
            return $this->redirectToRoute('app_rh_utilisateurs');
        }

        return $this->render('rh/ajuster_solde.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/rh/statistiques', name: 'app_rh_statistiques')]
    public function statistiques(PermissionRepository $repo): Response
    {
        $permissions = $repo->findAll();

        $stats = [
            'total' => count($permissions),
            'acceptées' => 0,
            'refusées' => 0,
            'en attente' => 0,
        ];

        foreach ($permissions as $permission) {
            $statut = $permission->getStatut();
            if (isset($stats[$statut])) {
                $stats[$statut]++;
            }
        }

        return $this->render('rh/statistiques.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/rh/conflits', name: 'app_rh_conflits')]
    public function conflits(ConflitRepository $conflitRepository): Response
    {
        $conflits = $conflitRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('rh/conflits.html.twig', [
            'conflits' => $conflits,
        ]);
    }

    #[Route('/rh/conflits/supprimer/{id}', name: 'app_rh_conflit_supprimer')]
    public function supprimerConflit(int $id, ConflitRepository $conflitRepository, EntityManagerInterface $em): Response
    {
        $conflit = $conflitRepository->find($id);

        if (!$conflit) {
            $this->addFlash('danger', 'Conflit introuvable.');
        } else {
            $em->remove($conflit);
            $em->flush();
            $this->addFlash('success', 'Conflit supprimé avec succès.');
        }

        return $this->redirectToRoute('app_rh_conflits');
    }

    #[Route('/rh/conflits/alerter/{id}', name: 'app_rh_conflit_alerter')]
    public function alerterOfficier(int $id, ConflitRepository $conflitRepository, MailerInterface $mailer): Response
    {
        $conflit = $conflitRepository->find($id);

        if (!$conflit || !$conflit->getUser()) {
            $this->addFlash('danger', 'Officier introuvable pour ce conflit.');
            return $this->redirectToRoute('app_rh_conflits');
        }

        $officier = $conflit->getUser();

        $email = (new \Symfony\Component\Mime\Email())
            ->from('admin@militaire.test')
            ->to($officier->getEmail())
            ->subject('Conflit détecté')
            ->text("Bonjour " . $officier->getNom() . ", un conflit de planning a été détecté. Merci de le traiter rapidement.");

        $mailer->send($email);

        $this->addFlash('success', 'Un email d’alerte a été envoyé à l’officier.');

        return $this->redirectToRoute('app_rh_conflits');
    }
}
