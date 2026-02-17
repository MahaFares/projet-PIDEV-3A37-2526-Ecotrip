<?php

namespace App\Controller\Transport;

use App\Entity\Transport;
use App\Form\TransportType;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trajet')]
final class TrajetController extends AbstractController
{
    #[Route(name: 'app_trajet_index', methods: ['GET'])]
    public function index(TransportRepository $transportRepository): Response
    {
        return $this->render('TransportTemplate/trajet/index.html.twig', [
            'transports' => $transportRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_trajet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transport = new Transport();
        $form = $this->createForm(TransportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($transport);
            $entityManager->flush();

            return $this->redirectToRoute('app_trajet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('TransportTemplate/trajet/new.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trajet_show', methods: ['GET'])]
    public function show(Transport $transport): Response
    {
        return $this->render('TransportTemplate/trajet/show.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trajet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Transport $transport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_trajet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('TransportTemplate/trajet/edit.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trajet_delete', methods: ['POST'])]
    public function delete(Request $request, Transport $transport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transport->getId(), $request->request->get('_token'))) {
            $entityManager->remove($transport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trajet_index', [], Response::HTTP_SEE_OTHER);
    }
}
