<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    #[Route('/my-account', name: 'app_my_account', methods: ['GET'])]
    public function myAccount(): Response
    {
        $user = $this->getUser();
        
        // Admin: render admin version
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('user/admin_my_account.html.twig');
        }
        
        // Regular user: render front-end version
        return $this->render('user/my_account_front.html.twig');
    }

#[Route('/update-account', name: 'app_update_account', methods: ['GET','POST'])]
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
        // Handle image upload
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

        return $this->redirectToRoute('app_my_account');
    }

    return $this->render('user/update_account.html.twig', [
        'form' => $form->createView(),
    ]);
}



#[Route('/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
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

        // Verify current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $form->get('currentPassword')->addError(
                new \Symfony\Component\Form\FormError('Mot de passe actuel incorrect.')
            );
        } else {
            $newPassword = $form->get('newPassword')->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $newPassword)
            );

            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès.');

            return $this->redirectToRoute('app_my_account');
        }
    }

    return $this->render('user/change_password.html.twig', [
        'form' => $form->createView(),
    ]);
}

}
