<?php

namespace App\Controller\Hebergement;

use App\Entity\CategorieHebergement;
use App\Form\CategorieHebergementType;
use App\Repository\CategorieHebergementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categorie_hebergement')]
final class CategorieHebergementController extends AbstractController
{
    #[Route('/', name: 'app_categorie_hebergement_index', methods: ['GET'])]
    public function index(Request $request, CategorieHebergementRepository $categorieHebergementRepository): Response
    {
        $searchQuery = $request->query->get('q', '');

        // Filter categories based on search
        if (!empty($searchQuery)) {
            $categories = $categorieHebergementRepository->findBySearchQuery($searchQuery);
        } else {
            $categories = $categorieHebergementRepository->findAll();
        }

        // If AJAX request, return only the table rows partial
        if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
            return $this->render('HebergementTemplate/categorie_hebergement/_table.html.twig', [
                'categorie_hebergements' => $categories,
            ]);
        }

        // Regular request, return full page
        return $this->render('HebergementTemplate/categorie_hebergement/index.html.twig', [
            'categorie_hebergements' => $categories,
            'filters' => ['q' => $searchQuery],
        ]);
    }

    #[Route('/new', name: 'app_categorie_hebergement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorieHebergement = new CategorieHebergement();
        $form = $this->createForm(CategorieHebergementType::class, $categorieHebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorieHebergement);
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('HebergementTemplate/categorie_hebergement/new.html.twig', [
            'categorie_hebergement' => $categorieHebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_hebergement_show', methods: ['GET'])]
    public function show(CategorieHebergement $categorieHebergement): Response
    {
        return $this->render('HebergementTemplate/categorie_hebergement/show.html.twig', [
            'categorie_hebergement' => $categorieHebergement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_hebergement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieHebergement $categorieHebergement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieHebergementType::class, $categorieHebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('HebergementTemplate/categorie_hebergement/edit.html.twig', [
            'categorie_hebergement' => $categorieHebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_hebergement_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieHebergement $categorieHebergement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieHebergement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($categorieHebergement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_categorie_hebergement_index', [], Response::HTTP_SEE_OTHER);
    }
}