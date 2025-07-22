<?php

namespace App\Controller\Web;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/militaire', name: 'app_militaire_')]
class MilitaireController extends AbstractController
{
    #[Route('/demande', name: 'demande')]
    public function demandePermission(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request;

            $permission = new Permission();
            $permission->setUser($this->getUser());
            $permission->setType($data->get('type'));
            $permission->setDateDebut(new \DateTime($data->get('date_debut')));
            $permission->setDateFin(new \DateTime($data->get('date_fin')));
            $permission->setMotif($data->get('motif'));
            $permission->setStatut('en attente');
            $permission->setCreatedAt(new \DateTimeImmutable());

            $em->persist($permission);
            $em->flush();

            // ✅ Nouveau message clair pour le militaire
            $this->addFlash('success', '✅ Votre demande de permission a bien été enregistrée et sera traitée par votre officier.');

            return $this->redirectToRoute('app_militaire_dashboard');
        }

        return $this->render('militaire/demande.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $permissions = $user->getPermissions();

        return $this->render('militaire/dashboard.html.twig', [
            'user' => $user,
            'permissions' => $permissions
        ]);
    }
}
