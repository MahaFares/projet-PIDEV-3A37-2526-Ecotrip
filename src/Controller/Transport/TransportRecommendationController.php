<?php

namespace App\Controller\Transport;

use App\Form\TransportRecommendationType;
use App\Repository\TransportRepository;
use App\Service\AiTransportRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransportRecommendationController extends AbstractController
{
    #[Route('/transport/recommend', name: 'app_transport_recommend', methods: ['GET'])]
    public function recommendForm(Request $request): Response
    {
        $form = $this->createForm(TransportRecommendationType::class, null, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_transport_recommend_results'),
        ]);

        return $this->render('TransportTemplate/recommendation/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/transport/recommend/results', name: 'app_transport_recommend_results', methods: ['POST'])]
    public function recommendResults(
        Request $request,
        TransportRepository $transportRepository,
        AiTransportRecommendationService $aiService
    ): Response {
        $form = $this->createForm(TransportRecommendationType::class, null, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_transport_recommend_results'),
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('TransportTemplate/recommendation/form.html.twig', [
                'form' => $form->createView(),
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        /** @var array<string, mixed> $input */
        $input = $form->getData();
        $budgetMin = isset($input['budgetMin']) && $input['budgetMin'] !== '' ? (float) $input['budgetMin'] : null;
        $budgetMax = isset($input['budgetMax']) && $input['budgetMax'] !== '' ? (float) $input['budgetMax'] : null;
        $input['budgetMin'] = $budgetMin;
        $input['budgetMax'] = $budgetMax;

        if ($budgetMin !== null && $budgetMax !== null && $budgetMin > $budgetMax) {
            $form->get('budgetMax')->addError(new FormError('Budget max must be greater than or equal to budget min.'));

            return $this->render('TransportTemplate/recommendation/form.html.twig', [
                'form' => $form->createView(),
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $availableTransports = $transportRepository->createQueryBuilder('t')
            ->leftJoin('t.category', 'cat')
            ->addSelect('cat')
            ->andWhere('t.disponible = :available')
            ->setParameter('available', true)
            ->getQuery()
            ->getResult();

        $transportPayload = [];
        $transportById = [];

        foreach ($availableTransports as $transport) {
            $id = (int) $transport->getId();
            $transportById[$id] = $transport;
            $transportPayload[] = [
                'id' => $id,
                'type' => (string) ($transport->getType() ?? ''),
                'capacite' => (int) ($transport->getCapacite() ?? 0),
                'emissionco2' => (float) ($transport->getEmissionco2() ?? 0),
                'prixparpersonne' => (float) ($transport->getPrixparpersonne() ?? 0),
                'category' => (string) ($transport->getCategory()?->getName() ?? 'Uncategorized'),
            ];
        }

        $recommendations = [];

        if (!empty($transportPayload)) {
            try {
                $aiRecommendations = $aiService->recommend($input, $transportPayload);

                foreach ($aiRecommendations as $rank => $item) {
                    $transportId = $item['transport_id'];
                    if (!isset($transportById[$transportId])) {
                        continue;
                    }

                    $recommendations[] = [
                        'rank' => $rank + 1,
                        'transport' => $transportById[$transportId],
                        'explanation' => $item['explanation'],
                    ];
                }
            } catch (\Throwable) {
                $this->addFlash('warning', 'AI recommendation service is currently unavailable. Fallback ranking was used.');
            }
        }

        if (count($recommendations) < 3) {
            $passengers = (int) ($input['passengers'] ?? 1);
            $preference = (string) ($input['preference'] ?? 'eco-friendly');
            $comfortLevel = (string) ($input['comfortLevel'] ?? 'standard');

            $fallback = array_filter($transportPayload, static function (array $t) use ($passengers, $budgetMin, $budgetMax): bool {
                if ($t['capacite'] < $passengers) {
                    return false;
                }

                if ($budgetMin !== null && $t['prixparpersonne'] < $budgetMin) {
                    return false;
                }

                if ($budgetMax !== null && $t['prixparpersonne'] > $budgetMax) {
                    return false;
                }

                return true;
            });
            if (empty($fallback)) {
                $fallback = $transportPayload;
            }

            usort($fallback, static function (array $a, array $b) use ($preference, $comfortLevel): int {
                $baseComparison = match ($preference) {
                    'cheapest' => $a['prixparpersonne'] <=> $b['prixparpersonne'],
                    'fastest' => strcmp($a['type'], $b['type']),
                    default => $a['emissionco2'] <=> $b['emissionco2'],
                };

                if ($baseComparison !== 0) {
                    return $baseComparison;
                }

                $comfortScore = static function (array $t): int {
                    $label = strtolower(trim(($t['type'] ?? '').' '.($t['category'] ?? '')));
                    if (str_contains($label, 'vip') || str_contains($label, 'lux') || str_contains($label, 'premium')) {
                        return 3;
                    }
                    if (str_contains($label, 'standard') || str_contains($label, 'comfort')) {
                        return 2;
                    }

                    return 1;
                };

                return match ($comfortLevel) {
                    'premium' => $comfortScore($b) <=> $comfortScore($a),
                    'basic' => $comfortScore($a) <=> $comfortScore($b),
                    default => abs($comfortScore($a) - 2) <=> abs($comfortScore($b) - 2),
                };
            });

            $pickedIds = array_map(static fn (array $r): int => (int) $r['transport']->getId(), $recommendations);

            foreach ($fallback as $candidate) {
                if (count($recommendations) >= 3) {
                    break;
                }

                if (in_array($candidate['id'], $pickedIds, true)) {
                    continue;
                }

                $reason = match ($preference) {
                    'cheapest' => 'Selected as a strong budget option with competitive cost per person.',
                    'fastest' => 'Selected as a likely quick option based on transport type and practical usage.',
                    default => 'Selected for low CO2 emissions, aligned with eco-friendly preference.',
                };

                $recommendations[] = [
                    'rank' => count($recommendations) + 1,
                    'transport' => $transportById[$candidate['id']],
                    'explanation' => $reason,
                ];
                $pickedIds[] = $candidate['id'];
            }
        }

        return $this->render('TransportTemplate/recommendation/results.html.twig', [
            'input' => $input,
            'recommendations' => array_slice($recommendations, 0, 3),
        ]);
    }
}
