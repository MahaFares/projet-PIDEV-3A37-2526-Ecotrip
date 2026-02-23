<?php

namespace App\Controller\BackOffice_Controller;

use App\Entity\Enum\ReservationStatus;
use App\Entity\Enum\ReservationType;
use App\Entity\Reservation;
use App\Repository\ActivityRepository;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use App\Repository\TransportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'app_reservation_index', methods: ['GET'])]
    public function index(
        Request $request,
        ReservationRepository $reservationRepo,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): Response {
        $statusFilter = $request->query->get('status');
        $typeFilter = $request->query->get('type');
        $userSearch = $request->query->get('user');
        $dateFromStr = $request->query->get('date_from');
        $dateToStr = $request->query->get('date_to');

        $status = null;
        if ($statusFilter !== null && $statusFilter !== '') {
            $status = ReservationStatus::tryFrom($statusFilter) ?? null;
        }
        $type = null;
        if ($typeFilter !== null && $typeFilter !== '') {
            $type = ReservationType::tryFrom($typeFilter) ?? null;
        }
        $dateFrom = null;
        if ($dateFromStr !== null && $dateFromStr !== '') {
            try {
                $dateFrom = new \DateTimeImmutable($dateFromStr);
            } catch (\Throwable $e) {
            }
        }
        $dateTo = null;
        if ($dateToStr !== null && $dateToStr !== '') {
            try {
                $dateTo = new \DateTimeImmutable($dateToStr);
            } catch (\Throwable $e) {
            }
        }

        $reservations = $reservationRepo->findWithFilters($status, $type, $userSearch ?: null, $dateFrom, $dateTo);
        $stats = $reservationRepo->getStatsForAdmin();

        $items = [];
        foreach ($reservations as $r) {
            $label = $this->resolveLabel($r, $hebergementRepo, $activityRepo, $transportRepo);
            $items[] = [
                'reservation' => $r,
                'label' => $label,
                'typeLabel' => $r->getReservationType()?->label() ?? '-',
            ];
        }

        $filters = [
            'status' => $statusFilter,
            'type' => $typeFilter,
            'user' => $userSearch,
            'date_from' => $dateFromStr,
            'date_to' => $dateToStr,
        ];

        if ($request->isXmlHttpRequest() || $request->query->get('partial')) {
            $html = $this->renderView('BackOffice/reservation/_table_rows.html.twig', ['items' => $items]);
            return new JsonResponse([
                'stats' => $stats,
                'html' => $html,
            ]);
        }

        return $this->render('BackOffice/reservation/index.html.twig', [
            'items' => $items,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Reservation $reservation,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): Response {
        $label = $this->resolveLabel($reservation, $hebergementRepo, $activityRepo, $transportRepo);
        return $this->render('BackOffice/reservation/show.html.twig', [
            'reservation' => $reservation,
            'label' => $label,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Reservation $reservation, ReservationRepository $repo): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reservation_delete_' . $reservation->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_reservation_index');
        }
        $repo->remove($reservation);
        $this->addFlash('success', 'Réservation supprimée.');
        return $this->redirectToRoute('app_reservation_index');
    }

    private function resolveLabel(
        Reservation $r,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): string {
        switch ($r->getReservationType()) {
            case ReservationType::HEBERGEMENT:
                $entity = $hebergementRepo->find($r->getReservationId());
                return $entity ? $entity->getNom() : 'Hébergement #' . $r->getReservationId();
            case ReservationType::ACTIVITY:
                $entity = $activityRepo->find($r->getReservationId());
                return $entity ? $entity->getTitle() : 'Activité #' . $r->getReservationId();
            case ReservationType::TRANSPORT:
                $entity = $transportRepo->find($r->getReservationId());
                return $entity ? $entity->getType() : 'Transport #' . $r->getReservationId();
            default:
                return 'Réservation #' . $r->getReservationId();
        }
    }
}
