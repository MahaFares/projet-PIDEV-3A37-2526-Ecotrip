<?php

namespace App\Controller\FrontOffice_Controller;

use App\Repository\TransportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ActivityRepository;
use App\Repository\ActivityCategoryRepository;
use App\Repository\ActivityScheduleRepository;
use App\Repository\GuideRepository;
use App\Repository\HebergementRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('FrontOffice/home/index.html.twig');
    }

    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('FrontOffice/about_us/about.html.twig');
    }

    #[Route('/hebergements', name: 'app_hebergement')]
    public function hebergement(HebergementRepository $hebergementRepository): Response
    {
        $hebergements = $hebergementRepository->findAll();
        return $this->render('FrontOffice/hebergement/accomodation.html.twig', [
            'hebergements' => $hebergements,
        ]);
    }

    #[Route('/activites', name: 'app_activites')]
    public function activites(
        ActivityRepository $activityRepository,
        ActivityCategoryRepository $categoryRepository,
        Request $request
    ): Response {
        try {
            // Get filter parameters
            $minPrice = $request->query->get('minPrice');
            $maxPrice = $request->query->get('maxPrice');
            $categoryId = $request->query->get('category');

            // Convert to proper types
            $minPrice = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
            $maxPrice = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;
            $categoryId = $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null;

            // Get all categories for the filter sidebar
            $sidebarCategories = $categoryRepository->findAll();

            // Build query
            $qb = $activityRepository->createQueryBuilder('a')
                ->leftJoin('a.category', 'c')
                ->leftJoin('a.schedules', 's')
                ->leftJoin('a.guide', 'g')
                ->addSelect('c', 's', 'g')
                ->where('a.isActive = :active')
                ->setParameter('active', true)
                ->orderBy('a.title', 'ASC');

            // Apply filters
            if ($categoryId !== null) {
                $qb->andWhere('c.id = :categoryId')
                    ->setParameter('categoryId', $categoryId);
            }

            if ($minPrice !== null) {
                $qb->andWhere('a.price >= :minPrice')
                    ->setParameter('minPrice', $minPrice);
            }

            if ($maxPrice !== null) {
                $qb->andWhere('a.price <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
            }

            $activities = $qb->getQuery()->getResult();

        } catch (\Exception $e) {
            $activities = [];
            $sidebarCategories = [];
        }

        return $this->render('FrontOffice/activities/blog.html.twig', [
            'activities' => $activities,
            'sidebarCategories' => $sidebarCategories,
            'selectedCategory' => $categoryId ?? null,
            'filterMinPrice' => $minPrice,
            'filterMaxPrice' => $maxPrice,
        ]);
    }

    #[Route('/transport', name: 'app_transport')]
    public function transport(TransportRepository $transportRepository, Request $request): Response
    {
        $type = $request->query->get('type');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $minCapacity = $request->query->get('minCapacity');
        $available = $request->query->get('available');

        $minPrice = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
        $maxPrice = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;
        $minCapacity = $minCapacity !== null && $minCapacity !== '' ? (int) $minCapacity : null;
        $available = ($available === '1' ? true : ($available === '0' ? false : null));

        $transports = $transportRepository->findByFilters($type, $minPrice, $maxPrice, $minCapacity, $available);

        return $this->render('FrontOffice/transport/accomodation.html.twig', [
            'transports' => $transports,
            'filters' => [
                'type' => $type,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'minCapacity' => $minCapacity,
                'available' => $available,
            ],
        ]);
    }

    #[Route('/boutique', name: 'app_boutique')]
    public function boutique(\App\Repository\ProduitRepository $produitRepo): Response
    {
        try {
            $produits = $produitRepo->findAll();
        } catch (\Exception $e) {
            $produits = [];
        }
        return $this->render('FrontOffice/boutique/boutique.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/se-connecter', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('FrontOffice/se_connecter/login.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('FrontOffice/contact/contact.html.twig');
    }
}
