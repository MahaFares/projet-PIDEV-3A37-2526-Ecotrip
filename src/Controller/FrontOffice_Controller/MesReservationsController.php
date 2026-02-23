<?php

namespace App\Controller\FrontOffice_Controller;

use App\Entity\Enum\ReservationType;
use App\Repository\HebergementRepository;
use App\Repository\ActivityRepository;
use App\Repository\TransportRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

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
}
