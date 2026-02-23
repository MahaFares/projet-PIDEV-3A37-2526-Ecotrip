<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FaceVerificationService;
use App\Service\HuggingFaceFaceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/face')]
class FaceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FaceVerificationService $faceService,
        private UserRepository $userRepository,
        private TokenStorageInterface $tokenStorage,
        private HuggingFaceFaceService $hfFaceService,
    ) {
    }

    /**
     * Enroll: send image (base64); backend gets embedding via Hugging Face and stores it.
     */
    #[Route('/enroll', name: 'app_face_enroll', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enroll(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $imageBase64 = $data['image'] ?? null;

        if ($imageBase64 === null || $imageBase64 === '') {
            return new JsonResponse(['success' => false, 'message' => 'Image requise (champ "image" en base64).'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->hfFaceService->isConfigured()) {
            return new JsonResponse(['success' => false, 'message' => 'Reconnaissance faciale non configurée (HUGGINGFACE_API_TOKEN).'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $imageBytes = HuggingFaceFaceService::base64ToImageBytes($imageBase64);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => 'Image invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $embedding = $this->hfFaceService->getEmbeddingFromImage($imageBytes);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur API: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setFaceDescriptor(array_map('floatval', $embedding));
        $this->em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Visage enregistré.']);
    }

    /** Session key set when admin passes face gate for dashboard access. */
    public const FACE_GATE_SESSION_KEY = 'face_gate_passed';

    /**
     * Verify gate: current user (already logged in) sends image; if it matches stored descriptor, set session and allow dashboard.
     */
    #[Route('/verify-gate', name: 'app_face_verify_gate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function verifyGate(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$user->getFaceDescriptor()) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun visage enregistré. Enregistrez votre visage depuis Mon Compte.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $imageBase64 = $data['image'] ?? null;
        if ($imageBase64 === null || $imageBase64 === '') {
            return new JsonResponse(['success' => false, 'message' => 'Image requise.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->hfFaceService->isConfigured()) {
            return new JsonResponse(['success' => false, 'message' => 'Reconnaissance faciale non configurée.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $imageBytes = HuggingFaceFaceService::base64ToImageBytes($imageBase64);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => 'Image invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $embedding = $this->hfFaceService->getEmbeddingFromImage($imageBytes);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur API: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        $stored = $user->getFaceDescriptor();
        if (!$this->faceService->compare($stored, array_map('floatval', $embedding))) {
            return new JsonResponse(['success' => false, 'message' => 'Reconnaissance échouée.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->getSession()->set(self::FACE_GATE_SESSION_KEY, true);
        $redirect = $this->generateUrl('app_dashboard');
        return new JsonResponse(['success' => true, 'redirect' => $redirect]);
    }

    /**
     * Verify: send email + image; backend gets embedding, compares with stored, logs in if match.
     * @deprecated Used only for login-by-face; login now uses password only, face gate is for dashboard.
     */
    #[Route('/verify', name: 'app_face_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $imageBase64 = $data['image'] ?? null;

        if ($email === '' || $imageBase64 === null || $imageBase64 === '') {
            return new JsonResponse(['success' => false, 'message' => 'Email et image requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->hfFaceService->isConfigured()) {
            return new JsonResponse(['success' => false, 'message' => 'Reconnaissance faciale non configurée.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$user->getFaceDescriptor()) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun visage enregistré pour cet email.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $imageBytes = HuggingFaceFaceService::base64ToImageBytes($imageBase64);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => 'Image invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $embedding = $this->hfFaceService->getEmbeddingFromImage($imageBytes);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur API: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        $stored = $user->getFaceDescriptor();
        if (!$this->faceService->compare($stored, array_map('floatval', $embedding))) {
            return new JsonResponse(['success' => false, 'message' => 'Reconnaissance échouée.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));
        $redirect = $this->generateUrl('app_front_mon_compte');
        return new JsonResponse(['success' => true, 'redirect' => $redirect]);
    }

    /**
     * Delete stored face descriptor for current user.
     */
    #[Route('/delete', name: 'app_face_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setFaceDescriptor(null);
        $this->em->flush();
        return new JsonResponse(['success' => true, 'message' => 'Visage supprimé.']);
    }
}
