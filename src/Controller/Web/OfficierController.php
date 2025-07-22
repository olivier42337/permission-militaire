<?php

namespace App\Controller\Web;

use App\Entity\Permission;
use App\Entity\Programme;
use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProgrammeType;

#[Route('/officier')]
class OfficierController extends AbstractController
{
    #[Route('/dashboard', name: 'app_officier_dashboard')]
    public function dashboard(PermissionRepository $repo): Response
    {
        $user = $this->getUser();
        $permissions = $repo->findByUniteOrderedByDate($user->getUnite());

        return $this->render('officier/dashboard.html.twig', [
            'user' => $user,
            'permissions' => $permissions
        ]);
    }

    #[Route('/demandes', name: 'app_officier_demandes')]
    public function demandes(PermissionRepository $repo): Response
    {
        $user = $this->getUser();
        $permissions = $repo->findPendingByUnite($user->getUnite());

        return $this->render('officier/demandes.html.twig', [
            'permissions' => $permissions
        ]);
    }

    #[Route('/permission/{id}/valider', name: 'app_officier_permission_valider', methods: ['POST'])]
    public function valider(Permission $permission, Request $request, EntityManagerInterface $em): Response
    {
        $commentaire = $request->request->get('commentaire', 'Validé par l\'officier');
        $permission->setStatut('acceptée')->setCommentaire($commentaire);
        $em->flush();

        $this->addFlash('success', 'Permission validée.');
        return $this->redirectToRoute('app_officier_demandes');
    }

    #[Route('/permission/{id}/refuser', name: 'app_officier_permission_refuser', methods: ['POST'])]
    public function refuser(Permission $permission, Request $request, EntityManagerInterface $em): Response
    {
        $commentaire = $request->request->get('commentaire', 'Refusé par l\'officier');
        $permission->setStatut('refusée')->setCommentaire($commentaire);
        $em->flush();

        $this->addFlash('danger', 'Permission refusée.');
        return $this->redirectToRoute('app_officier_demandes');
    }

    #[Route('/calendrier', name: 'app_officier_calendrier')]
    public function calendrier(PermissionRepository $permissionRepo): Response
    {
        $user = $this->getUser();
        $permissions = $permissionRepo->findAcceptedByUnite($user->getUnite());

        return $this->render('officier/calendrier.html.twig', [
            'permissions' => $permissions
        ]);
    }

    #[Route('/calendrier/data', name: 'app_officier_calendrier_data')]
    public function calendrierData(PermissionRepository $permissionRepo, ProgrammeRepository $programmeRepo): JsonResponse
    {
        $user = $this->getUser();
        $unite = $user->getUnite();
        $events = [];

        foreach ($permissionRepo->findAcceptedByUnite($unite) as $permission) {
            $events[] = [
                'title' => $permission->getUser()->getNom() . ' (' . $permission->getType() . ')',
                'start' => $permission->getDateDebut()->format('Y-m-d'),
                'end'   => $permission->getDateFin()->modify('+1 day')->format('Y-m-d'),
                'color' => '#198754'
            ];
        }

        foreach ($programmeRepo->findByUnite($unite) as $programme) {
            $color = match ($programme->getType()) {
                'mission' => '#0d6efd',
                'stage' => '#ffc107',
                default => '#6c757d'
            };

            $events[] = [
                'title' => $programme->getUser()->getNom() . ' (' . $programme->getType() . ')',
                'start' => $programme->getDateDebut()->format('Y-m-d'),
                'end'   => $programme->getDateFin()->modify('+1 day')->format('Y-m-d'),
                'color' => $color
            ];
        }

        return $this->json($events);
    }

   #[Route('/programme/ajout', name: 'app_officier_ajouter_programme')]
public function ajouterProgramme(Request $request, EntityManagerInterface $em): Response
{
    $programme = new Programme();
    $programme->setUser($this->getUser());

    // ✅ Utilise le bon formType ici
    $form = $this->createForm(ProgrammeType::class, $programme);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($programme);
        $em->flush();
        $this->addFlash('success', 'Programme ajouté avec succès.');
        return $this->redirectToRoute('app_officier_dashboard');
    }

    return $this->render('officier/ajouter_programme.html.twig', [
        'form' => $form->createView(),
    ]);
}
    #[Route('/utilisateurs', name: 'app_officier_liste_utilisateurs')]
    public function listeUtilisateurs(EntityManagerInterface $em): Response
    {
        $utilisateurs = $em->getRepository(User::class)->findByRole('ROLE_MILITAIRE');

        return $this->render('officier/utilisateurs.html.twig', [
            'utilisateurs' => $utilisateurs
        ]);
    }

    #[Route('/militaire/ajouter', name: 'app_officier_ajouter_militaire')]
    public function ajouterMilitaire(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('email', EmailType::class)
            ->add('grade', TextType::class)
            ->add('unite', TextType::class)
            ->add('password', TextType::class, [
                'label' => 'Mot de passe (défini par l\'officier)'
            ])
            ->add('submit', SubmitType::class, ['label' => 'Créer le compte'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_MILITAIRE']);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte militaire créé avec succès.');
            return $this->redirectToRoute('app_officier_liste_utilisateurs');
        }

        return $this->render('officier/ajouter_militaire.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/programmes', name: 'app_officier_liste_programmes')]
    public function listeProgrammes(ProgrammeRepository $repo): Response
    {
        $user = $this->getUser();
        $programmes = $repo->findByUniteOrderedByDate($user->getUnite());

        return $this->render('officier/liste_programmes.html.twig', [
            'programmes' => $programmes
        ]);
    }
    #[Route('/militaire/{id}/modifier', name: 'modifier_militaire')]
public function modifierMilitaire(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher
): Response {
    $user = $em->getRepository(User::class)->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Militaire non trouvé.');
    }

    $form = $this->createFormBuilder($user)
        ->add('nom', TextType::class)
        ->add('prenom', TextType::class)
        ->add('email', EmailType::class)
        ->add('grade', TextType::class)
        ->add('unite', TextType::class)
        ->add('password', TextType::class, [
            'label' => 'Nouveau mot de passe (optionnel)',
            'required' => false
        ])
        ->add('submit', SubmitType::class, ['label' => 'Enregistrer'])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if ($form->get('password')->getData()) {
            $hashedPassword = $hasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
        }

        $em->flush();
        $this->addFlash('success', 'Le compte militaire a été mis à jour.');
        return $this->redirectToRoute('app_officier_liste_utilisateurs');
    }

    return $this->render('officier/modifier_militaire.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/militaire/{id}/supprimer', name: 'supprimer_militaire', methods: ['POST'])]
public function supprimerMilitaire(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    CsrfTokenManagerInterface $csrfTokenManager
): Response {
    $user = $em->getRepository(User::class)->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Militaire introuvable.');
    }

    $submittedToken = $request->request->get('_token');
    if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete' . $user->getId(), $submittedToken))) {
        throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }

    $em->remove($user);
    $em->flush();

    $this->addFlash('success', 'Militaire supprimé avec succès.');
    return $this->redirectToRoute('app_officier_liste_utilisateurs');
}
}
