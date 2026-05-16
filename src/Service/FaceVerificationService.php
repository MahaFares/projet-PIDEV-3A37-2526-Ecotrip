<?php

namespace App\Service;

/**
 * Compares two face/image embedding vectors.
 * Supports 128-dim (Face-API.js) and variable length (e.g. 512 from Hugging Face CLIP).
 * Uses cosine similarity for variable length; Euclidean distance for 128-dim (backward compat).
 */
final class FaceVerificationService
{
    /** Euclidean distance below this = same person (128-dim Face-API.js). */
    private const EUCLIDEAN_THRESHOLD_128 = 0.6;

    /** Cosine similarity above this = same person (e.g. CLIP embeddings). */
    private const COSINE_THRESHOLD = 0.5;

    public function compare(array $a, array $b): bool
    {
        $n = count($a);
        if ($n !== count($b) || $n === 0) {
            return false;
        }
        if ($n === 128) {
            return $this->euclideanDistance($a, $b) < self::EUCLIDEAN_THRESHOLD_128;
        }
        return $this->cosineSimilarity($a, $b) >= self::COSINE_THRESHOLD;
    }

    public function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        $norm = sqrt($normA) * sqrt($normB);
        return $norm <= 0 ? 0.0 : $dot / $norm;
    }
}
