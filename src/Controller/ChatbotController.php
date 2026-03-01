<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Service\ChatbotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    // ==========================================
    // API CHATBOT (Pour le widget frontend)
    // ==========================================

    /**
     * API pour envoyer un message au chatbot
     */
    #[Route('/api/chatbot/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        ChatbotService $chatbotService,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        // Récupérer le message
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message vide'
            ], 400);
        }

        // Gérer la session
        $sessionId = $session->get('chatbot_session_id');
        if (!$sessionId) {
            $sessionId = uniqid('chat_', true);
            $session->set('chatbot_session_id', $sessionId);
        }

        // Récupérer l'historique (derniers 5 messages)
        $conversations = $em->getRepository(Conversation::class)
            ->findBy(
                ['sessionId' => $sessionId],
                ['dateMessage' => 'ASC'],
                5
            );

        $historique = array_map(function($conv) {
            return [
                'user' => $conv->getMessageUser(),
                'bot' => $conv->getReponseBot()
            ];
        }, $conversations);

        try {
            // Appeler le chatbot
            $result = $chatbotService->repondreQuestion($message, $historique);

            if ($result['success']) {
                // Sauvegarder dans la base
                $conversation = new Conversation();
                $conversation->setSessionId($sessionId);
                $conversation->setMessageUser($message);
                $conversation->setReponseBot($result['response']);

                // Si l'utilisateur est connecté
                if ($this->getUser()) {
                    $conversation->setUser($this->getUser());
                }

                $em->persist($conversation);
                $em->flush();

                return new JsonResponse([
                    'success' => true,
                    'response' => $result['response'],
                    'provider' => $result['provider']
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'response' => $result['response'],
                    'error' => $result['error'] ?? null
                ], 500);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur serveur',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'historique de conversation
     */
    #[Route('/api/chatbot/history', name: 'api_chatbot_history', methods: ['GET'])]
    public function getHistory(
        SessionInterface $session,
        EntityManagerInterface $em
    ): JsonResponse {
        $sessionId = $session->get('chatbot_session_id');

        if (!$sessionId) {
            return new JsonResponse([
                'success' => true,
                'history' => []
            ]);
        }

        $conversations = $em->getRepository(Conversation::class)
            ->findBy(
                ['sessionId' => $sessionId],
                ['dateMessage' => 'ASC']
            );

        $history = array_map(function($conv) {
            return [
                'user' => $conv->getMessageUser(),
                'bot' => $conv->getReponseBot(),
                'date' => $conv->getDateMessage()->format('Y-m-d H:i:s')
            ];
        }, $conversations);

        return new JsonResponse([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Réinitialiser la conversation
     */
    #[Route('/api/chatbot/reset', name: 'api_chatbot_reset', methods: ['POST'])]
    public function resetConversation(SessionInterface $session): JsonResponse
    {
        $session->remove('chatbot_session_id');

        return new JsonResponse([
            'success' => true,
            'message' => 'Conversation réinitialisée'
        ]);
    }

    /**
     * Obtenir le provider actif
     */
    #[Route('/api/chatbot/provider', name: 'api_chatbot_provider', methods: ['GET'])]
    public function getProvider(ChatbotService $chatbotService): JsonResponse
    {
        return new JsonResponse([
            'provider' => $chatbotService->getProvider()
        ]);
    }

    // ==========================================
    // PAGES ADMIN (BackOffice)
    // ==========================================

    /**
     * Dashboard admin - Statistiques conversations
     */
    #[Route('/admin/chatbot', name: 'admin_chatbot_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Statistiques globales
        $totalConversations = $em->getRepository(Conversation::class)->count([]);
        
        // Conversations aujourd'hui
        $today = new \DateTime('today');
        $conversationsToday = $em->getRepository(Conversation::class)
            ->createQueryBuilder('c')
            ->where('c.dateMessage >= :today')
            ->setParameter('today', $today)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Sessions uniques
        $sessionsUniques = $em->getRepository(Conversation::class)
            ->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.sessionId)')
            ->getQuery()
            ->getSingleScalarResult();

        // Dernières conversations
        $dernieresConversations = $em->getRepository(Conversation::class)
            ->findBy([], ['dateMessage' => 'DESC'], 20);

        return $this->render('HebergementTemplate/chatbot/admin_dashboard.html.twig', [
            'totalConversations' => $totalConversations,
            'conversationsToday' => $conversationsToday,
            'sessionsUniques' => $sessionsUniques,
            'conversations' => $dernieresConversations
        ]);
    }

    /**
     * Voir les détails d'une conversation
     */
    #[Route('/admin/chatbot/conversation/{sessionId}', name: 'admin_chatbot_conversation')]
    public function viewConversation(
        string $sessionId,
        EntityManagerInterface $em
    ): Response {
        $conversations = $em->getRepository(Conversation::class)
            ->findBy(
                ['sessionId' => $sessionId],
                ['dateMessage' => 'ASC']
            );

        if (empty($conversations)) {
            throw $this->createNotFoundException('Conversation introuvable');
        }

        return $this->render('HebergementTemplate/chatbot/admin_conversation.html.twig', [
            'sessionId' => $sessionId,
            'conversations' => $conversations
        ]);
    }

    /**
     * Supprimer une conversation
     */
    #[Route('/admin/chatbot/conversation/{sessionId}/delete', name: 'admin_chatbot_delete', methods: ['POST'])]
    public function deleteConversation(
        string $sessionId,
        EntityManagerInterface $em
    ): Response {
        $conversations = $em->getRepository(Conversation::class)
            ->findBy(['sessionId' => $sessionId]);

        foreach ($conversations as $conv) {
            $em->remove($conv);
        }

        $em->flush();

        $this->addFlash('success', 'Conversation supprimée avec succès');

        return $this->redirectToRoute('admin_chatbot_dashboard');
    }
}