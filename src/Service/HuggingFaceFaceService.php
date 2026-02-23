<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Get face (image) embedding via Hugging Face Inference API.
 * Frontend sends image; we call HF and get back a vector to store/compare.
 */
final class HuggingFaceFaceService
{
    /** New router format: /hf-inference/models/{model}/pipeline/{task} */
    private const API_URL = 'https://router.huggingface.co/hf-inference/models/%s/pipeline/%s';

    /**
     * Model for image embeddings.
     * google/vit-base-patch16-224 supports image-feature-extraction and returns a 768-dim vector.
     */
    private const DEFAULT_MODEL = 'google/vit-base-patch16-224';
    private const DEFAULT_TASK  = 'image-feature-extraction';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?string             $apiToken = null,
        private string              $modelId  = self::DEFAULT_MODEL,
        private string              $task     = self::DEFAULT_TASK,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiToken !== null && $this->apiToken !== '';
    }

    /**
     * Get embedding vector from raw image bytes.
     *
     * @param  string        $imageBytes  Raw image content (JPEG, PNG, WEBP…)
     * @return array<float>               Embedding vector (768-dim for ViT-base)
     * @throws \RuntimeException
     */
    public function getEmbeddingFromImage(string $imageBytes): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'Hugging Face API token not configured. Set HUGGINGFACE_API_TOKEN in .env'
            );
        }

        // ✅ Detect MIME type dynamically (JPEG, PNG, WEBP, GIF…)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageBytes) ?: 'image/jpeg';

        // ✅ Build a proper data URL — NOT raw base64
        $dataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageBytes);

        $url = sprintf(self::API_URL, $this->modelId, $this->task);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'inputs' => $dataUrl,
                ],
                'timeout' => 90,
                'max_duration' => 95,
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'timeout') !== false || stripos($msg, 'idle') !== false) {
                throw new \RuntimeException(
                    'Le serveur Hugging Face met trop de temps à répondre (timeout). Réessayez dans quelques instants, ou vérifiez votre connexion.'
                );
            }
            throw new \RuntimeException('Erreur réseau Hugging Face: ' . $msg);
        }

        $status = $response->getStatusCode();
        $body   = $response->toArray(false);

        if ($status !== 200) {
            $msg = $body['error'] ?? $body['message'] ?? $response->getContent(false);
            throw new \RuntimeException(
                'Hugging Face API error: ' . (is_string($msg) ? $msg : json_encode($msg))
            );
        }

        return $this->parseEmbedding($body);
    }

    /**
     * Get embedding vector from a public image URL.
     *
     * @param  string        $imageUrl  Publicly accessible image URL
     * @return array<float>
     * @throws \RuntimeException
     */
    public function getEmbeddingFromUrl(string $imageUrl): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'Hugging Face API token not configured. Set HUGGINGFACE_API_TOKEN in .env'
            );
        }

        $url = sprintf(self::API_URL, $this->modelId, $this->task);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'inputs' => $imageUrl,
                ],
                'timeout' => 90,
                'max_duration' => 95,
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'timeout') !== false || stripos($msg, 'idle') !== false) {
                throw new \RuntimeException(
                    'Le serveur Hugging Face met trop de temps à répondre (timeout). Réessayez dans quelques instants, ou vérifiez votre connexion.'
                );
            }
            throw new \RuntimeException('Erreur réseau Hugging Face: ' . $msg);
        }

        $status = $response->getStatusCode();
        $body   = $response->toArray(false);

        if ($status !== 200) {
            $msg = $body['error'] ?? $body['message'] ?? $response->getContent(false);
            throw new \RuntimeException(
                'Hugging Face API error: ' . (is_string($msg) ? $msg : json_encode($msg))
            );
        }

        return $this->parseEmbedding($body);
    }

    /**
     * Decode a base64 data URL or raw base64 string to raw image bytes.
     *
     * @param  string $input  e.g. "data:image/jpeg;base64,/9j/4AAQ…" or raw base64
     * @return string         Raw binary image bytes
     * @throws \InvalidArgumentException
     */
    public static function base64ToImageBytes(string $input): string
    {
        if (str_starts_with($input, 'data:')) {
            $input = preg_replace('#^data:image/\w+;base64,#i', '', $input) ?? '';
        }

        $decoded = base64_decode($input, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 image data.');
        }

        return $decoded;
    }

    /**
     * Cosine similarity between two embedding vectors (range −1 … 1).
     * Values above ~0.85 typically indicate the same face.
     *
     * @param  array<float> $a
     * @param  array<float> $b
     * @return float
     * @throws \InvalidArgumentException
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException(
                sprintf('Vector length mismatch: %d vs %d', count($a), count($b))
            );
        }

        $dot  = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $valA) {
            $valB  = $b[$i];
            $dot  += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        $denom = sqrt($normA) * sqrt($normB);
        if ($denom === 0.0) {
            return 0.0;
        }

        return $dot / $denom;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the various response shapes returned by HF image-feature-extraction.
     *
     * Known shapes:
     *   • [[float, float, …]]          — ViT (most common): array of one array
     *   • [float, float, …]            — flat array of floats
     *   • {"embedding": [float, …]}    — some custom models
     *   • {"image_embeds": [float, …]} — CLIP-style models
     *
     * @param  mixed         $body
     * @return array<float>
     * @throws \RuntimeException
     */
    private function parseEmbedding(mixed $body): array
    {
        // {"embedding": [...]}
        if (isset($body['embedding']) && is_array($body['embedding'])) {
            return array_map('floatval', $body['embedding']);
        }

        // {"image_embeds": [...]} or {"image_embeds": [[...]]}
        if (isset($body['image_embeds']) && is_array($body['image_embeds'])) {
            $emb = $body['image_embeds'];
            return array_map('floatval', is_array($emb[0] ?? null) ? $emb[0] : $emb);
        }

        // [[float, float, …]]  ← ViT default
        if (is_array($body) && isset($body[0]) && is_array($body[0])) {
            // Some models return [[[float,…]]] (3 levels) — flatten one more level
            $inner = $body[0];
            if (isset($inner[0]) && is_array($inner[0])) {
                $inner = $inner[0];
            }
            return array_map('floatval', $inner);
        }

        // [float, float, …]  ← flat
        if (is_array($body) && isset($body[0]) && is_numeric($body[0])) {
            return array_map('floatval', $body);
        }

        throw new \RuntimeException(
            'Unexpected Hugging Face API response format: ' . json_encode($body)
        );
    }
}