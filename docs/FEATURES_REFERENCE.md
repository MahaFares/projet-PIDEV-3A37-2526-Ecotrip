# EcoTrip – Features reference

## 1. Réservations (front – fichiers concernés)

### Côté front (site client)

| Fichier | Rôle |
|---------|------|
| **`src/Controller/FrontOffice_Controller/MesReservationsController.php`** | Liste des réservations de l’utilisateur connecté (`/mes-reservations`). Charge les réservations + labels (hébergement / activité / transport). |
| **`templates/FrontOffice/mes_reservations/index.html.twig`** | Page « Mes réservations » : cartes par réservation, type, dates, prix, statut, bouton **Payer** si statut = pending. |
| **`src/Controller/FrontOffice_Controller/PanierController.php`** | Panier, checkout, création des réservations après paiement. Route **POST** `app_panier_restore_reservation` : remet une réservation « en attente » dans le panier pour repayer. |
| **`templates/FrontOffice/panier/index.html.twig`** | Page panier. |
| **`templates/FrontOffice/panier/checkout.html.twig`** | Page checkout. |
| **`templates/FrontOffice/panier/payment.html.twig`** | Page paiement. |
| **`templates/FrontOffice/panier/success.html.twig`** | Page succès après paiement. |
| **`templates/navbar.html.twig`** | Lien « Mes réservations » vers `app_mes_reservations`. |

### Entités / données

| Fichier | Rôle |
|---------|------|
| **`src/Entity/Reservation.php`** | Entité réservation (user, type, reservationId, statut, dates, prix, détails, paymentReservation). |
| **`src/Entity/PaymentReservation.php`** | Lien réservation ↔ paiement. |
| **`src/Entity/Enum/ReservationStatus.php`** | PENDING, CONFIRMED, CANCELLED. |
| **`src/Entity/Enum/ReservationType.php`** | HEBERGEMENT, ACTIVITY, TRANSPORT. |
| **`src/Repository/ReservationRepository.php`** | Requêtes (par user, statut, type, filtres admin). |

### Back-office (admin)

| Fichier | Rôle |
|---------|------|
| **`src/Controller/BackOffice_Controller/ReservationController.php`** | Liste avec filtres, stats, détail, suppression. Réponse JSON pour l’AJAX (partial). |
| **`templates/BackOffice/reservation/index.html.twig`** | Page liste : filtres, stats, tableau (AJAX). |
| **`templates/BackOffice/reservation/_table_rows.html.twig`** | Partial : lignes du tableau (pour l’AJAX). |
| **`templates/BackOffice/reservation/show.html.twig`** | Détail d’une réservation. |
| **`templates/BackOffice/sidebar.html.twig`** | Lien « Réservations » vers `app_reservation_index`. |

---

## 2. Reconnaissance faciale – comment c’est fait

### Principe

- **Enregistrement du visage** : l’utilisateur (connecté) ouvre la caméra, on capture une photo, on l’envoie en base64 au backend. Le backend appelle l’API Hugging Face (extraction de vecteur d’image), stocke le vecteur dans `User.faceDescriptor`.
- **Porte d’accès dashboard** : après connexion (email + mot de passe), un admin qui a un visage enregistré doit passer la vérification faciale pour accéder au dashboard. Une fois validée, un flag en session autorise l’accès.

### Fichiers impliqués

| Fichier | Rôle |
|---------|------|
| **`src/Service/HuggingFaceFaceService.php`** | Appel à `router.huggingface.co` (modèle type ViT, tâche `image-feature-extraction`). Envoie l’image en base64, récupère un vecteur d’embedding. |
| **`src/Service/FaceVerificationService.php`** | Compare deux vecteurs (distance euclidienne pour 128D, similarité cosinus pour autres). Retourne vrai si « même personne » selon le seuil. |
| **`src/Controller/Api/FaceController.php`** | Routes : `POST /api/face/enroll` (enregistrer le visage du user connecté), `POST /api/face/verify-gate` (vérifier le visage pour accès dashboard), `POST /api/face/delete` (supprimer le visage). Constante de session `FACE_GATE_SESSION_KEY`. |
| **`src/Controller/BackOffice_Controller/FaceGateController.php`** | Page **GET** `/face-gate` : popup avec caméra + bouton « Vérifier mon visage ». Si pas de visage enregistré, lien vers Mon Compte. |
| **`src/Controller/BackOffice_Controller/DashboardController.php`** | Avant d’afficher le dashboard : si l’admin a un `faceDescriptor` et que la session n’a pas le flag, redirection vers `app_face_gate`. |
| **`templates/BackOffice/face_gate.html.twig`** | Page de vérification : fond flouté, popup caméra, capture → POST vers `app_face_verify_gate` → redirection vers dashboard en cas de succès. |
| **`templates/user/my_account.html.twig`** | Carte « Reconnaissance faciale » + modal : caméra, capturer, POST vers `app_face_enroll`. |
| **`templates/FrontOffice/compte/mon_compte.html.twig`** | Même principe : enregistrement du visage depuis le front. |
| **`src/Entity/User.php`** | Propriété `faceDescriptor` (JSON / array nullable) pour stocker le vecteur. |
| **`config/services.yaml`** | Injection de `HuggingFaceFaceService` (token + modèle). |
| **`.env`** | `HUGGINGFACE_API_TOKEN`, optionnellement `HUGGINGFACE_FACE_MODEL`. |

### Flux

1. **Enregistrement** : Mon Compte (front ou back) → ouvrir la caméra → Capturer → POST `{ "image": "base64" }` sur `/api/face/enroll` → le serveur appelle Hugging Face, enregistre le vecteur sur le `User`.
2. **Accès dashboard** : Admin se connecte → redirection vers dashboard → si visage enregistré et pas encore vérifié → redirection vers `/face-gate` → caméra → Capturer → POST `{ "image": "base64" }` sur `/api/face/verify-gate` → comparaison avec `User.faceDescriptor` → si OK, session `face_gate_passed` = true → redirection dashboard.

---

## 3. Changement de langue – comment c’est fait

### Principe

- **Côté client** : sélecteur de langue dans la navbar (FR / EN / ES). La langue choisie est stockée en `localStorage` (`eco_trip_lang`). Au changement, le script envoie le **texte visible de la page** à une API de traduction et remplace le contenu par la traduction (pas de rechargement de page).
- **API** : `POST /api/translate` avec `{ text: string|string[], source, target }`. Le backend appelle **MyMemory** (api.mymemory.translated.net) et renvoie le texte (ou un tableau) traduit.

### Fichiers impliqués

| Fichier | Rôle |
|---------|------|
| **`templates/navbar.html.twig`** | Dropdown langue : `<span id="current-lang-text">FR</span>`, options avec `data-lang="fr"`, `data-lang="en"`, `data-lang="es"`. |
| **`public/js/translator.js`** | Au clic sur une langue : sauvegarde dans `localStorage`, mise à jour du libellé (FR/EN/ES). Parcours du DOM (TreeWalker), collecte des nœuds texte (hors script/style/code/…), envoi par lots (BATCH_SIZE) en **POST** à `/api/translate` avec `{ text: [...], source, target }`, remplacement du texte par `result.translations`. |
| **`src/Controller/Api/TranslateController.php`** | Route **POST** `/api/translate`. Reçoit `text` (chaîne ou tableau), `source`, `target`. Si tableau : appelle MyMemory pour chaque chaîne, renvoie `{ success: true, translations: [...] }`. Sinon renvoie `{ success: true, translatedText: "..." }`. |
| **`TranslateController::callMyMemory()`** | Requête HTTP vers `https://api.mymemory.translated.net/get?q=...&langpair=fr|en`, parse `responseData.translatedText`, gère les réponses d’erreur / limite MyMemory. |
| **`config/packages/translation.yaml`** | Config Symfony `translations` (dossier `translations/`, etc.) – utilisé pour les traductions côté serveur si besoin ; le changement de langue « visible » sur le site est géré par le JS + MyMemory. |
| **`templates/base.html.twig` / `templates/basefront.html.twig`** | Inclus ou référence le script `translator.js` pour que le sélecteur de langue fonctionne sur les pages concernées. |

### Flux

1. Utilisateur clique sur une langue (ex. English) dans la navbar.
2. `translator.js` : `currentLang = 'en'`, `localStorage.setItem('eco_trip_lang', 'en')`, mise à jour du libellé affiché.
3. `translatePage(source, target)` : parcourt le DOM, extrait les textes par lots de 20, **POST** `/api/translate` avec `{ text: [...], source: 'fr', target: 'en' }`.
4. `TranslateController` : pour chaque chaîne, appel MyMemory (`langpair=fr|en`), renvoie `{ success: true, translations: [...] }`.
5. Le script remplace chaque nœud texte par la traduction correspondante (et marque les nœuds avec `data-translated` pour éviter de retraduire).

---

*Généré pour référence du projet EcoTrip.*
