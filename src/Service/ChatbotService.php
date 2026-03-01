<?php

namespace App\Service;

use App\Repository\HebergementRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    private HttpClientInterface $httpClient;
    private HebergementRepository $hebergementRepo;
    private string $aiProvider;
    private string $openaiApiKey;
    private string $openaiModel;
    private string $huggingfaceApiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        HebergementRepository $hebergementRepo,
        string $aiProvider,
        string $openaiApiKey = '',
        string $openaiModel = 'gpt-4o',
        string $huggingfaceApiKey = ''
    ) {
        $this->httpClient = $httpClient;
        $this->hebergementRepo = $hebergementRepo;
        $this->aiProvider = $aiProvider;
        $this->openaiApiKey = $openaiApiKey;
        $this->openaiModel = $openaiModel;
        $this->huggingfaceApiKey = $huggingfaceApiKey;
    }

    /**
     * Répondre à une question utilisateur
     */
    public function repondreQuestion(string $questionUtilisateur, array $historique = []): array
    {
        try {
            // 1. Récupérer les hébergements disponibles
            $hebergements = $this->hebergementRepo->findBy(['actif' => true]);

            // 2. Construire le contexte
            $contexte = $this->construireContexte($hebergements);

            // 3. Appeler l'IA selon le provider configuré
            if ($this->aiProvider === 'openai') {
                $reponse = $this->appellerOpenAI($contexte, $historique, $questionUtilisateur);
            } else {
                $reponse = $this->appellerHuggingFace($contexte, $historique, $questionUtilisateur);
            }

            return [
                'success' => true,
                'response' => $reponse,
                'provider' => $this->aiProvider
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => "Désolé, je rencontre un problème technique. Pouvez-vous reformuler votre question ?",
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Construire le contexte avec les hébergements
     */
    private function construireContexte(array $hebergements): string
    {
        $contexte = "=== BASE DE DONNÉES ECOTRIP ===\n\n";
        $contexte .= "Hébergements éco-responsables disponibles en Tunisie :\n\n";

        foreach ($hebergements as $h) {
            $contexte .= "🏡 " . $h->getNom() . "\n";
            $contexte .= "   ID: " . $h->getId() . "\n";
            $contexte .= "   📍 Ville: " . $h->getVille() . "\n";
            
            if ($h->getCategorie()) {
                $contexte .= "   🏷️ Catégorie: " . $h->getCategorie()->getNom() . "\n";
            }
            
            $contexte .= "   ⭐ Étoiles: " . $h->getNbEtoiles() . "\n";
            
            if ($h->getLabelEco()) {
                $contexte .= "   🌿 Label écologique: " . $h->getLabelEco() . "\n";
            }

            // Note moyenne si disponible
            if (method_exists($h, 'getNoteMoyenne') && $h->getNoteMoyenne()) {
                $contexte .= "   ⭐ Note clients: " . $h->getNoteMoyenne() . "/5";
                $contexte .= " (" . $h->getNombreAvis() . " avis)\n";
            }

            // Prix
            $chambres = $h->getChambres();
            if ($chambres->count() > 0) {
                $chambresDispos = $chambres->filter(fn($c) => $c->isDisponible());
                if ($chambresDispos->count() > 0) {
                    $prix = array_map(fn($c) => $c->getPrixParNuit(), $chambresDispos->toArray());
                    $prixMin = min($prix);
                    $prixMax = max($prix);
                    
                    if ($prixMin === $prixMax) {
                        $contexte .= "   💰 Prix: " . $prixMin . "€/nuit\n";
                    } else {
                        $contexte .= "   💰 Prix: de " . $prixMin . "€ à " . $prixMax . "€/nuit\n";
                    }
                    
                    $contexte .= "   🛏️ Chambres disponibles: " . $chambresDispos->count() . "\n";
                }
            }

            // Équipements
            $equipements = $h->getEquipements();
            if ($equipements->count() > 0) {
                $listeEquip = array_map(fn($e) => $e->getNom(), $equipements->toArray());
                $contexte .= "   ✨ Équipements: " . implode(', ', $listeEquip) . "\n";
            }

            $contexte .= "   📝 Description: " . substr($h->getDescription(), 0, 200) . "...\n";
            $contexte .= "   🔗 Lien: https://ecotrip.com/hebergement/" . $h->getId() . "\n";
            $contexte .= "\n";
        }

        return $contexte;
    }

    /**
     * Appeler OpenAI GPT-4
     */
    private function appellerOpenAI(string $contexte, array $historique, string $question): string
    {
        $messages = [];

        // System prompt
        $messages[] = [
            'role' => 'system',
            'content' => "Tu es un assistant de voyage éco-responsable pour EcoTrip Tunisie.

PERSONNALITÉ:
- Chaleureux et professionnel
- Expert en tourisme durable
- Utilise des emojis (🌿🏡⭐💚) pour être agréable

RÈGLES:
1. Réponds en français uniquement
2. Maximum 200 mots par réponse
3. Base-toi UNIQUEMENT sur les données fournies
4. Si tu ne sais pas, dis-le honnêtement
5. Propose toujours des actions concrètes
6. Mets en avant les labels écologiques
7. Donne les liens directs vers les hébergements

DONNÉES DISPONIBLES:
$contexte

TON OBJECTIF: Aider l'utilisateur à trouver l'hébergement parfait pour son voyage éco-responsable en Tunisie."
        ];

        // Historique
        foreach ($historique as $msg) {
            $messages[] = ['role' => 'user', 'content' => $msg['user']];
            $messages[] = ['role' => 'assistant', 'content' => $msg['bot']];
        }

        // Question actuelle
        $messages[] = ['role' => 'user', 'content' => $question];

        // Appel API
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->openaiModel,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
                'presence_penalty' => 0.6,
                'frequency_penalty' => 0.3
            ],
            'timeout' => 30
        ]);

        $data = $response->toArray();

        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }

        throw new \Exception('Réponse invalide de OpenAI');
    }

    /**
     * Appeler Hugging Face (Mistral)
     */
    private function appellerHuggingFace(string $contexte, array $historique, string $question): string
    {
        // Construire le prompt complet pour Mistral
        $prompt = "<s>[INST] Tu es un assistant de voyage éco-responsable pour EcoTrip Tunisie.\n\n";
        $prompt .= "Réponds en français, sois chaleureux et utilise des emojis.\n";
        $prompt .= "Maximum 200 mots.\n\n";
        $prompt .= "Données disponibles:\n" . $contexte . "\n\n";
        
        // Ajouter l'historique
        if (!empty($historique)) {
            $prompt .= "Conversation précédente:\n";
            foreach ($historique as $msg) {
                $prompt .= "Utilisateur: " . $msg['user'] . "\n";
                $prompt .= "Assistant: " . $msg['bot'] . "\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Question actuelle: " . $question . " [/INST]";

        // Appel API
        $response = $this->httpClient->request('POST', 
            'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->huggingfaceApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 400,
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                        'return_full_text' => false
                    ]
                ],
                'timeout' => 60
            ]
        );

        $data = $response->toArray();

        if (isset($data[0]['generated_text'])) {
            $reponse = trim($data[0]['generated_text']);
            
            // Nettoyer la réponse
            $reponse = str_replace('[/INST]', '', $reponse);
            $reponse = trim($reponse);
            
            return $reponse;
        }

        // Si le modèle est en train de charger
        if (isset($data['error']) && strpos($data['error'], 'loading') !== false) {
            return "⏳ Le modèle IA se charge... Cela peut prendre 20-30 secondes. Merci de patienter et de réessayer dans un instant ! 🌿";
        }

        throw new \Exception('Réponse invalide de Hugging Face');
    }

    /**
     * Obtenir le provider actif
     */
    public function getProvider(): string
    {
        return $this->aiProvider;
    }
}