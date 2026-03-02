<?php

namespace App\Controller\Reservation;

use App\Enum\ReservationType;
use App\Entity\Reservation;
use App\Form\ReservationEditType;
use App\Repository\HebergementRepository;
use App\Repository\ActivityRepository;
use App\Repository\TransportRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

#[IsGranted('ROLE_USER')]
#[Route('/mes-reservations')]
class MesReservationsController extends AbstractController
{
    #[Route('', name: 'app_mes_reservations', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepo,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $reservations = $reservationRepo->findByUser($user->getId());
        $items = [];

        foreach ($reservations as $r) {
            $label = '';
            $typeLabel = $r->getReservationType()?->label() ?? '-';
            switch ($r->getReservationType()) {
                case ReservationType::HEBERGEMENT:
                    $entity = $hebergementRepo->find($r->getReservationId());
                    $label = $entity ? $entity->getNom() : 'Hébergement #' . $r->getReservationId();
                    break;
                case ReservationType::ACTIVITY:
                    $entity = $activityRepo->find($r->getReservationId());
                    $label = $entity ? $entity->getTitle() : 'Activité #' . $r->getReservationId();
                    break;
                case ReservationType::TRANSPORT:
                    $entity = $transportRepo->find($r->getReservationId());
                    $label = $entity ? $entity->getType() : 'Transport #' . $r->getReservationId();
                    break;
                default:
                    $label = 'Réservation #' . $r->getReservationId();
            }
            $items[] = [
                'reservation' => $r,
                'label' => $label,
                'typeLabel' => $typeLabel,
            ];
        }

        return $this->render('FrontOffice/mes_reservations/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mes_reservations_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        ReservationRepository $reservationRepo,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $reservationRepo->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Réservation introuvable ou accès non autorisé.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        $form = $this->createForm(ReservationEditType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTotal = $this->recalculateTotalPrice($reservation, $hebergementRepo, $activityRepo, $transportRepo);
            if ($newTotal !== null) {
                $reservation->setTotalPrice($newTotal);
            }
            $reservation->setUpdatedAt(new \DateTimeImmutable());
            $reservationRepo->save($reservation);
            $this->addFlash('success', 'Réservation mise à jour.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        $label = $this->resolveLabel($reservation, $hebergementRepo, $activityRepo, $transportRepo);

        return $this->render('FrontOffice/mes_reservations/edit.html.twig', [
            'reservation' => $reservation,
            'label' => $label,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_mes_reservations_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id, ReservationRepository $reservationRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $reservationRepo->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Réservation introuvable ou accès non autorisé.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_reservation_' . $id, $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        $reservationRepo->remove($reservation);
        $this->addFlash('success', 'Réservation supprimée.');

        return $this->redirectToRoute('app_mes_reservations');
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

    /**
     * Recalculate total price after edit based on type: transport/activity = unit price × persons; hebergement = price per night × nights.
     */
    private function recalculateTotalPrice(
        Reservation $reservation,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): ?float {
        $persons = max(1, $reservation->getNumberOfPersons() ?? 1);
        switch ($reservation->getReservationType()) {
            case ReservationType::TRANSPORT:
                $transport = $transportRepo->find($reservation->getReservationId());
                if (!$transport || !$transport->getPrixparpersonne()) {
                    return null;
                }
                return (float) $transport->getPrixparpersonne() * $persons;
            case ReservationType::ACTIVITY:
                $activity = $activityRepo->find($reservation->getReservationId());
                if (!$activity || $activity->getPrice() === null) {
                    return null;
                }
                return (float) $activity->getPrice() * $persons;
            case ReservationType::HEBERGEMENT:
                $details = $reservation->getDetails() ?? [];
                $oldNights = (int) ($details['nights'] ?? 1);
                if ($oldNights < 1) {
                    $oldNights = 1;
                }
                $pricePerNight = $reservation->getTotalPrice() / $oldNights;
                $dateFrom = $reservation->getDateFrom();
                $dateTo = $reservation->getDateTo();
                $nights = 1;
                if ($dateFrom && $dateTo && $dateTo > $dateFrom) {
                    $nights = (int) $dateFrom->diff($dateTo)->days;
                    if ($nights < 1) {
                        $nights = 1;
                    }
                }
                $reservation->setDetails(array_merge($details, ['nights' => $nights]));
                return $pricePerNight * $nights;
            default:
                return null;
        }
    }
}
