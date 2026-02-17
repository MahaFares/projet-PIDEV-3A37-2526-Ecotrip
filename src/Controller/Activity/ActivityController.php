<?php

namespace App\Controller\Activity;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\ActivityCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/activity')]
final class ActivityController extends AbstractController
{
    #[Route('', name: 'app_activity_index', methods: ['GET'])]
    public function index(Request $request, ActivityRepository $activityRepository, ActivityCategoryRepository $categoryRepository): Response
    {
        // If it's an AJAX request, return JSON
        if ($request->isXmlHttpRequest()) {
            return $this->searchActivities($request, $activityRepository);
        }

        $activitiesByCategory = $categoryRepository->getActivitiesCountByCategory();
        $chartLabels = array_map(fn($item) => $item['category']->getName(), $activitiesByCategory);
        $chartData = array_map(fn($item) => $item['count'], $activitiesByCategory);
        $totalActivities = array_sum($chartData);

        // Otherwise, render the page normally (for initial page load)
        return $this->render('ActivityTemplate/activity/index.html.twig', [
            'activities' => [],
            'filters' => [
                'q' => '',
                'minPrice' => null,
                'maxPrice' => null,
                'available' => null,
            ],
            'stats' => [
                'chartLabels' => $chartLabels,
                'chartData' => $chartData,
                'totalActivities' => $totalActivities,
            ],
        ]);
    }

    #[Route('/search', name: 'app_activity_search', methods: ['GET'])]
    public function searchActivities(Request $request, ActivityRepository $activityRepository): JsonResponse
    {
        $q = $request->query->get('q');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $available = $request->query->get('available');

        $minPrice = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
        $maxPrice = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;
        $available = ($available === '1' ? true : ($available === '0' ? false : null));

        $activities = $activityRepository->findByFilters($q, $minPrice, $maxPrice, $available);

        // Transform activities to JSON-friendly array
        $data = array_map(function($activity) {
            $guideName = null;
            if ($activity->getGuide()) {
                $guideName = trim($activity->getGuide()->getFirstName() . ' ' . $activity->getGuide()->getLastName());
            }

            return [
                'id' => $activity->getId(),
                'title' => $activity->getTitle(),
                'description' => $activity->getDescription(),
                'price' => $activity->getPrice(),
                'durationMinutes' => $activity->getDurationMinutes(),
                'location' => $activity->getLocation(),
                'categoryName' => $activity->getCategory() ? $activity->getCategory()->getName() : null,
                'isActive' => $activity->isActive(),
                'image' => $activity->getImage(),
                'guideName' => $guideName,
            ];
        }, $activities);

        return $this->json([
            'success' => true,
            'activities' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_activity_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generatePdf(Activity $activity, SluggerInterface $slugger): Response
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);

        // Render HTML template
        $html = $this->renderView('ActivityTemplate/activity/pdf.html.twig', [
            'activity' => $activity,
            'generatedAt' => new \DateTime(),
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // Setup paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render PDF
        $dompdf->render();

        // Generate filename with slugged title
        $safeTitle = $slugger->slug($activity->getTitle());
        $filename = sprintf('activity-%s-%s.pdf',
            $activity->getId(),
            $safeTitle
        );

        // Output PDF (force download)
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route('/new', name: 'app_activity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Create directory if it doesn't exist
                $filesystem = new Filesystem();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/activities';
                if (!$filesystem->exists($uploadDir)) {
                    $filesystem->mkdir($uploadDir, 0755);
                }

                // Move the file
                $imageFile->move($uploadDir, $newFilename);

                // Store relative path in database
                $activity->setImage('images/activities/' . $newFilename);
            }

            $entityManager->persist($activity);
            $entityManager->flush();

            $this->addFlash('success', 'Activité créée avec succès!');

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ActivityTemplate/activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Activity $activity): Response
    {
        return $this->render('ActivityTemplate/activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activity_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Delete old image if it exists
                $filesystem = new Filesystem();
                if ($activity->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/' . $activity->getImage();
                    if ($filesystem->exists($oldImagePath)) {
                        $filesystem->remove($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Create directory if it doesn't exist
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/activities';
                if (!$filesystem->exists($uploadDir)) {
                    $filesystem->mkdir($uploadDir, 0755);
                }

                // Move the file
                $imageFile->move($uploadDir, $newFilename);

                // Store relative path in database
                $activity->setImage('images/activities/' . $newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Activité modifiée avec succès!');

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ActivityTemplate/activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activity->getId(), $request->getPayload()->getString('_token'))) {
            // Delete image file if exists
            $filesystem = new Filesystem();
            if ($activity->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $activity->getImage();
                if ($filesystem->exists($imagePath)) {
                    $filesystem->remove($imagePath);
                }
            }

            $entityManager->remove($activity);
            $entityManager->flush();

            $this->addFlash('success', 'Activité supprimée avec succès!');
        }

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }
}
