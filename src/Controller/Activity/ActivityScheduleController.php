<?php

namespace App\Controller\Activity;

use App\Entity\ActivitySchedule;
use App\Form\ActivityScheduleType;
use App\Repository\ActivityScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activity/schedule')]
final class ActivityScheduleController extends AbstractController
{
    #[Route('', name: 'app_activity_schedule_index', methods: ['GET'])]
    public function index(ActivityScheduleRepository $activityScheduleRepository): Response
    {
        return $this->render('ActivityTemplate/activity_schedule/index.html.twig', [
            'activity_schedules' => $activityScheduleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_activity_schedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activitySchedule = new ActivitySchedule();
        $form = $this->createForm(ActivityScheduleType::class, $activitySchedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activitySchedule);
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_schedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ActivityTemplate/activity_schedule/new.html.twig', [
            'activity_schedule' => $activitySchedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_schedule_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(ActivitySchedule $activitySchedule): Response
    {
        return $this->render('ActivityTemplate/activity_schedule/show.html.twig', [
            'activity_schedule' => $activitySchedule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activity_schedule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, ActivitySchedule $activitySchedule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityScheduleType::class, $activitySchedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_schedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ActivityTemplate/activity_schedule/edit.html.twig', [
            'activity_schedule' => $activitySchedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_schedule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, ActivitySchedule $activitySchedule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activitySchedule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($activitySchedule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_schedule_index', [], Response::HTTP_SEE_OTHER);
    }
}
