<?php

namespace App\Controller\Transport;

use App\Entity\TransportCategory;
use App\Form\TransportCategoryType;
use App\Repository\TransportCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/transport/category')]
final class TransportCategoryController extends AbstractController
{
    #[Route('', name: 'app_transport_category_index', methods: ['GET'])]
    public function index(Request $request, TransportCategoryRepository $repo): Response
    {
        // Read search term from URL (?q=...)
        $q = $request->query->get('q', '');

        // Use the search method if q is present, otherwise all sorted
        if ($q) {
            $categories = $repo->search($q);
        } else {
            $categories = $repo->findBy([], ['name' => 'ASC']);
        }

        // Statistiques (même style que transports)
        $stats = [
            'total'       => count($categories),
            'top'         => 0,
            'chartLabels' => [],
            'chartData'   => [],
        ];

        $transportCounts = [];
        foreach ($categories as $category) {
            $count = count($category->getTransports());
            $transportCounts[$category->getName() ?: 'Sans nom'] = $count;

            if ($count > 0) {
                $stats['top']++;
            }
        }

        arsort($transportCounts);
        $top5 = array_slice($transportCounts, 0, 5, true);

        $stats['chartLabels'] = array_keys($top5);
        $stats['chartData']   = array_values($top5);

        return $this->render('TransportTemplate/transport_category/index.html.twig', [
            'categories' => $categories,
            'stats'      => $stats,
            'q'          => $q,               // ← we pass q now!
        ]);
    }
    #[Route('/ajax', name: 'app_transport_category_ajax', methods: ['GET'])]
    public function ajax(Request $request, TransportCategoryRepository $repo): Response
    {
        $q = $request->query->get('q', '');
        $categories = $repo->search($q);

        return $this->render('TransportTemplate/transport_category/_category_table.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_transport_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new TransportCategory();
        $form = $this->createForm(TransportCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée.');
            return $this->redirectToRoute('app_transport_category_index');
        }

        return $this->render('TransportTemplate/transport_category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_category_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(TransportCategory $category): Response
    {
        return $this->render('TransportTemplate/transport_category/show.html.twig', ['category' => $category]);
    }

    #[Route('/{id}/edit', name: 'app_transport_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, TransportCategory $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TransportCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie mise à jour.');
            return $this->redirectToRoute('app_transport_category_index');
        }

        return $this->render('TransportTemplate/transport_category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_category_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, TransportCategory $category, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée.');
        }
        return $this->redirectToRoute('app_transport_category_index');
    }
}
