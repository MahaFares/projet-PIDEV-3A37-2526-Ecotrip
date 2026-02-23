<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiTransportRecommendationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $openAiApiKey,
        private readonly string $openAiModel = 'gpt-4o'
    ) {
    }

    /**
     * @param array<string, mixed> $userInput
     * @param array<int, array<string, mixed>> $availableTransports
     *
     * @return array<int, array{transport_id:int, explanation:string}>
     */
    public function recommend(array $userInput, array $availableTransports): array
    {
        if ($this->openAiApiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $systemPrompt = <<<'PROMPT'
You are an assistant that ranks transport options for eco-booking.
Return ONLY valid JSON with this exact schema:
{
  "recommendations": [
    {
      "transport_id": 123,
      "explanation": "Two short sentences maximum explaining why this option fits the user preference."
    }
  ]
}
Rules:
- Return exactly 3 recommendations.
- Use only transport_id values from the given transport list.
- Rank from best to third best in the returned order.
- Explanations must be max 2 sentences each.
- Consider capacity as a hard constraint: avoid recommending transports with insufficient capacity.
- Respect budget range when provided (budgetMin and budgetMax are per person).
- Consider comfortLevel:
  - basic => prioritize simple and economical options.
  - standard => prioritize balanced options.
  - premium => prioritize higher comfort and quality options.
- Prioritize based on user preference:
  - eco-friendly => lower emissionco2 is best.
  - cheapest => lower prixparpersonne is best.
  - fastest => infer by type/category, favor typically faster options and justify inference.
PROMPT;

        $userPrompt = [
            'user_input' => [
                'origin' => (string) ($userInput['origin'] ?? ''),
                'destination' => (string) ($userInput['destination'] ?? ''),
                'passengers' => (int) ($userInput['passengers'] ?? 1),
                'preference' => (string) ($userInput['preference'] ?? 'eco-friendly'),
                'budgetMin' => isset($userInput['budgetMin']) ? (float) $userInput['budgetMin'] : null,
                'budgetMax' => isset($userInput['budgetMax']) ? (float) $userInput['budgetMax'] : null,
                'comfortLevel' => (string) ($userInput['comfortLevel'] ?? 'standard'),
            ],
            'available_transports' => $availableTransports,
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($userPrompt, JSON_THROW_ON_ERROR)],
                    ],
                ],
                'timeout' => 45,
            ]);

            $payload = $response->toArray(false);
            $content = $payload['choices'][0]['message']['content'] ?? null;

            if (!is_string($content) || trim($content) === '') {
                throw new \RuntimeException('OpenAI returned an empty response.');
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $recommendations = $decoded['recommendations'] ?? null;

            if (!is_array($recommendations)) {
                throw new \RuntimeException('OpenAI response JSON does not contain recommendations array.');
            }

            $normalized = [];
            foreach ($recommendations as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $transportId = isset($item['transport_id']) ? (int) $item['transport_id'] : 0;
                $explanation = trim((string) ($item['explanation'] ?? ''));

                if ($transportId > 0 && $explanation !== '') {
                    $normalized[] = [
                        'transport_id' => $transportId,
                        'explanation' => $explanation,
                    ];
                }
            }

            return array_slice($normalized, 0, 3);
        } catch (ExceptionInterface | \JsonException | \Throwable $e) {
            $this->logger->error('AI transport recommendation failed', [
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Unable to fetch AI transport recommendations right now.', 0, $e);
        }
    }
}
