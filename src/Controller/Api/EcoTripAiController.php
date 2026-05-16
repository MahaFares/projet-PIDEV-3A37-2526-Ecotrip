<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class EcoTripAiController extends AbstractController
{
    #[Route('/api/ecotrip-ai', name: 'api_ecotrip_ai_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return new JsonResponse(['error' => "Dites-moi ce que vous recherchez pour votre voyage éco-responsable en Tunisie ! (ex: activités à Tunis, hébergement à Djerba, transport pour le désert)"], 400);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            return new JsonResponse([
                'error' => "Le service EcoTrip AI n'est pas encore configuré (Clé API manquante).",
            ], 500);
        }

        $systemInstruction = "Tu es EcoTrip AI, l'assistant virtuel expert en tourisme éco-responsable en Tunisie pour la plateforme EcoTrip.\n\n" .
            "IMPORTANT: Tu dois répondre UNIQUEMENT avec un objet JSON valide. Pas de texte avant, pas de texte après, pas de markdown, juste le JSON pur.\n\n" .
            "Structure JSON OBLIGATOIRE:\n" .
            "{\n" .
            "  \"intro\": \"Message d'accueil dynamique et motivant (1-2 phrases courtes, ton amical et éco-friendly)\",\n" .
            "  \"recommendations\": [\n" .
            "    {\n" .
            "      \"type\": \"activity|hebergement|transport|product\",\n" .
            "      \"title\": \"Titre attractif du service\",\n" .
            "      \"description\": \"Description détaillée en 2-3 phrases (éco-responsable, local)\",\n" .
            "      \"image\": \"URL complète d'image (utilise des URLs Cloudinary réalistes comme https://res.cloudinary.com/dofap5wt0/image/upload/v1/activities/trekking-tunisie.jpg ou placeholders Unsplash/Tunisia-themed)\",\n" .
            "      \"details\": {\n" .
            "        \"price\": 45.5,\n" .
            "        \"duration\": \"2h30\",\n" .
            "        \"location\": \"Tunis / Ichkeul / Sahara\",\n" .
            "        \"capacity\": 8\n" .
            "      }\n" .
            "    }\n" .
            "  ],\n" .
            "  \"schedule\": [\n" .
            "    {\n" .
            "      \"time\": \"09:00\",\n" .
            "      \"activity\": \"Visite guidée du parc national Ichkeul (observation oiseaux)\"\n" .
            "    }\n" .
            "  ],\n" .
            "  \"next_steps\": \"Que veux-tu réserver ensuite ? (ex: transport, hébergement)\"\n" .
            "}\n\n" .
            "RÈGLES STRICTES:\n" .
            "- Retourne 2-4 recommandations maximum basées sur la requête de l'utilisateur.\n" .
            "- Types : activity (randonnée, safari, diving), hebergement (hôtel éco, gîte), transport (voiture avec chauffeur, bus éco), product (souvenirs artisanaux, équipements éco).\n" .
            "- Utilise des vraies destinations tunisiennes : Tunis, Djerba, Sahara (Douz), Ichkeul, Tabarka, Kairouan, Carthage, Jebel Serj, etc.\n" .
            "- Ton : Dynamique, éco-responsable, local, enthousiaste. Mentionne 'EcoTrip' et la durabilité.\n" .
            "- Images : URLs réalistes (Cloudinary style ou https://picsum.photos/id/xx/800/600 avec thèmes Tunisie).\n" .
            "- Schedule : Propose un planning simple et réaliste (matin/après-midi/soir).\n" .
            "- Si pas lié au tourisme : {\"error\": \"Je suis expert en voyages éco en Tunisie uniquement !\"}\n" .
            "- AUCUN texte en dehors du JSON pur.\n" .
            "- Exemples de requêtes : 'activités à Tunis', 'hébergement Djerba', 'transport désert', 'plan journée à Carthage'.";

        try {
            $response = $httpClient->request(
                'POST',
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'query' => [
                        'key' => $apiKey,
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $systemInstruction . "\n\nQuestion de l'utilisateur: " . $message . "\n\nRéponds UNIQUEMENT avec le JSON ci-dessus :"
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.75,
                            'topK' => 40,
                            'topP' => 0.95,
                        ],
                    ],
                    'timeout' => 60,
                    'max_duration' => 60,
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429) {
                return new JsonResponse([
                    'error' => "Trop de requêtes EcoTrip AI. Respirez l'air frais tunisien et réessayez dans 10 secondes !",
                ], 200);
            }

            if ($statusCode !== 200) {
                $logger->error('Gemini API returned non-200 status', [
                    'status' => $statusCode,
                ]);
                return new JsonResponse([
                    'error' => "Connexion avec EcoTrip AI interrompue (Code: {$statusCode}).",
                ], 200);
            }

            $payload = $response->toArray(false);

            if (isset($payload['error'])) {
                $errorMessage = $payload['error']['message'] ?? 'Unknown error';
                $logger->error('Gemini API error', ['error' => $payload['error']]);
                return new JsonResponse([
                    'error' => "Erreur du service IA : " . $errorMessage,
                ], 200);
            }

            $answer = null;

            if (isset($payload['candidates'][0]['content']['parts'])) {
                $parts = $payload['candidates'][0]['content']['parts'];

                if (is_array($parts)) {
                    $texts = [];
                    foreach ($parts as $part) {
                        if (isset($part['text']) && is_string($part['text'])) {
                            $texts[] = trim($part['text']);
                        }
                    }

                    if (!empty($texts)) {
                        $answer = implode("\n", $texts);
                    }
                }
            }

            if (!$answer) {
                return new JsonResponse([
                    'error' => "Désolé, mes circuits éco n'ont pas pu générer de recommandation. Réessayez !",
                ], 200);
            }

            // Aggressive JSON cleaning (same as original)
            $answer = preg_replace('/^[^{]*/', '', $answer);
            $answer = preg_replace('/[^}]*$/', '', $answer);
            $answer = preg_replace('/```json\s*|\s*```/', '', $answer);
            $answer = trim($answer);

            $logger->info('Cleaned EcoTrip AI Response', ['response' => substr($answer, 0, 500)]);

            // Try to decode the JSON
            $decodedAnswer = json_decode($answer, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAnswer)) {
                return new JsonResponse($decodedAnswer);
            } else {
                $logger->warning('Failed to parse JSON, returning as plain text', [
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($answer, 0, 200)
                ]);

                return new JsonResponse([
                    'answer' => $answer
                ], 200);
            }

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $logger->error('Transport error (timeout or network)', [
                'message' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'error' => "Le service met trop de temps à répondre. Le voyage virtuel a pris trop de temps !",
            ], 200);

        } catch (\Throwable $e) {
            $logger->error('EcoTrip AI Error', ['message' => $e->getMessage()]);
            return new JsonResponse([
                'error' => "Une erreur système est survenue. Le voyage éco a rencontré un imprévu !",
            ], 200);
        }
    }
}