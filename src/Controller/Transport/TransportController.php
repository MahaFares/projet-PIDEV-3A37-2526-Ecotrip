<?php

namespace App\Controller\Transport;

use App\Entity\Transport;
use App\Form\Transport1Type;
use App\Repository\TransportRepository;
use App\Service\SmsService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/transport')]
final class TransportController extends AbstractController
{
    #[Route(name: 'app_transport_index', methods: ['GET'])]
    public function index(Request $request, TransportRepository $transportRepository): Response
    {
        $q           = $request->query->get('q');
        $type        = $request->query->get('type');
        $minPrice    = $request->query->get('minPrice');
        $maxPrice    = $request->query->get('maxPrice');
        $minCapacity = $request->query->get('minCapacity');
        $available   = $request->query->get('available');

        // Conversion des filtres
        $minPrice    = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
        $maxPrice    = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;
        $minCapacity = $minCapacity !== null && $minCapacity !== '' ? (int) $minCapacity : null;
        $available   = ($available === '1' ? true : ($available === '0' ? false : null));

        $transports = $transportRepository->findByFilters(
            $type, $minPrice, $maxPrice, $minCapacity, $available, $q
        );

        // === Statistiques ===
        $allTransports = $transportRepository->findAll();
        $stats = [
            'total'       => count($allTransports),
            'available'   => count(array_filter($allTransports, fn($t) => $t->isDisponible())),
            'unavailable' => count(array_filter($allTransports, fn($t) => !$t->isDisponible())),
            'types'       => [],
            'chartLabels' => [],
            'chartData'   => [],
        ];

        $typesCounts = [];
        foreach ($allTransports as $transport) {
            $type = $transport->getType() ?? 'Non défini';
            $typesCounts[$type] = ($typesCounts[$type] ?? 0) + 1;
        }
        $stats['types']       = array_keys($typesCounts);
        $stats['chartLabels'] = array_keys($typesCounts);
        $stats['chartData']   = array_values($typesCounts);

        return $this->render('TransportTemplate/transport/index.html.twig', [
            'transports' => $transports,
            'stats'      => $stats,
            'filters'    => [
                'q'           => $q,
                'type'        => $type,
                'minPrice'    => $minPrice,
                'maxPrice'    => $maxPrice,
                'minCapacity' => $minCapacity,
                'available'   => $available,
            ],
        ]);
    }

    #[Route('/ajax', name: 'app_transport_ajax', methods: ['GET'])]
    public function ajax(Request $request, TransportRepository $transportRepository): Response
    {
        $q           = $request->query->get('q');
        $type        = $request->query->get('type');
        $minPrice    = $request->query->get('minPrice');
        $maxPrice    = $request->query->get('maxPrice');
        $minCapacity = $request->query->get('minCapacity');
        $available   = $request->query->get('available');

        $minPrice    = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
        $maxPrice    = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;
        $minCapacity = $minCapacity !== null && $minCapacity !== '' ? (int) $minCapacity : null;
        $available   = ($available === '1' ? true : ($available === '0' ? false : null));

        $transports = $transportRepository->findByFilters(
            $type, $minPrice, $maxPrice, $minCapacity, $available, $q
        );

        return $this->render('TransportTemplate/transport/_transport_table.html.twig', [
            'transports' => $transports,
        ]);
    }

    #[Route('/new', name: 'app_transport_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SmsService $smsService
    ): Response
    {
        $transport = new Transport();
        $form = $this->createForm(Transport1Type::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires ou corriger les erreurs du formulaire.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion upload image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/transports',
                        $newFilename
                    );
                    $transport->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Échec upload image : ' . $e->getMessage());
                }
            }

            $entityManager->persist($transport);
            $entityManager->flush();

            // Message SMS détaillé pour création
            $chauffeurName = $transport->getChauffeur()?->getFullName() ?? 'Non assigné';
            $disponible    = $transport->isDisponible() ? 'Oui' : 'Non';

            $message = "EcoTrip : NOUVEAU TRANSPORT CRÉÉ !\n" .
                "ID: #" . $transport->getId() . "\n" .
                "Type: " . ($transport->getType() ?? 'Non défini') . "\n" .
                "Chauffeur: " . $chauffeurName . "\n" .
                "Capacité: " . ($transport->getCapacite() ?? '?') . " places\n" .
                "Prix/pers.: " . ($transport->getPrixparpersonne() ?? '-') . " TND\n" .
                "Dispo: " . $disponible . "\n" .
                "Vérifiez dans l'admin EcoTrip.";

            $smsService->send('+21699950854', $message);

            $this->addFlash('success', 'Transport créé avec succès ! SMS envoyé.');

            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('TransportTemplate/transport/new.html.twig', [
            'transport' => $transport,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_show', methods: ['GET'])]
    public function show(Transport $transport, WeatherService $weatherService): Response
    {
        $weather = $weatherService->getCurrentWeather('Tunis', 'TN');

        return $this->render('TransportTemplate/transport/show.html.twig', [
            'transport' => $transport,
            'weather'   => $weather,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transport_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Transport $transport,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SmsService $smsService
    ): Response
    {
        $form = $this->createForm(Transport1Type::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion upload image (remplacement)
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    // Supprime ancienne image si existe
                    if ($transport->getImage()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/transports/' . $transport->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/transports',
                        $newFilename
                    );
                    $transport->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Échec upload image : ' . $e->getMessage());
                }
            }

            // Message SMS détaillé pour modification
            $chauffeurName = $transport->getChauffeur()?->getFullName() ?? 'Non assigné';
            $disponible    = $transport->isDisponible() ? 'Oui' : 'Non';

            $message = "EcoTrip : TRANSPORT MODIFIÉ !\n" .
                "ID: #" . $transport->getId() . "\n" .
                "Type: " . ($transport->getType() ?? 'Non défini') . "\n" .
                "Chauffeur: " . $chauffeurName . "\n" .
                "Capacité: " . ($transport->getCapacite() ?? '?') . " places\n" .
                "Prix/pers.: " . ($transport->getPrixparpersonne() ?? '-') . " TND\n" .
                "Dispo: " . $disponible . "\n" .
                "Modifications enregistrées. Consultez l'admin EcoTrip.";

            $smsService->send('+21699950854', $message);

            $entityManager->flush();

            $this->addFlash('success', 'Transport modifié avec succès ! SMS envoyé.');

            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('TransportTemplate/transport/edit.html.twig', [
            'transport' => $transport,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_delete', methods: ['POST'])]
    public function delete(Request $request, Transport $transport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $transport->getId(), $request->getPayload()->getString('_token'))) {
            if ($transport->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/transports/' . $transport->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($transport);
            $entityManager->flush();

            $this->addFlash('success', 'Transport supprimé avec succès !');
        }

        return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
    }
}