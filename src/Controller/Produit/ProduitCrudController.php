<?php

namespace App\Controller\Produit;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/produit')]
class ProduitCrudController extends AbstractController
{
    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'app_produit_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q         = $request->query->get('q');
        $minPrice  = $request->query->get('minPrice');
        $maxPrice  = $request->query->get('maxPrice');
        $available = $request->query->get('available');

        $minPrice  = ($minPrice !== null && $minPrice !== '') ? (float)$minPrice : null;
        $maxPrice  = ($maxPrice !== null && $maxPrice !== '') ? (float)$maxPrice : null;
        $available = ($available === '1') ? true : (($available === '0') ? false : null);

        $produits = $this->produitRepository->findByFilters($q, $minPrice, $maxPrice, $available);

        // Stats
        $totalProduits   = $this->produitRepository->count([]);
        $totalEnStock    = $this->produitRepository->countByStockGreaterThanZero();
        $totalRupture    = $this->produitRepository->countByStockZero();
        $totalCategories = $this->produitRepository->createQueryBuilder('p')
            ->select('COUNT(DISTINCT IDENTITY(p.categorie))')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('ProductTemplate/produit/index.html.twig', [
            'produits' => $produits,
            'filters'  => compact('q', 'minPrice', 'maxPrice', 'available'),
            'stats'    => [
                'total'      => $totalProduits,
                'enStock'    => $totalEnStock,
                'rupture'    => $totalRupture,
                'categories' => (int)$totalCategories,
            ],
        ]);
    }

    #[Route('/ajax', name: 'app_produit_ajax', methods: ['GET'])]
    public function ajaxSearch(Request $request): Response
    {
        $q         = $request->query->get('q');
        $minPrice  = $request->query->get('minPrice') !== '' ? (float)$request->query->get('minPrice') : null;
        $maxPrice  = $request->query->get('maxPrice') !== '' ? (float)$request->query->get('maxPrice') : null;
        $available = $request->query->get('available') === '1' ? true : ($request->query->get('available') === '0' ? false : null);

        $produits = $this->produitRepository->findByFilters($q, $minPrice, $maxPrice, $available);

        return $this->render('ProductTemplate/produit/_produit_table.html.twig', ['produits' => $produits]);
    }

    #[Route('/csv', name: 'app_produit_csv', methods: ['GET'])]
    public function csvExport(Request $request): StreamedResponse
    {
        $q         = $request->query->get('q');
        $minPrice  = $request->query->get('minPrice') !== '' ? (float)$request->query->get('minPrice') : null;
        $maxPrice  = $request->query->get('maxPrice') !== '' ? (float)$request->query->get('maxPrice') : null;
        $available = $request->query->get('available') === '1' ? true : ($request->query->get('available') === '0' ? false : null);

        $produits = $this->produitRepository->findByFilters($q, $minPrice, $maxPrice, $available);

        $response = new StreamedResponse(function () use ($produits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Nom', 'Prix', 'Stock', 'Catégorie'], ';');
            foreach ($produits as $p) {
                fputcsv($handle, [
                    $p->getId(),
                    $p->getNom(),
                    $p->getPrix(),
                    $p->getStock(),
                    $p->getCategorie()?->getNom() ?? '–',
                ], ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="produits_' . date('Y-m-d_H-i') . '.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nom = trim((string) $produit->getNom());

            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du produit ne peut pas être vide.');
                return $this->render('ProductTemplate/produit/new.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            if (preg_match('/^\d+$/', $nom)) {
                $this->addFlash('error', 'Le nom du produit ne peut pas contenir uniquement des chiffres.');
                return $this->render('ProductTemplate/produit/new.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            $errors = $this->validator->validate($produit);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/produit/new.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            $this->entityManager->persist($produit);
            $this->entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ProductTemplate/produit/new.html.twig', [
            'produit' => $produit,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('ProductTemplate/produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nom = trim((string) $produit->getNom());

            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du produit ne peut pas être vide.');
                return $this->render('ProductTemplate/produit/edit.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            if (preg_match('/^\d+$/', $nom)) {
                $this->addFlash('error', 'Le nom du produit ne peut pas contenir uniquement des chiffres.');
                return $this->render('ProductTemplate/produit/edit.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            $errors = $this->validator->validate($produit);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/produit/edit.html.twig', [
                    'produit' => $produit,
                    'form'    => $form,
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ProductTemplate/produit/edit.html.twig', [
            'produit' => $produit,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit): Response
    {
        if ($this->isCsrfTokenValid('delete_produit_' . $produit->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($produit);
            $this->entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}