<?php

namespace App\Controller\Produit;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôle de saisie côté serveur :
 * - Validation des entrées : formulaire (Form Type + contraintes) puis ValidatorInterface->validate($entity).
 * - Pas de SQL brut : uniquement Repository->find($id) / findBy() (requêtes paramétrées Doctrine).
 * - ID route : requirements: ['id' => '\d+'] pour n'accepter que des entiers.
 * - Suppression : méthode POST + vérification du jeton CSRF (isCsrfTokenValid).
 */
#[Route('/admin/paiement')]
class PaiementCrudController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PaiementRepository $repository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'app_paiement_index', methods: ['GET'])]
    public function index(): Response
    {
        $items = $this->repository->findBy([], ['datePaiement' => 'DESC']);

        return $this->render('ProductTemplate/paiement/index.html.twig', [
            'paiements' => $items,
        ]);
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $paiement = new Paiement();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($paiement);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/paiement/new.html.twig', [
                    'paiement' => $paiement,
                    'form' => $form,
                ]);
            }
            $this->em->persist($paiement);
            $this->em->flush();
            $this->addFlash('success', 'Paiement créé.');
            return $this->redirectToRoute('app_paiement_index');
        }

        return $this->render('FrontOffice/boutique/paiment/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $paiement = $this->repository->find($id);
        if (!$paiement) {
            throw $this->createNotFoundException('Paiement introuvable.');
        }

        return $this->render('ProductTemplate/paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paiement_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $paiement = $this->repository->find($id);
        if (!$paiement) {
            throw $this->createNotFoundException('Paiement introuvable.');
        }

        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($paiement);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('ProductTemplate/paiement/edit.html.twig', [
                    'paiement' => $paiement,
                    'form' => $form,
                ]);
            }
            $this->em->flush();
            $this->addFlash('success', 'Paiement mis à jour.');
            return $this->redirectToRoute('app_paiement_index');
        }

        return $this->render('ProductTemplate/paiement/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_paiement_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $paiement = $this->repository->find($id);
        if (!$paiement) {
            throw $this->createNotFoundException('Paiement introuvable.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_paiement_' . $id, $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_paiement_index');
        }

        $this->em->remove($paiement);
        $this->em->flush();
        $this->addFlash('success', 'Paiement supprimé.');

        return $this->redirectToRoute('app_paiement_index');
    }
}
