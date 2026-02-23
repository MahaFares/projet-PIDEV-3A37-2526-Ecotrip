<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class TranslateController extends AbstractController
{
    #[Route('/translate', name: 'api_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        set_time_limit(30);
        $data = json_decode($request->getContent(), true) ?? [];
        $textOrBatch = $data['text'] ?? '';
        $source = $data['source'] ?? 'fr';
        $target = $data['target'] ?? 'en';

        if ($source === $target) {
            if (is_array($textOrBatch)) {
                return new JsonResponse(['success' => true, 'translations' => $textOrBatch]);
            }
            return new JsonResponse(['success' => true, 'translatedText' => $textOrBatch]);
        }

        // Batch: array of strings
        if (is_array($textOrBatch)) {
            $translations = [];
            foreach ($textOrBatch as $text) {
                $t = is_string($text) ? trim($text) : '';
                if ($t === '') {
                    $translations[] = '';
                    continue;
                }
                $translations[] = $this->callMyMemory($t, $source, $target) ?: $t;
            }
            return new JsonResponse(['success' => true, 'translations' => $translations]);
        }

        // Single string
        $text = is_string($textOrBatch) ? trim($textOrBatch) : '';
        if ($text === '') {
            return new JsonResponse(['success' => true, 'translatedText' => '']);
        }
        $translated = $this->callMyMemory($text, $source, $target);

        return new JsonResponse([
            'success' => true,
            'translatedText' => $translated ?: $text,
        ]);
    }

    private function callMyMemory(string $text, string $source, string $target): ?string
    {
        $langPair = $source . '|' . $target;
        $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
            'q' => $text,
            'langpair' => $langPair,
        ]);

        try {
            $response = file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => 4, 'ignore_errors' => true],
            ]));
        } catch (\Throwable) {
            return null;
        }

        if ($response === false || $response === '') {
            return null;
        }

        // MyMemory returns limit warnings in the response body – do not parse as translation
        if (stripos($response, 'MYMEMORY WARNING') !== false || stripos($response, 'YOU USED ALL AVAILABLE') !== false) {
            return null;
        }

        $json = json_decode($response, true);
        $translated = $json['responseData']['translatedText'] ?? null;

        // If MyMemory returned a limit warning as "translation", return original text and do not expose the warning
        if ($translated && (stripos($translated, 'MYMEMORY WARNING') !== false || stripos($translated, 'YOU USED ALL AVAILABLE') !== false)) {
            return null;
        }

        return $translated ?: null;
    }
}
