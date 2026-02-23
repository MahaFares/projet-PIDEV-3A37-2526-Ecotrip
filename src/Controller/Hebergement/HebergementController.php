<?php

namespace App\Controller\Hebergement;

use App\Entity\Hebergement;
use App\Form\HebergementType;
use App\Repository\ChambreRepository;
use App\Repository\HebergementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\CloudinaryService;
use App\Service\GeminiService;

#[Route('/hebergement')]
final class HebergementController extends AbstractController
{
    private const UPLOAD_DIR = 'uploads/hebergements';

    #[Route('', name: 'app_hebergement_index', methods: ['GET'])]
    public function index(Request $request, HebergementRepository $hebergementRepository, ChambreRepository $chambreRepository, PaginatorInterface $paginator): Response
    {
        $q = $request->query->get('q');
        $minStars = $request->query->get('minStars');
        $maxStars = $request->query->get('maxStars');
        $active = $request->query->get('active');

        $minStars = $minStars !== null && $minStars !== '' ? (int) $minStars : null;
        $maxStars = $maxStars !== null && $maxStars !== '' ? (int) $maxStars : null;
        $active = ($active === '1' ? true : ($active === '0' ? false : null));

        $qb = $hebergementRepository->getQueryBuilderByFilters($q, $minStars, $maxStars, $active);
        $hebergements = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            5
        );

        $stats = [
            'total_hebergements' => $hebergementRepository->count([]),
            'total_chambres' => $chambreRepository->count([]),
        ];

        $hebergementByCategory = $hebergementRepository->getCountByCategory();
        $chartPieLabels = array_keys($hebergementByCategory);
        $chartPieData = array_values($hebergementByCategory);

        $chambresPerHebergement = $hebergementRepository->getChambresCountPerHebergement(5);
        $chartBarLabels = array_map(fn ($r) => $r['nom'], $chambresPerHebergement);
        $chartBarData = array_map(fn ($r) => $r['count'], $chambresPerHebergement);

        return $this->render('HebergementTemplate/hebergement/index.html.twig', [
            'hebergements' => $hebergements,
            'stats' => $stats,
            'chartPieLabels' => $chartPieLabels,
            'chartPieData' => $chartPieData,
            'chartBarLabels' => $chartBarLabels,
            'chartBarData' => $chartBarData,
            'filters' => [
                'q' => $q,
                'minStars' => $minStars,
                'maxStars' => $maxStars,
                'active' => $active,
            ],
        ]);
    }

    #[Route('/new', name: 'app_hebergement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, CloudinaryService $cloudinaryService): Response
    {
        $hebergement = new Hebergement();
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imagePrincipale')->getData();
            if (!$imageFile instanceof UploadedFile) {
                $formFiles = $request->files->get($form->getName());
                if (\is_array($formFiles) && isset($formFiles['imagePrincipale']) && $formFiles['imagePrincipale'] instanceof UploadedFile) {
                    $imageFile = $formFiles['imagePrincipale'];
                }
            }
            if (!$imageFile instanceof UploadedFile) {
                $imageFile = $this->findFirstUploadedFile($request->files->all());
            }

            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                try {
                    $imageUrl = $cloudinaryService->uploadImage($imageFile, 'hebergements');
                    $hebergement->setImagePrincipale($imageUrl);
                    $this->addFlash('success', 'Image enregistrée sur Cloudinary (dossier hebergements). Vérifiez dans Media Library.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur Cloudinary: ' . $e->getMessage());
                }
            }
            $hebergement->setActif($hebergement->isActif() ?? true);
            $entityManager->persist($hebergement);
            $entityManager->flush();

            return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        // Form submitted but invalid (e.g. PHP cleared POST/FILES due to post_max_size)
        if ($form->isSubmitted() && !$form->isValid() && $request->request->count() === 0 && $request->getMethod() === 'POST') {
            $this->addFlash('error', 'Aucune donnée reçue. Si vous aviez choisi une image, augmentez post_max_size et upload_max_filesize dans php.ini (ex: 20M).');
        }

        return $this->render('HebergementTemplate/hebergement/new.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_hebergement_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Hebergement $hebergement): Response
    {
        return $this->render('HebergementTemplate/hebergement/show.html.twig', [
            'hebergement' => $hebergement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_hebergement_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager, SluggerInterface $slugger, CloudinaryService $cloudinaryService): Response
    {
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imagePrincipale')->getData();
            if (!$imageFile instanceof UploadedFile) {
                $formFiles = $request->files->get($form->getName());
                if (\is_array($formFiles) && isset($formFiles['imagePrincipale']) && $formFiles['imagePrincipale'] instanceof UploadedFile) {
                    $imageFile = $formFiles['imagePrincipale'];
                }
            }
            if (!$imageFile instanceof UploadedFile) {
                $imageFile = $this->findFirstUploadedFile($request->files->all());
            }
            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                try {
                    $imageUrl = $cloudinaryService->uploadImage($imageFile, 'hebergements');
                    $hebergement->setImagePrincipale($imageUrl);
                    $this->addFlash('success', 'Image mise à jour sur Cloudinary (dossier hebergements).');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur Cloudinary: ' . $e->getMessage());
                }
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('HebergementTemplate/hebergement/edit.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_hebergement_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hebergement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($hebergement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Save uploaded file to public/uploads/hebergements and return path for DB (e.g. uploads/hebergements/abc.jpg).
     */
    private function saveUploadedFile(UploadedFile $file, SluggerInterface $slugger): ?string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $publicDir = $projectDir . '/public';
        $targetDir = $publicDir . '/' . self::UPLOAD_DIR;

        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0755, true)) {
                return null;
            }
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName);
        $extension = $file->guessExtension() ?: 'jpg';
        $fileName = $safeName . '-' . uniqid('', true) . '.' . $extension;

        try {
            $file->move($targetDir, $fileName);
            return self::UPLOAD_DIR . '/' . $fileName;
        } catch (FileException $e) {
            return null;
        }
    }

    /**
     * Recursively find the first valid UploadedFile in the request files array.
     */
    private function findFirstUploadedFile(array $files): ?UploadedFile
    {
        foreach ($files as $value) {
            if ($value instanceof UploadedFile && $value->isValid()) {
                return $value;
            }
            if (\is_array($value)) {
                $found = $this->findFirstUploadedFile($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }


    #[Route('/ai/generate-description', name: 'app_hebergement_ai_description', methods: ['POST'])]
    public function generateDescription(Request $request, GeminiService $geminiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nom = $data['nom'] ?? 'un hébergement';
        $ville = $data['ville'] ?? 'Tunisie';
        $categorie = $data['categorie'] ?? 'Standard';
        $equipements = $data['equipements'] ?? 'Non spécifié';
        $etoiles = $data['nbEtoiles'] ?? 'Non spécifié';

        $prompt = "Rédige une description attrayante, professionnelle et orientée écologie (environ 80-100 mots) pour cet hébergement :
        Nom : $nom
        Ville : $ville
        Catégorie : $categorie
        Nombre d'étoiles : $etoiles
        Équipements : $equipements
        Mets en valeur le confort et l'aspect durable (EcoTrip). Utilise un ton accueillant.";

        $description = $geminiService->generateText($prompt);

        return $this->json(['description' => $description]);
    }
}
