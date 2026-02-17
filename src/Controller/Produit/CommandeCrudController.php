<?php

namespace App\Controller\Produit;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/commande')]
class CommandeCrudController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommandeRepository $repository,
        private readonly ValidatorInterface $validator,
        private readonly ProduitRepository $produitRepository,
    ) {
    }

    #[Route('', name: 'app_commande_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // If it's an AJAX request, return JSON
        if ($request->isXmlHttpRequest()) {
            return $this->searchCommandes($request);
        }

        // Get stats
        $totalCommandes = $this->repository->count([]);
        $totalRevenue = $this->repository->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Today's revenue - using proper date comparison
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $todayRevenue = $this->repository->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.dateCommande >= :today')
            ->andWhere('c.dateCommande < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Get all products for filter
        $produits = $this->produitRepository->findAll();

        return $this->render('ProductTemplate/commande/index.html.twig', [
            'commandes' => [],
            'produits' => $produits,
            'stats' => [
                'total' => $totalCommandes,
                'revenue' => $totalRevenue,
                'todayRevenue' => $todayRevenue,
            ],
        ]);
    }

    #[Route('/search', name: 'app_commande_search', methods: ['GET'])]
    public function searchCommandes(Request $request): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');
        $produitId = $request->query->get('produit', '');
        $minTotal = $request->query->get('minTotal', '');
        $maxTotal = $request->query->get('maxTotal', '');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $qb = $this->repository->createQueryBuilder('c')
            ->leftJoin('c.produit', 'p')
            ->addSelect('p')
            ->orderBy('c.dateCommande', 'DESC');

        // Search by user ID
        if (!empty($searchTerm)) {
            $qb->andWhere('c.idUser LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Filter by product
        if (!empty($produitId)) {
            $qb->andWhere('p.idProduit = :produitId')
                ->setParameter('produitId', $produitId);
        }

        // Filter by total amount
        if ($minTotal !== '') {
            $qb->andWhere('c.total >= :minTotal')
                ->setParameter('minTotal', $minTotal);
        }
        if ($maxTotal !== '') {
            $qb->andWhere('c.total <= :maxTotal')
                ->setParameter('maxTotal', $maxTotal);
        }

        // Filter by date range
        if (!empty($dateFrom)) {
            $qb->andWhere('c.dateCommande >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        if (!empty($dateTo)) {
            $qb->andWhere('c.dateCommande <= :dateTo')
                ->setParameter('dateTo', (new \DateTime($dateTo))->setTime(23, 59, 59));
        }

        // Get total count for pagination
        $totalQuery = clone $qb;
        $total = count($totalQuery->getQuery()->getResult());

        // Apply pagination
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        $commandes = $qb->getQuery()->getResult();

        // Transform to JSON-friendly array
        $data = array_map(function($commande) {
            return [
                'id' => $commande->getIdCommande(),
                'idUser' => $commande->getIdUser(),
                'produitNom' => $commande->getProduit() ? $commande->getProduit()->getNom() : 'N/A',
                'quantite' => $commande->getQuantite(),
                'prixUnitaire' => $commande->getPrixUnitaire(),
                'total' => $commande->getTotal(),
                'dateCommande' => $commande->getDateCommande()->format('d/m/Y H:i'),
            ];
        }, $commandes);

        return $this->json([
            'success' => true,
            'commandes' => $data,
            'count' => count($data),
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $commande = new Commande();
        $commande->setDateCommande(new \DateTime());

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($commande);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/commande/new.html.twig', [
                    'commande' => $commande,
                    'form' => $form,
                ]);
            }
            $this->em->persist($commande);
            $this->em->flush();
            $this->addFlash('success', 'Commande créée avec succès!');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('ProductTemplate/commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $commande = $this->repository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('ProductTemplate/commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $commande = $this->repository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($commande);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/commande/edit.html.twig', [
                    'commande' => $commande,
                    'form' => $form,
                ]);
            }
            $this->em->flush();
            $this->addFlash('success', 'Commande mise à jour avec succès!');
            return $this->redirectToRoute('app_commande_index');
        }

        return $this->render('ProductTemplate/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_commande_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $commande = $this->repository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_commande_' . $id, $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_commande_index');
        }

        $this->em->remove($commande);
        $this->em->flush();
        $this->addFlash('success', 'Commande supprimée avec succès!');

        return $this->redirectToRoute('app_commande_index');
    }
}