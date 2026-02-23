<?php

namespace App\Controller\FrontOffice_Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Frontend "Mon compte" — stays on the front (no backend layout).
 * Backend "Mon Compte" remains app_my_account for admin sidebar.
 */
#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
final class CompteController extends AbstractController
{
    #[Route('', name: 'app_front_mon_compte', methods: ['GET'])]
    public function monCompte(): Response
    {
        return $this->render('FrontOffice/compte/mon_compte.html.twig');
    }

    #[Route('/modifier', name: 'app_front_update_account', methods: ['GET', 'POST'])]
    public function updateAccount(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $filesystem = new Filesystem();
                if ($user->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getImage();
                    if ($filesystem->exists($oldImagePath)) {
                        $filesystem->remove($oldImagePath);
                    }
                }
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/users';
                if (!$filesystem->exists($uploadDir)) {
                    $filesystem->mkdir($uploadDir, 0755);
                }
                $imageFile->move($uploadDir, $newFilename);
                $user->setImage('uploads/users/' . $newFilename);
            }
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_front_mon_compte');
        }

        return $this->render('FrontOffice/compte/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/changer-mot-de-passe', name: 'app_front_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $form->get('currentPassword')->addError(
                    new \Symfony\Component\Form\FormError('Mot de passe actuel incorrect.')
                );
            } else {
                $newPassword = $form->get('newPassword')->getData();
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $em->flush();
                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_front_mon_compte');
            }
        }

        return $this->render('FrontOffice/compte/changer_mot_de_passe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
