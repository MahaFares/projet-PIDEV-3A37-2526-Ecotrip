<?php

namespace App\Controller\Transport;
use App\Entity\Chauffeur;
use App\Form\ChauffeurType;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/transport/chauffeur')]
final class ChauffeurController extends AbstractController
{
    #[Route('', name: 'app_chauffeur_index', methods: ['GET'])]
    public function index(Request $request, ChauffeurRepository $repo): Response
    {
        $q = $request->query->get('q');
        $chauffeurs = $repo->search($q);

        // Stats
        $total = count($chauffeurs);
        $totalExp = 0;
        foreach ($chauffeurs as $c) {
            $totalExp += $c->getExperience() ?? 0;
        }

        return $this->render('TransportTemplate/chauffeur/index.html.twig', [
            'chauffeurs' => $chauffeurs,
            'q' => $q,
            'stats' => [
                'total' => $total,
                'avgExperience' => $total > 0 ? round($totalExp / $total, 1) : 0,
            ]
        ]);
    }

    #[Route('/ajax', name: 'app_chauffeur_ajax', methods: ['GET'])]
    public function ajaxSearch(Request $request, ChauffeurRepository $repo): Response
    {
        $q = $request->query->get('q');
        $chauffeurs = $repo->search($q);
        return $this->render('TransportTemplate/chauffeur/_chauffeur_table.html.twig', [
            'chauffeurs' => $chauffeurs
        ]);
    }

    #[Route('/new', name: 'app_chauffeur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $chauffeur = new Chauffeur();
        $form = $this->createForm(ChauffeurType::class, $chauffeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($chauffeur);
            $em->flush();
            $this->addFlash('success', 'Chauffeur créé.');
            return $this->redirectToRoute('app_chauffeur_index');
        }

        return $this->render('TransportTemplate/chauffeur/new.html.twig', [
            'chauffeur' => $chauffeur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chauffeur_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Chauffeur $chauffeur): Response
    {
        return $this->render('TransportTemplate/chauffeur/show.html.twig', ['chauffeur' => $chauffeur]);
    }

    #[Route('/{id}/edit', name: 'app_chauffeur_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Chauffeur $chauffeur, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ChauffeurType::class, $chauffeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Chauffeur mis à jour.');
            return $this->redirectToRoute('app_chauffeur_index');
        }

        return $this->render('TransportTemplate/chauffeur/edit.html.twig', [
            'chauffeur' => $chauffeur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chauffeur_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Chauffeur $chauffeur, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $chauffeur->getId(), $request->request->get('_token'))) {
            $em->remove($chauffeur);
            $em->flush();
            $this->addFlash('success', 'Chauffeur supprimé.');
        }
        return $this->redirectToRoute('app_chauffeur_index');
    }
}