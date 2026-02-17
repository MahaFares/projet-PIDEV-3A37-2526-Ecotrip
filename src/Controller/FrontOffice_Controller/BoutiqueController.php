<?php

namespace App\Controller\FrontOffice_Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BoutiqueController extends AbstractController
{
   #[Route('/boutique', name: 'front_boutique')]
public function index(ProduitRepository $repo): Response
{
    try {
        $produits = $repo->findAll();
    } catch (\Exception $e) {
        $produits = [];
    }

    return $this->render('FrontOffice/boutique/boutique.html.twig', [
        'produits' => $produits  // Changé de 'categories' à 'produits'
    ]);

    
}

// Route pour UN SEUL produit (utilise detail.html.twig)
    #[Route('/boutique/produit/{id}', name: 'front_produit_detail')]
    public function detail(int $id, ProduitRepository $repo): Response
    {
        $produit = $repo->find($id);
        
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('FrontOffice/boutique/produit/detail.html.twig', [
            'produit' => $produit
        ]);
    }
}

