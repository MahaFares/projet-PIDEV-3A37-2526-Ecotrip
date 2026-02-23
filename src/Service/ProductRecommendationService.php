<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductRecommendationService
{
    private const MAX_OTHER_PRODUCTS = 25;
    private const RECOMMEND_COUNT = 4;

    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get 3–4 products that "go well with" the given product.
     * Uses Gemini AI when possible; falls back to same-category otherwise.
     *
     * @return Produit[]
     */
    public function getRecommendationsFor(Produit $product): array
    {
        $others = $this->getOtherProductsList($product);
        if (empty($others)) {
            return [];
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->fallbackRecommendations($product);
        }

        $ids = $this->askGeminiForRecommendations($product->getNom(), $others, $apiKey);
        if ($ids !== []) {
            $recommended = $this->produitRepository->findBy(['idProduit' => $ids]);
            // Preserve order from AI
            usort($recommended, fn (Produit $a, Produit $b) => array_search($a->getId(), $ids) <=> array_search($b->getId(), $ids));
            return array_slice($recommended, 0, self::RECOMMEND_COUNT);
        }

        return $this->fallbackRecommendations($product);
    }

    /**
     * @return array<int, string> id => name
     */
    private function getOtherProductsList(Produit $exclude): array
    {
        $all = $this->produitRepository->createQueryBuilder('p')
            ->where('p.idProduit != :id')
            ->setParameter('id', $exclude->getId())
            ->orderBy('p.nom', 'ASC')
            ->setMaxResults(self::MAX_OTHER_PRODUCTS)
            ->getQuery()
            ->getResult();

        $list = [];
        foreach ($all as $p) {
            $list[$p->getId()] = $p->getNom() ?? '';
        }
        return $list;
    }

    /**
     * @param array<int, string> $others id => name
     * @return int[] list of product ids (3–4)
     */
    private function askGeminiForRecommendations(string $currentProductName, array $others, string $apiKey): array
    {
        $listJson = json_encode(
            array_map(fn ($id, $name) => ['id' => $id, 'name' => $name], array_keys($others), array_values($others)),
            JSON_UNESCAPED_UNICODE
        );

        $prompt = <<<PROMPT
Tu es l'assistant de la boutique éco-responsable EcoTrip (Tunisie). On affiche la fiche d'un produit et on veut proposer "Vous aimerez aussi" avec 3 à 4 autres produits qui vont bien avec.

Produit actuel : {$currentProductName}

Liste des autres produits (JSON) avec leur id :
{$listJson}

RÈGLE : Réponds UNIQUEMENT par un tableau JSON d'entiers : les id des 3 ou 4 produits qui vont le mieux avec "{$currentProductName}" (complémentaires pour un voyage éco, même thème, ou souvent achetés ensemble). Exemple : [12, 5, 8, 3]
Ne mets aucun texte avant ni après, uniquement le tableau JSON.
PROMPT;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'query' => ['key' => $apiKey],
                    'json' => [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'maxOutputTokens' => 256,
                        ],
                    ],
                    'timeout' => 15,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('ProductRecommendationService: Gemini non-200', ['status' => $response->getStatusCode()]);
                return [];
            }

            $payload = $response->toArray(false);
            $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = trim($text);
            $text = preg_replace('/^[^\[]*|[^\]]*$/', '', $text);
            $text = trim($text);

            $ids = json_decode($text, true);
            if (!is_array($ids)) {
                return [];
            }
            $ids = array_map('intval', array_values($ids));
            $validIds = array_keys($others);
            $ids = array_values(array_intersect($ids, $validIds));

            return array_slice($ids, 0, self::RECOMMEND_COUNT);
        } catch (\Throwable $e) {
            $this->logger->warning('ProductRecommendationService: Gemini error', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @return Produit[]
     */
    private function fallbackRecommendations(Produit $product): array
    {
        return $this->produitRepository->findRecommendedByCategory($product, self::RECOMMEND_COUNT);
    }
}
