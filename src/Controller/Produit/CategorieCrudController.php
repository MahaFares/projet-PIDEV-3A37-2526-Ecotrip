<?php

namespace App\Controller\Produit;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/produit/categorie')]
class CategorieCrudController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategorieRepository $repository,
        private readonly ValidatorInterface $validator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route(name: 'app_categorie_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // If it's an AJAX request, return JSON
        if ($request->isXmlHttpRequest()) {
            return $this->searchCategories($request);
        }

        // Get stats
        $totalCategories = $this->repository->count([]);
        $aLouerCount = $this->repository->count(['nom' => 'A louer']);
        $aVendreCount = $this->repository->count(['nom' => 'A vendre']);

        return $this->render('ProductTemplate/categorie/index.html.twig', [
            'categories' => [],
            'stats' => [
                'total' => $totalCategories,
                'aLouer' => $aLouerCount,
                'aVendre' => $aVendreCount,
            ],
        ]);
    }

    #[Route('/search', name: 'app_categorie_search', methods: ['GET'])]
    public function searchCategories(Request $request): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');
        $filterType = $request->query->get('type', '');

        $qb = $this->repository->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC');

        // Search by name or description
        if (!empty($searchTerm)) {
            $qb->andWhere('c.nom LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Filter by type (A louer / A vendre)
        if (!empty($filterType)) {
            $qb->andWhere('c.nom = :type')
                ->setParameter('type', $filterType);
        }

        $categories = $qb->getQuery()->getResult();

        // Transform to JSON-friendly array (with delete CSRF token per row for delete-from-table)
        $data = array_map(function ($categorie) {
            $tokenId = 'delete_categorie_' . $categorie->getId();
            return [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'description' => $categorie->getDescription(),
                'produitsCount' => $categorie->getProduits()->count(),
                'deleteToken' => $this->csrfTokenManager->getToken($tokenId)->getValue(),
            ];
        }, $categories);

        return $this->json([
            'success' => true,
            'categories' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/new', name: 'app_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($categorie);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/categorie/new.html.twig', [
                    'categorie' => $categorie,
                    'form' => $form,
                ]);
            }
            $this->em->persist($categorie);
            $this->em->flush();
            $this->addFlash('success', 'Catégorie créée avec succès!');
            return $this->redirectToRoute('app_categorie_index');
        }

        return $this->render('ProductTemplate/categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $categorie = $this->repository->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        return $this->render('ProductTemplate/categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $categorie = $this->repository->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($categorie);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/categorie/edit.html.twig', [
                    'categorie' => $categorie,
                    'form' => $form,
                ]);
            }
            $this->em->flush();
            $this->addFlash('success', 'Catégorie mise à jour avec succès!');
            return $this->redirectToRoute('app_categorie_index');
        }

        return $this->render('ProductTemplate/categorie/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_categorie_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $categorie = $this->repository->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $token = $request->request->get('_token');
        if ($token === null) {
            $data = $request->request->all();
            foreach ($data as $value) {
                if (\is_array($value) && isset($value['_token'])) {
                    $token = $value['_token'];
                    break;
                }
            }
        }
        $tokenId = 'delete_categorie_' . $id;
        if (!$token || !$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Réessayez de supprimer.');
            return $this->redirectToRoute('app_categorie_show', ['id' => $id]);
        }

        foreach ($categorie->getProduits() as $produit) {
            foreach ($produit->getCommandes() as $commande) {
                $commande->setProduit(null);
            }
            foreach ($produit->getLigneDeCommandes() as $ligne) {
                $ligne->setIdProduct(null);
            }
        }

        try {
            $this->em->remove($categorie);
            $this->em->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès!');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'foreign key') || str_contains($msg, 'constraint') || str_contains($msg, '1451') || str_contains($msg, 'Integrity')) {
                $this->addFlash('error', 'Impossible de supprimer : des produits de cette catégorie sont liés à des commandes.');
            } else {
                $this->addFlash('error', 'Impossible de supprimer la catégorie (erreur base de données).');
            }
            return $this->redirectToRoute('app_categorie_show', ['id' => $id]);
        }

        return $this->redirectToRoute('app_categorie_index');
    }
}