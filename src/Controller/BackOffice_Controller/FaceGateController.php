<?php

namespace App\Controller\BackOffice_Controller;

use App\Controller\Api\FaceController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/face-gate')]
class FaceGateController extends AbstractController
{
    /**
     * Page shown when admin must pass face verification to access dashboard.
     */
    #[Route('', name: 'app_face_gate', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($request->getSession()->get(FaceController::FACE_GATE_SESSION_KEY)) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = $this->getUser();
        $hasFaceEnrolled = $user && $user->getFaceDescriptor();

        return $this->render('BackOffice/face_gate.html.twig', [
            'hasFaceEnrolled' => (bool) $hasFaceEnrolled,
        ]);
    }
}
