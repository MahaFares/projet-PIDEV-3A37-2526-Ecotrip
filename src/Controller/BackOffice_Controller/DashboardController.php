<?php

namespace App\Controller\BackOffice_Controller;

use App\Controller\Api\FaceController;
use App\Entity\User;
use App\Repository\ChauffeurRepository;
use App\Repository\TransportCategoryRepository;
use App\Repository\TransportRepository;
use App\Repository\TrajetRepository;
use App\Repository\CategorieHebergementRepository;
use App\Repository\ChambreRepository;
use App\Repository\EquipementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ActivityRepository;
use App\Repository\ActivityCategoryRepository;
use App\Repository\ActivityScheduleRepository;
use App\Repository\GuideRepository;
use App\Repository\HebergementRepository;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\PaiementRepository;

class DashboardController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        Request $request,
        ActivityRepository $activityRepo,
        ActivityCategoryRepository $categoryRepo,
        ActivityScheduleRepository $scheduleRepo,
        GuideRepository $guideRepo,
        HebergementRepository $hebergementRepo,
        ChambreRepository $chambreRepo,
        EquipementRepository $equipementRepo,
        CategorieHebergementRepository $categorieHebergementRepo,
        TransportRepository $transportRepo,
        TransportCategoryRepository $transportCategoryRepo,
        ChauffeurRepository $chauffeurRepo,
        TrajetRepository $trajetRepo,
        ProduitRepository $produitRepo,
        CategorieRepository $categorieRepo,
        CommandeRepository $commandeRepo,
        PaiementRepository $paiementRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($user && $user->getFaceDescriptor() && !$request->getSession()->get(FaceController::FACE_GATE_SESSION_KEY)) {
            return $this->redirectToRoute('app_face_gate');
        }

        // Activity Statistics
        $totalActivities = $activityRepo->count([]);
        $activeActivities = $activityRepo->count(['isActive' => true]);
        $totalCategories = $categoryRepo->count([]);
        $totalGuides = $guideRepo->count([]);
        
        // Upcoming schedules
        $upcomingSchedules = $scheduleRepo->findUpcomingSchedules(5);
        
        // Activities by category
        $activitiesByCategory = $categoryRepo->getActivitiesCountByCategory();
        
        // Recent activities
        $recentActivities = $activityRepo->findBy([], ['id' => 'DESC'], 5);
        
        // Top rated activities
        $topRatedActivities = $activityRepo->findTopRated(5);

        // Boutique Statistics
        $totalProduits = $produitRepo->count([]);
        $totalCategoriesBoutique = $categorieRepo->count([]);
        $totalCommandes = $commandeRepo->count([]);
        $totalPaiements = $paiementRepo->count([]);

        // Hébergement & Chambre chart data
        $hebergementByCategory = $hebergementRepo->getCountByCategory();
        $chambresPerHebergement = $hebergementRepo->getChambresCountPerHebergement(10);
        $chambresByType = $chambreRepo->getCountByType();

        return $this->render('BackOffice/dashboard.html.twig', [
            'totalActivities' => $totalActivities,
            'activeActivities' => $activeActivities,
            'totalCategories' => $totalCategories,
            'totalGuides' => $totalGuides,
            'upcomingSchedules' => $upcomingSchedules,
            'activitiesByCategory' => $activitiesByCategory,
            'recentActivities' => $recentActivities,
            'topRatedActivities' => $topRatedActivities,
            'totalHebergements' => $hebergementRepo->count([]),
            'totalChambres' => $chambreRepo->count([]),
            'totalEquipements' => $equipementRepo->count([]),
            'totalCategoriesHebergement' => $categorieHebergementRepo->count([]),
            'totalTransports' => $transportRepo->count([]),
            'totalTransportCategories' => $transportCategoryRepo->count([]),
            'totalChauffeurs' => $chauffeurRepo->count([]),
            'totalTrajets' => $trajetRepo->count([]),
            'totalProduits' => $totalProduits,
            'totalCategoriesBoutique' => $totalCategoriesBoutique,
            'totalCommandes' => $totalCommandes,
            'totalPaiements' => $totalPaiements,
            'hebergementChartLabels' => array_keys($hebergementByCategory),
            'hebergementChartData' => array_values($hebergementByCategory),
            'chambresPerHebergementLabels' => array_map(fn ($r) => $r['nom'], $chambresPerHebergement),
            'chambresPerHebergementData' => array_map(fn ($r) => $r['count'], $chambresPerHebergement),
            'chambresByTypeLabels' => array_keys($chambresByType),
            'chambresByTypeData' => array_values($chambresByType),
        ]);
    }
}