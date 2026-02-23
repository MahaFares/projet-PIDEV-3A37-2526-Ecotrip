<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private $httpClient;
    private $apiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire(env: 'GEMINI_API_KEY')] string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function generateText(string $prompt): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Erreur : Impossible de générer le texte.';
        } catch (\Exception $e) {
            return 'Erreur lors de la communication avec l\'IA : ' . $e->getMessage();
        }
    }
}