<?php

namespace App\Controller\Hebergement;

use App\Entity\Hebergement;
use App\Form\HebergementType;
use App\Repository\HebergementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/hebergement')]
final class HebergementController extends AbstractController
{
    #[Route('', name: 'app_hebergement_index', methods: ['GET'])]
    public function index(Request $request, HebergementRepository $hebergementRepository): Response
    {
        $q = $request->query->get('q');
        $minStars = $request->query->get('minStars');
        $maxStars = $request->query->get('maxStars');
        $active = $request->query->get('active');

        $minStars = $minStars !== null && $minStars !== '' ? (int) $minStars : null;
        $maxStars = $maxStars !== null && $maxStars !== '' ? (int) $maxStars : null;
        $active = ($active === '1' ? true : ($active === '0' ? false : null));

        $hebergements = $hebergementRepository->findByFilters($q, $minStars, $maxStars, $active);

        return $this->render('HebergementTemplate/hebergement/index.html.twig', [
            'hebergements' => $hebergements,
            'filters' => [
                'q' => $q,
                'minStars' => $minStars,
                'maxStars' => $maxStars,
                'active' => $active,
            ],
        ]);
    }

    #[Route('/new', name: 'app_hebergement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $hebergement = new Hebergement();
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // imagePrincipale field stores either URL or filename
            $hebergement->setActif(true);
            $entityManager->persist($hebergement);
            $entityManager->flush();

            return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('HebergementTemplate/hebergement/new.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_hebergement_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Hebergement $hebergement): Response
    {
        return $this->render('HebergementTemplate/hebergement/show.html.twig', [
            'hebergement' => $hebergement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_hebergement_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // imagePrincipale field stores either URL or filename
            $entityManager->flush();

            return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('HebergementTemplate/hebergement/edit.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_hebergement_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hebergement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($hebergement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
    }
}
