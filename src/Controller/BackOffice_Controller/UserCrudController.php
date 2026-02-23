<?php

namespace App\Controller\BackOffice_Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
final class UserCrudController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepo): Response
    {
        $users = $userRepo->search(
            $request->query->get('q'),
            $request->query->get('role'),
            100
        );
        $totalUsers = $userRepo->count([]);
        $totalAdmins = $userRepo->countByRole(Role::ADMIN->value);
        $totalRegular = $userRepo->countByRole(Role::USER->value);
        $verifiedCount = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isVerified = true')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('BackOffice/user/index.html.twig', [
            'users' => $users,
            'stats' => [
                'total' => $totalUsers,
                'admins' => $totalAdmins,
                'users' => $totalRegular,
                'verified' => (int) $verifiedCount,
            ],
        ]);
    }

    #[Route('/ajax', name: 'app_user_ajax', methods: ['GET'])]
    public function ajaxSearch(Request $request, UserRepository $userRepo): Response
    {
        $users = $userRepo->search(
            $request->query->get('q'),
            $request->query->get('role'),
            50
        );
        return $this->render('BackOffice/user/_user_table.html.twig', ['users' => $users]);
    }

    #[Route('/csv', name: 'app_user_csv', methods: ['GET'])]
    public function csvExport(Request $request, UserRepository $userRepo): StreamedResponse
    {
        $users = $userRepo->search(
            $request->query->get('q'),
            $request->query->get('role'),
            5000
        );
        $response = new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Email', 'Username', 'Address', 'Telephone', 'Role', 'Verified'], ';');
            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->getId(),
                    $u->getEmail(),
                    $u->getUsername(),
                    $u->getAddress() ?? '',
                    $u->getTelephone() ?? '',
                    $u->getRoles()[0] ?? '',
                    $u->isVerified() ? 'Oui' : 'Non',
                ], ';');
            }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d_H-i') . '.csv"');
        return $response;
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $user->setRoles(Role::USER);
        $form = $this->createForm(UserType::class, $user, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('password')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('BackOffice/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('BackOffice/user/show.html.twig', ['user' => $user]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['require_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('password')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('BackOffice/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('app_user_index');
    }
}
