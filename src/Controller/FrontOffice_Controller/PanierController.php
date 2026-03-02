<?php

namespace App\Controller\FrontOffice_Controller;

use App\Entity\Activity;
use App\Entity\Commande;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\ReservationStatus;
use App\Enum\ReservationType;
use App\Entity\Hebergement;
use App\Entity\LigneDeCommande;
use App\Entity\Paiement;
use App\Entity\PaymentReservation;
use App\Entity\Produit;
use App\Entity\Reservation;
use App\Entity\Transport;
use App\Repository\ActivityRepository;
use App\Repository\HebergementRepository;
use App\Repository\ProduitRepository;
use App\Repository\TransportRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Stripe\Checkout\Session as StripeSession;
use TCPDF;
use Stripe\Stripe;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/panier')]
class PanierController extends AbstractController
{
    #[Route('', name: 'app_panier_index', methods: ['GET'])]
    public function index(
        CartService $cartService,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo,
        ProduitRepository $produitRepo
    ): Response {
        $cart = $cartService->getCart();
        $items = [];

        // Batch-load entities (one query per type instead of per item)
        $hebergementIds = array_unique(array_column($cart['hebergements'] ?? [], 'id'));
        $activityIds = array_unique(array_column($cart['activities'] ?? [], 'id'));
        $transportIds = array_unique(array_column($cart['transports'] ?? [], 'id'));
        $produitIds = array_unique(array_column($cart['produits'] ?? [], 'id'));

        $hebergementsMap = $hebergementIds ? array_column($hebergementRepo->findBy(['id' => $hebergementIds]), null, 'id') : [];
        $activitiesMap = $activityIds ? array_column($activityRepo->findBy(['id' => $activityIds]), null, 'id') : [];
        $transportsMap = $transportIds ? array_column($transportRepo->findBy(['id' => $transportIds]), null, 'id') : [];
$produitsRaw = $produitIds ? $produitRepo->findBy(['idProduit' => $produitIds]) : [];
$produitsMap = [];
foreach ($produitsRaw as $p) {
    $produitsMap[$p->getId()] = $p;
}
        foreach ($cart['hebergements'] ?? [] as $cartKey => $data) {
            $id = $data['id'] ?? 0;
            $items[] = ['cartKey' => $cartKey, 'type' => 'hebergement', 'entity' => $hebergementsMap[$id] ?? null, 'data' => $data];
        }
        foreach ($cart['activities'] ?? [] as $cartKey => $data) {
            $id = $data['id'] ?? 0;
            $items[] = ['cartKey' => $cartKey, 'type' => 'activity', 'entity' => $activitiesMap[$id] ?? null, 'data' => $data];
        }
        foreach ($cart['transports'] ?? [] as $cartKey => $data) {
            $id = $data['id'] ?? 0;
            $items[] = ['cartKey' => $cartKey, 'type' => 'transport', 'entity' => $transportsMap[$id] ?? null, 'data' => $data];
        }
        foreach ($cart['produits'] ?? [] as $cartKey => $data) {
            $id = $data['id'] ?? 0;
            $items[] = ['cartKey' => $cartKey, 'type' => 'produit', 'entity' => $produitsMap[$id] ?? null, 'data' => $data];
        }

        return $this->render('FrontOffice/panier/index.html.twig', [
            'cartItems' => $items,
            'total' => $cartService->getTotal(),
        ]);
    }

    /**
     * Re-add a PENDING reservation to the cart so the user can pay (redirects to panier).
     */
    #[Route('/restore-reservation/{id}', name: 'app_panier_restore_reservation', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restoreReservation(
        Reservation $reservation,
        Request $request,
        CartService $cartService,
        HebergementRepository $hebergementRepo,
        ActivityRepository $activityRepo,
        TransportRepository $transportRepo
    ): Response {
        if (!$this->isCsrfTokenValid('restore_reservation', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_mes_reservations');
        }
        $user = $this->getUser();
        if (!$user || $reservation->getUser() !== $user) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_mes_reservations');
        }
        if ($reservation->getStatus() !== ReservationStatus::PENDING) {
            $this->addFlash('info', 'Cette réservation est déjà payée ou annulée.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        $id = $reservation->getReservationId();
        $totalPrice = (float) $reservation->getTotalPrice();
        $persons = $reservation->getNumberOfPersons() ?: 1;
        $details = $reservation->getDetails() ?? [];
        $dateFrom = $reservation->getDateFrom();
        $dateTo = $reservation->getDateTo();

        switch ($reservation->getReservationType()) {
            case ReservationType::HEBERGEMENT:
                $hebergement = $hebergementRepo->find($id);
                if (!$hebergement) {
                    $this->addFlash('error', 'Hébergement introuvable.');
                    return $this->redirectToRoute('app_mes_reservations');
                }
                $nights = (int) ($details['nights'] ?? 1);
                $pricePerNight = $nights > 0 ? $totalPrice / $nights : $totalPrice;
                $options = [
                    'guests' => $persons,
                ];
                if ($dateFrom) {
                    $options['dateFrom'] = $dateFrom->format('Y-m-d');
                }
                if ($dateTo) {
                    $options['dateTo'] = $dateTo->format('Y-m-d');
                }
                $cartService->addHebergement($id, (float) $pricePerNight, $hebergement->getNom(), $nights, $options);
                break;
            case ReservationType::ACTIVITY:
                $activity = $activityRepo->find($id);
                if (!$activity) {
                    $this->addFlash('error', 'Activité introuvable.');
                    return $this->redirectToRoute('app_mes_reservations');
                }
                $pricePerPerson = $persons > 0 ? $totalPrice / $persons : (float) $activity->getPrice();
                $options = ['participants' => $persons];
                if ($dateFrom) {
                    $options['reservedAt'] = $dateFrom->format(\DateTimeInterface::ATOM);
                }
                $cartService->addActivity($id, $pricePerPerson, $activity->getTitle(), $persons, $options);
                break;
            case ReservationType::TRANSPORT:
                $transport = $transportRepo->find($id);
                if (!$transport) {
                    $this->addFlash('error', 'Transport introuvable.');
                    return $this->redirectToRoute('app_mes_reservations');
                }
                $pricePerPerson = $persons > 0 ? $totalPrice / $persons : (float) $transport->getPrixparpersonne();
                $options = [
                    'passengers' => $persons,
                    'depart' => $details['depart'] ?? null,
                    'arrivee' => $details['arrivee'] ?? null,
                ];
                if ($dateFrom) {
                    $options['travelDate'] = $dateFrom->format('Y-m-d');
                }
                $cartService->addTransport($id, (float) $pricePerPerson, $transport->getType(), $persons, $options);
                break;
            default:
                $this->addFlash('error', 'Type de réservation non géré.');
                return $this->redirectToRoute('app_mes_reservations');
        }

        $this->addFlash('success', 'Réservation ajoutée au panier. Vous pouvez procéder au paiement.');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/add/hebergement/{id}', name: 'app_panier_add_hebergement', methods: ['POST'])]
    public function addHebergement(int $id, Request $request, CartService $cartService, HebergementRepository $repo): JsonResponse
    {
        $hebergement = $repo->findWithChambres($id);
        if (!$hebergement) {
            return new JsonResponse(['success' => false, 'message' => 'Hébergement introuvable'], 404);
        }
        $nights = max(1, (int) ($request->request->get('nights', 1)));
        $dateFrom = $request->request->get('dateFrom');
        $dateTo = $request->request->get('dateTo');
        $guests = $request->request->get('guests');
        $price = 0;
        foreach ($hebergement->getChambres() as $chambre) {
            if ($chambre->getPrixParNuit() && (!$price || $chambre->getPrixParNuit() < $price)) {
                $price = $chambre->getPrixParNuit();
            }
        }
        if ($price <= 0) {
            $price = 50; // fallback
        }
        $options = array_filter([
            'dateFrom' => $dateFrom ?: null,
            'dateTo' => $dateTo ?: null,
            'guests' => $guests !== null && $guests !== '' ? (int) $guests : null,
        ], fn ($v) => $v !== null);
        $cartService->addHebergement($id, $price, $hebergement->getNom(), $nights, $options);
        return new JsonResponse(['success' => true, 'count' => $cartService->getCount()]);
    }

    #[Route('/add/activity/{id}', name: 'app_panier_add_activity', methods: ['POST'])]
    public function addActivity(int $id, Request $request, CartService $cartService, ActivityRepository $repo): JsonResponse
    {
        $activity = $repo->find($id);
        if (!$activity) {
            return new JsonResponse(['success' => false, 'message' => 'Activité introuvable'], 404);
        }
        $quantity = max(1, (int) ($request->request->get('participants', 1)));
        $price = (float) $activity->getPrice();
        $options = array_filter([
            'reservedAt' => $request->request->get('reservedAt') ?: null,
            'participants' => $quantity,
        ], fn ($v) => $v !== null);
        $cartService->addActivity($id, $price, $activity->getTitle(), $quantity, $options);
        return new JsonResponse(['success' => true, 'count' => $cartService->getCount()]);
    }

    #[Route('/add/transport/{id}', name: 'app_panier_add_transport', methods: ['POST'])]
    public function addTransport(int $id, Request $request, CartService $cartService, TransportRepository $repo): JsonResponse
    {
        $transport = $repo->find($id);
        if (!$transport) {
            return new JsonResponse(['success' => false, 'message' => 'Transport introuvable'], 404);
        }
        $quantity = max(1, (int) ($request->request->get('passengers', 1)));
        $price = (float) $transport->getPrixparpersonne();
        $options = array_filter([
            'depart' => $request->request->get('depart') ?: null,
            'arrivee' => $request->request->get('arrivee') ?: null,
            'travelDate' => $request->request->get('travelDate') ?: null,
            'passengers' => $quantity,
        ], fn ($v) => $v !== null);
        $cartService->addTransport($id, $price, $transport->getType(), $quantity, $options);
        return new JsonResponse(['success' => true, 'count' => $cartService->getCount()]);
    }

    #[Route('/add/produit/{id}', name: 'app_panier_add_produit', methods: ['POST'])]
    public function addProduit(int $id, Request $request, CartService $cartService, ProduitRepository $repo): JsonResponse
    {
        $produit = $repo->find($id);
        if (!$produit || $produit->getStock() <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Produit indisponible'], 404);
        }
        $quantity = max(1, min($produit->getStock(), (int) ($request->request->get('quantity', 1))));
        $price = (float) $produit->getPrix();
        $cartService->addProduit($id, $price, $produit->getNom(), $quantity);
        return new JsonResponse(['success' => true, 'count' => $cartService->getCount()]);
    }

    #[Route('/remove/{type}/{key}', name: 'app_panier_remove', methods: ['POST'])]
    public function remove(string $type, string $key, CartService $cartService): JsonResponse
    {
        $typeMap = ['hebergement' => 'hebergements', 'activity' => 'activities', 'transport' => 'transports', 'produit' => 'produits'];
        $cartType = $typeMap[$type] ?? $type . 's';
        $cartService->remove($cartType, $key);
        return new JsonResponse(['success' => true, 'count' => $cartService->getCount(), 'total' => $cartService->getTotal()]);
    }

    #[Route('/update-quantity/{key}', name: 'app_panier_update_quantity', methods: ['POST'])]
    public function updateQuantity(string $key, Request $request, CartService $cartService, ProduitRepository $produitRepo): JsonResponse
    {
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cart = $cartService->getCart();
        $data = $cart['produits'][$key] ?? null;
        if (!$data) {
            return new JsonResponse(['success' => false], 404);
        }
        $produit = $produitRepo->find($data['id']);
        if (!$produit || $quantity > $produit->getStock()) {
            return new JsonResponse(['success' => false, 'message' => 'Stock insuffisant']);
        }
        $cartService->updateProduitQuantity($key, $quantity);
        return new JsonResponse(['success' => true, 'total' => $cartService->getTotal()]);
    }

    #[Route('/checkout', name: 'app_panier_checkout', methods: ['GET'])]
    public function checkout(CartService $cartService): Response
    {
        if ($cartService->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour procéder au paiement.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('FrontOffice/panier/checkout.html.twig', [
            'total' => $cartService->getTotal(),
        ]);
    }

    #[Route('/payment', name: 'app_panier_payment', methods: ['GET', 'POST'])]
    public function payment(Request $request, CartService $cartService, EntityManagerInterface $em): Response
    {
        if ($cartService->isEmpty()) {
            return $this->redirectToRoute('app_panier_index');
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('panier_payment', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_panier_payment');
            }
            $method = $request->request->get('method', 'CARD');
            $methodEnum = PaymentMethod::tryFrom($method) ?? PaymentMethod::CARD;

            // Si paiement par carte, création d'une session Stripe Checkout
            if ($methodEnum === PaymentMethod::CARD) {
                try {
                    $total = (float) $cartService->getTotal();
                    if ($total <= 0) {
                        $this->addFlash('error', 'Montant invalide pour le paiement.');
                        return $this->redirectToRoute('app_panier_payment');
                    }

                    Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

                    $session = StripeSession::create([
                        'mode' => 'payment',
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => 'Commande EcoTrip',
                                ],
                                'unit_amount' => (int) round($total * 100),
                            ],
                            'quantity' => 1,
                        ]],
                        'success_url' => $this->generateUrl('app_panier_stripe_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'cancel_url' => $this->generateUrl('app_panier_stripe_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);

                    return $this->redirect($session->url);
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Erreur lors de la création du paiement Stripe : ' . $e->getMessage());
                    return $this->redirectToRoute('app_panier_payment');
                }
            }

            // Paiement classique (espèces, etc.) traité directement côté application
            $this->finalizePayment($methodEnum, $cartService, $em, $user);
            $this->addFlash('success', 'Paiement effectué avec succès ! Merci pour votre commande.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('FrontOffice/panier/payment.html.twig', [
            'total' => $cartService->getTotal(),
        ]);
    }

    #[Route('/stripe/success', name: 'app_panier_stripe_success', methods: ['GET'])]
    public function stripeSuccess(CartService $cartService, EntityManagerInterface $em): Response
    {
        if ($cartService->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide ou le paiement a déjà été traité.');
            return $this->redirectToRoute('app_panier_index');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour finaliser le paiement.');
            return $this->redirectToRoute('app_login');
        }

        // On garde le total avant de vider le panier
        $total = (float) $cartService->getTotal();

        // Finaliser la commande et vider le panier
        $commande = $this->finalizePayment(PaymentMethod::CARD, $cartService, $em, $user);

        return $this->render('FrontOffice/panier/success.html.twig', [
            'total' => $total,
            'commande' => $commande,
        ]);
    }

    #[Route('/stripe/cancel', name: 'app_panier_stripe_cancel', methods: ['GET'])]
    public function stripeCancel(): Response
    {
        $this->addFlash('warning', 'Le paiement Stripe a été annulé. Vous pouvez réessayer.');
        return $this->redirectToRoute('app_panier_payment');
    }

    #[Route('/facture/{id}/pdf', name: 'app_panier_facture_pdf', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function facturePdf(Commande $commande, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user || $commande->getIdUser() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette facture.');
        }

        $generatedAt = new \DateTime();
        $signatureHash = strtoupper(substr(md5($commande->getIdCommande() . '-' . $generatedAt->format('YmdHis')), 0, 12));
        $signatureData = $request->request->get('signature');

        // Créer le PDF avec TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration du document
        $pdf->SetCreator('EcoTrip');
        $pdf->SetAuthor('EcoTrip Tunisie');
        $pdf->SetTitle('Facture #' . $commande->getIdCommande());
        $pdf->SetSubject('Facture de commande');
        
        // Supprimer header/footer par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Styles
        $pdf->SetFont('helvetica', '', 11);
        
        // En-tête
        $pdf->SetFillColor(45, 80, 22);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(0, 0, 210, 40, 'F');
        $pdf->SetXY(15, 10);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'Facture EcoTrip', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, 20);
        $pdf->Cell(0, 5, 'Paiement confirmé - Reçu officiel', 0, 1);
        
        // Réinitialiser la couleur du texte
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(50);
        
        // Informations client et facture
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(15, 50, 85, 40, 'F');
        $pdf->SetXY(15, 52);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Client', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, 60);
        $pdf->Cell(0, 5, $user->getUsername() ?? 'Client', 0, 1);
        $pdf->SetXY(15, 66);
        $pdf->Cell(0, 5, $user->getEmail(), 0, 1);
        
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(110, 50, 85, 40, 'F');
        $pdf->SetXY(110, 52);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Facture', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(110, 60);
        $pdf->Cell(0, 5, 'Numéro : #' . $commande->getIdCommande(), 0, 1);
        $pdf->SetXY(110, 66);
        $pdf->Cell(0, 5, 'Date : ' . $commande->getDateCommande()->format('d/m/Y H:i'), 0, 1);
        $pdf->SetXY(110, 72);
        $pdf->SetFillColor(16, 185, 129);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(30, 8, 'Payé', 0, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Tableau des produits
        $pdf->SetY(100);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(243, 244, 246);
        $pdf->Cell(80, 8, 'Produit / Service', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Quantité', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Prix unitaire', 1, 0, 'R', true);
        $pdf->Cell(35, 8, 'Sous-total', 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($commande->getLigneDeCommandes() as $ligne) {
            $pdf->Cell(80, 7, $ligne->getIdProduct()->getNom() ?? 'Produit', 1, 0, 'L', true);
            $pdf->Cell(30, 7, (string)$ligne->getQuantite(), 1, 0, 'C', true);
            $pdf->Cell(35, 7, number_format($ligne->getUnitPrice(), 2, ',', ' ') . ' TND', 1, 0, 'R', true);
            $pdf->Cell(35, 7, number_format($ligne->getSubtotal(), 2, ',', ' ') . ' TND', 1, 1, 'R', true);
        }
        
        // Total
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(17, 24, 39);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(145, 8, 'Total TTC', 1, 0, 'R', true);
        $pdf->Cell(35, 8, number_format((float)$commande->getTotal(), 2, ',', ' ') . ' TND', 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Zone de signature
        $pdf->SetY(180);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'EcoTrip Tunisie - Plateforme de voyages écoresponsables', 0, 1);
        $pdf->Cell(0, 5, 'Reçu généré le ' . $generatedAt->format('d/m/Y H:i'), 0, 1);
        $pdf->Cell(0, 5, 'Ce document a été généré automatiquement et signé électroniquement.', 0, 1);
        
        // Zone de signature avec image si fournie
        $pdf->SetY(200);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(120, 200);
        $pdf->Cell(0, 6, 'Signature du client', 0, 1);

        if ($signatureData && \is_string($signatureData)) {
            $signatureRaw = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData), true);
            if ($signatureRaw !== false && $signatureRaw !== '') {
                $pdf->Image('@' . $signatureRaw, 120, 208, 70, 28, 'PNG');
            }
        }

        // --- GÉNÉRATION DU QR CODE ---
        $qrContent = sprintf(
            "EcoTrip Invoice #%s\nClient: %s\nTotal: %s TND\nDate: %s\nSignature: %s",
            $commande->getIdCommande(),
            $user->getUsername() ?? 'Client',
            $commande->getTotal(),
            $commande->getDateCommande()->format('d/m/Y'),
            $signatureHash
        );

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        $result = $builder->build();

        $qrCodeData = $result->getString();
        // Afficher le QR code en bas à gauche
        $pdf->Image('@'.$qrCodeData, 15, 200, 40, 40, 'PNG');
        $pdf->SetXY(15, 240);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->Cell(40, 5, 'Scanner pour vérifier', 0, 0, 'C');
        // -----------------------------
        
        // Hash de sécurité
        $pdf->SetY(255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(240, 253, 244);
        $pdf->SetDrawColor(16, 185, 129);
        $pdf->Rect(120, 255, 70, 15, 'DF');
        $pdf->SetXY(120, 257);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 4, 'SCELLE ELECTRONIQUEMENT', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(120, 263);
        $pdf->Cell(0, 4, 'Hash: ' . $signatureHash, 0, 1, 'C');
        
        // Générer le PDF
        $filename = sprintf('facture-%s.pdf', $commande->getIdCommande());
        
        return new Response(
            $pdf->Output($filename, 'S'),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    /**
     * Finalise la commande et enregistre les paiements après confirmation (Stripe succès ou paiement classique).
     */
    private function finalizePayment(PaymentMethod $methodEnum, CartService $cartService, EntityManagerInterface $em, $user): ?Commande
    {
        $cart = $cartService->getCart();

        foreach ($cart['hebergements'] ?? [] as $key => $data) {
            $hebergement = $em->getRepository(Hebergement::class)->find($data['id']);
            if ($hebergement) {
                $nights = (int) ($data['nights'] ?? 1);
                $price = ($data['price'] ?? 0) * $nights;
                $dateFrom = isset($data['dateFrom']) ? \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateFrom']) : null;
                $dateTo = isset($data['dateTo']) ? \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateTo']) : null;
                if (!$dateFrom) {
                    $dateFrom = new \DateTimeImmutable();
                    $dateTo = $dateFrom->modify('+' . $nights . ' days');
                } elseif (!$dateTo) {
                    $dateTo = $dateFrom->modify('+' . $nights . ' days');
                }
                $guests = (int) ($data['guests'] ?? 1);
                if ($guests < 1) {
                    $guests = 1;
                }
                $reservation = new Reservation();
                $reservation->setUser($user);
                $reservation->setReservationType(ReservationType::HEBERGEMENT);
                $reservation->setReservationId($data['id']);
                $reservation->setTotalPrice($price);
                $reservation->setDateFrom($dateFrom);
                $reservation->setDateTo($dateTo);
                $reservation->setNumberOfPersons($guests);
                $reservation->setDetails(array_filter(['nights' => $nights, 'guests' => $guests]));
                $em->persist($reservation);
                $payment = new PaymentReservation();
                $payment->setReservation($reservation);
                $payment->setAmount($price);
                $payment->setPaymentMethod($methodEnum);
                $payment->setPaymentStatus(PaymentStatus::COMPLETED);
                $payment->setPaidAt(new \DateTimeImmutable());
                $em->persist($payment);
            }
        }

        foreach ($cart['activities'] ?? [] as $key => $data) {
            $quantity = (int) ($data['quantity'] ?? 1);
            $price = ($data['price'] ?? 0) * $quantity;
            $dateFrom = isset($data['reservedAt']) ? \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['reservedAt']) : null;
            if (!$dateFrom && isset($data['reservedAt'])) {
                $dateFrom = new \DateTimeImmutable($data['reservedAt']);
            }
            if (!$dateFrom) {
                $dateFrom = new \DateTimeImmutable();
            }
            $participants = (int) ($data['participants'] ?? $quantity);
            if ($participants < 1) {
                $participants = 1;
            }
            $reservation = new Reservation();
            $reservation->setUser($user);
            $reservation->setReservationType(ReservationType::ACTIVITY);
            $reservation->setReservationId($data['id']);
            $reservation->setTotalPrice($price);
            $reservation->setDateFrom($dateFrom);
            $reservation->setNumberOfPersons($participants);
            $reservation->setDetails(array_filter(['participants' => $participants]));
            $em->persist($reservation);
            $payment = new PaymentReservation();
            $payment->setReservation($reservation);
            $payment->setAmount($price);
            $payment->setPaymentMethod($methodEnum);
            $payment->setPaymentStatus(PaymentStatus::COMPLETED);
            $payment->setPaidAt(new \DateTimeImmutable());
            $em->persist($payment);
        }

        foreach ($cart['transports'] ?? [] as $key => $data) {
            $quantity = (int) ($data['quantity'] ?? 1);
            $price = ($data['price'] ?? 0) * $quantity;
            $dateFrom = isset($data['travelDate']) ? \DateTimeImmutable::createFromFormat('Y-m-d', $data['travelDate']) : null;
            if (!$dateFrom && isset($data['travelDate'])) {
                $dateFrom = new \DateTimeImmutable($data['travelDate']);
            }
            if (!$dateFrom) {
                $dateFrom = new \DateTimeImmutable();
            }
            $passengers = (int) ($data['passengers'] ?? $quantity);
            if ($passengers < 1) {
                $passengers = 1;
            }
            $details = array_filter(['passengers' => $passengers, 'depart' => $data['depart'] ?? null, 'arrivee' => $data['arrivee'] ?? null]);
            $reservation = new Reservation();
            $reservation->setUser($user);
            $reservation->setReservationType(ReservationType::TRANSPORT);
            $reservation->setReservationId($data['id']);
            $reservation->setTotalPrice($price);
            $reservation->setDateFrom($dateFrom);
            $reservation->setNumberOfPersons($passengers);
            $reservation->setDetails($details);
            $em->persist($reservation);
            $payment = new PaymentReservation();
            $payment->setReservation($reservation);
            $payment->setAmount($price);
            $payment->setPaymentMethod($methodEnum);
            $payment->setPaymentStatus(PaymentStatus::COMPLETED);
            $em->persist($payment);
        }

        $produitsTotal = 0;
        $commande = null;
        foreach ($cart['produits'] ?? [] as $key => $data) {
            $produit = $em->getRepository(Produit::class)->find($data['id']);
            if ($produit && ($data['quantity'] ?? 0) > 0 && $produit->getStock() >= $data['quantity']) {
                $subtotal = ($data['price'] ?? 0) * $data['quantity'];
                $produitsTotal += $subtotal;
                if (!$commande) {
                    $commande = new Commande();
                    $commande->setIdUser($user->getId());
                    $commande->setProduit($produit);
                    $commande->setDateCommande(new \DateTime());
                    $commande->setQuantite(0);
                    $commande->setPrixUnitaire('0');
                    $commande->setTotal('0');
                    $em->persist($commande);
                }
                $ligne = new LigneDeCommande();
                $ligne->setIdProduct($produit);
                $ligne->setIdCommande($commande);
                $ligne->setQuantite($data['quantity']);
                $ligne->setUnitPrice((int) round($data['price']));
                $ligne->setSubtotal((int) round($subtotal));
                $em->persist($ligne);
                $commande->addLigneDeCommande($ligne);
                $produit->setStock($produit->getStock() - $data['quantity']);
            }
        }

        if ($commande) {
            $commande->setQuantite(1);
            $commande->setPrixUnitaire((string) $produitsTotal);
            $commande->setTotal((string) $produitsTotal);
            $paiement = new Paiement();
            $paiement->setCommande($commande);
            $paiement->setMontant((string) $produitsTotal);
            $paiement->setMethodePaiement($methodEnum === PaymentMethod::CARD ? 'Par carte' : ($methodEnum === PaymentMethod::CASH ? 'Par espèces' : 'Par carte'));
            $paiement->setDatePaiement(new \DateTime());
            $em->persist($paiement);
        }

        $em->flush();
        $cartService->clear();

        return $commande;
    }
}
