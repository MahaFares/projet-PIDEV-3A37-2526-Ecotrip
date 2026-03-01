## Technical Summary – Métiers Avancés Implementation

### Introduction

This document presents an exhaustive overview of the advanced business functionalities (“métiers avancés”) implemented in the project.  
Each feature is classified into one of the three requested categories:

- **API Integration**
- **External Bundle / Library**
- **Advanced Custom Functionality**

For each métier avancé, the following aspects are documented:

1. Functionality name  
2. Functional description  
3. Technical implementation (files, data flow, Symfony mechanisms)  
4. Category  
5. Justification of category  
6. Technologies used  
7. Suggested oral explanation for the soutenance  

---

## I. API Integration

### 1. Face Recognition Enrollment & Admin “Face Gate” Security

**1️⃣ Functionality Name**  
Face recognition–based access gate for back-office administration

**2️⃣ Functional Description**  
Authenticated administrators can enroll their facial profile and must pass a real‑time face verification step (“face gate”) before accessing sensitive back‑office dashboards.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Api/FaceController.php`
  - `src/Controller/BackOffice_Controller/FaceGateController.php`
  - `src/Service/HuggingFaceFaceService.php`
  - `src/Service/FaceVerificationService.php`
  - `src/Entity/User.php` (field storing face descriptor/embedding)
  - `templates/BackOffice/face_gate.html.twig`
  - `public/js/face-auth.js`, `public/js/face-api.min.js`
- **Data flow**
  - On enrollment, the browser uses **face-api.js** (a JS library) to detect and compute a face descriptor from the user’s webcam stream.
  - The descriptor is either computed fully in the browser, or refined via calls to **Hugging Face Inference API** (`HuggingFaceFaceService`), and then stored on the `User` entity as a JSON field.
  - When an admin attempts to access the dashboard, `FaceGateController` checks for a session flag (e.g. `FACE_GATE_SESSION_KEY`). If not verified, the user is redirected to a dedicated face gate page.
  - The face gate page captures a live image/descriptor via `face-auth.js` and sends it to `FaceController` which delegates to `FaceVerificationService`.
  - `FaceVerificationService` compares the live descriptor with the stored descriptor using vector distance (e.g. cosine similarity or Euclidean distance) and returns a boolean.
  - On success, the controller sets the face-gate session key and grants access to the dashboard; on failure, the admin remains blocked.
- **Symfony mechanisms**
  - Standard controllers returning JSON responses for the API part.
  - Custom **services** (`HuggingFaceFaceService`, `FaceVerificationService`) injected via dependency injection.
  - Use of HTTP client to call the external Hugging Face API.
  - Session management for the additional face‑gate state.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
The core métier relies on calling an **external AI service (Hugging Face Inference API)** to work with facial embeddings, combined with an advanced browser‑side library (face-api.js). The essential verification logic depends on that external API integration.

**6️⃣ Technologies Used**  
Symfony (Controllers, Services), Doctrine (User entity), Symfony HTTP Client, Hugging Face Inference API, JavaScript, face-api.js, Twig, PHP sessions.

**7️⃣ Validation Explanation (Oral Version)**  
“In addition to classic password authentication, I implemented a second security layer based on face recognition. The browser captures a facial descriptor using face‑api.js, and the backend uses the Hugging Face API and a custom verification service to compare this descriptor with the one stored on the user entity. Only if the similarity score is sufficient do we set a dedicated session key that allows access to the back‑office dashboard, which significantly strengthens protection of the administration area.”

---

### 2. Cloudinary-Hosted Media Uploads

**1️⃣ Functionality Name**  
Cloudinary‑based media storage for images

**2️⃣ Functional Description**  
When users or admins upload images (profile picture, products, hébergements, activities), the files are sent to Cloudinary and stored remotely, and the application keeps only secure URLs.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Service/CloudinaryService.php`
  - Entities with image fields (e.g. `User.image`, product or hebergement image properties)
  - Forms and controllers that call the service during create/edit actions
- **Data flow**
  - During entity creation or update, the controller retrieves the uploaded `UploadedFile` from the form.
  - The file is passed to `CloudinaryService`, which wraps the `cloudinary/cloudinary_php` SDK.
  - The service handles upload to Cloudinary and returns a hosted URL (and possibly a public ID).
  - The entity’s image field is updated with this URL and persisted through Doctrine.
  - Deletion or replacement of images can trigger Cloudinary deletion via the same service.
- **Symfony mechanisms**
  - Dependency injection of `CloudinaryService` into controllers.
  - Use of Symfony Forms and `UploadedFile`.
  - Doctrine ORM for persisting the Cloudinary URL.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
The core functionality is delegating file storage and transformation to the **Cloudinary HTTP API**, accessed through the PHP SDK. The métier is essentially an integration with this external service.

**6️⃣ Technologies Used**  
Symfony (Forms, Controllers, Services), Doctrine ORM, Cloudinary PHP SDK, Cloudinary REST API, Twig.

**7️⃣ Validation Explanation (Oral Version)**  
“For image management I externalized storage to Cloudinary. On each upload, the controller sends the `UploadedFile` to a dedicated `CloudinaryService` that calls Cloudinary’s API and returns a secure URL. This way, the application database only stores the URL while Cloudinary manages hosting, transformations, and CDN delivery, which improves both performance and maintainability.”

---

### 3. AI-Driven EcoTrip Travel Assistant (Gemini Chat API)

**1️⃣ Functionality Name**  
EcoTrip AI assistant for eco‑friendly travel planning

**2️⃣ Functional Description**  
Users can describe their eco‑trip preferences (destination, budget, duration, style), and the system returns a complete AI‑generated travel plan: itinerary, activities, transport and eco‑friendly recommendations.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Api/EcoTripAiController.php`
  - `src/Service/GeminiService.php`
  - Twig template providing the front‑end UI for the assistant (e.g. `templates/api/eco_trip_ai/index.html.twig`)
- **Data flow**
  - The front‑end sends a JSON POST request to an API route (e.g. `/api/ecotrip-ai`) with structured user preferences.
  - `EcoTripAiController` validates input and builds a rich French prompt guiding the AI: output must be in strict JSON with sections like itinerary, recommendations, next steps.
  - The controller calls `GeminiService`, which encapsulates HTTP requests to **Google Gemini API** (`generativelanguage.googleapis.com`) using Symfony HTTP Client.
  - The service returns the AI response as text; the controller post‑processes it (cleanup of extraneous characters, JSON decoding) and validates the expected schema.
  - On success, the controller returns a JSON response to the front‑end; on error, it returns clear error messages and fallbacks.
- **Symfony mechanisms**
  - JSON API controller using `JsonResponse`.
  - Custom service encapsulating external API calls.
  - Centralized exception handling and input validation.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
The métier is built around a **large language model API (Gemini)**, with the business value depending on the correctness and robustness of that external AI integration.

**6️⃣ Technologies Used**  
Symfony (API controllers, services), Symfony HTTP Client, Google Gemini API, JSON, Twig, JavaScript (frontend calls).

**7️⃣ Validation Explanation (Oral Version)**  
“I developed an ‘EcoTrip AI’ assistant that connects to Google’s Gemini API. The controller takes structured user preferences, crafts a detailed prompt in French and asks Gemini to return a strict JSON structure describing the trip. A dedicated service encapsulates the external call and the controller cleans and validates the JSON before sending it back to the interface, which allows us to build a reliable AI‑driven travel planner.”

---

### 4. AI-Based Product Recommendations (“You May Also Like”)

**1️⃣ Functionality Name**  
AI‑powered product recommendation engine for the boutique

**2️⃣ Functional Description**  
On product detail pages, the user sees AI‑generated “you may also like” suggestions based on product characteristics and catalog context.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Service/ProductRecommendationService.php`
  - `src/Repository/ProduitRepository.php` (custom selectors / fallback queries)
  - Product detail templates (e.g. `templates/FrontOffice/boutique/produit/detail.html.twig`)
- **Data flow**
  - For a given product, `ProductRecommendationService` builds a candidate list from the database via `ProduitRepository` (same category, price range, etc.).
  - It sends these candidates and context to **Gemini** via HTTP, requesting a list of recommended product IDs in raw JSON form.
  - The service parses the response, validates that each returned ID exists and belongs to the candidate set, and preserves the ordering suggested by the AI.
  - If the AI call fails or the JSON is invalid, the service falls back to a deterministic recommendation strategy directly in Doctrine.
  - The final list of `Produit` entities is passed to the Twig template for rendering.
- **Symfony mechanisms**
  - Custom service for orchestration.
  - Repository methods for data access and fallback logic.
  - Integration with Twig to display recommendations.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
Even though there is significant custom logic around candidates and fallbacks, the core recommendation step depends on the **external Gemini API**, making this primarily an API Integration métier.

**6️⃣ Technologies Used**  
Symfony (Services, Repositories), Doctrine ORM, Symfony HTTP Client, Google Gemini API, Twig.

**7️⃣ Validation Explanation (Oral Version)**  
“For product recommendations I implemented a hybrid system: the service first selects candidate products from Doctrine, then calls Gemini to rank them and return a list of product IDs. The service validates these IDs and preserves the AI’s ordering; if the API is unavailable, it falls back to a classic category‑based strategy. This creates a robust ‘you may also like’ section powered by AI but secured with deterministic fallbacks.”

---

### 5. AI-Assisted Activity Description Generation

**1️⃣ Functionality Name**  
Automatic AI generation of marketing descriptions for activities

**2️⃣ Functional Description**  
When creating or editing an activity, administrators can click a button to automatically generate a rich marketing description in French based on structured activity metadata.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Activity/ActivityController.php` (JSON endpoint, e.g. `app_activity_ai_description`)
  - `src/Service/GeminiService.php`
  - `templates/ActivityTemplate/activity/_form.html.twig` (button and JavaScript integration)
- **Data flow**
  - The activity form template includes a JS hook or button to send current form fields (title, category, location, duration, price, participants) to a dedicated JSON route.
  - The controller receives these parameters, constructs a precise instruction prompt for Gemini (tone, language, length, marketing style).
  - It calls `GeminiService`, obtains the generated text, and returns it in a `JsonResponse`.
  - JavaScript in the form injects the returned text into the description textarea so the admin can edit it before saving.
- **Symfony mechanisms**
  - AJAX endpoint in a regular controller.
  - Service reuse (`GeminiService`) across several AI features.
  - Form enhancement via JSON API.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
This métier is centered on delegating description generation to an **external AI API**, encapsulated in `GeminiService`, so it clearly fits into API Integration.

**6️⃣ Technologies Used**  
Symfony (Controllers, Services), Symfony HTTP Client, Google Gemini API, Twig, JavaScript (AJAX).

**7️⃣ Validation Explanation (Oral Version)**  
“In the activity module I added a productivity feature for administrators: a button that calls the Gemini API to generate a marketing description based on the form fields. The controller exposes a JSON endpoint that reuses the same `GeminiService`; the client receives the generated text and injects it into the description textarea, which speeds up content creation while keeping final control on the admin side.”

---

### 6. Dynamic Multilingual Translation via MyMemory API

**1️⃣ Functionality Name**  
Client‑side dynamic translation of interface text using MyMemory

**2️⃣ Functional Description**  
Users can switch the interface language, and visible text on the page is dynamically translated without reloading, using an external translation API with caching.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `public/js/translator.js`
  - `src/Controller/Api/TranslateController.php`
  - `templates/FrontOffice/home/index.html.twig` (language selector, root wrapper `#ecotrip-translate-root`)
- **Data flow**
  - The Twig template wraps the main content in a root element and provides a language selector.
  - `translator.js` scans the DOM, extracts visible text nodes, and groups them into batches.
  - For each batch, it sends a POST request to `/api/translate` containing source texts and target language.
  - `TranslateController` calls the **MyMemory Translation API** (`api.mymemory.translated.net`) via HTTP, handling quotas, potential errors and rate limiting.
  - The controller returns translated text, which `translator.js` applies back to the DOM nodes.
  - A `localStorage` or in‑memory cache is used to avoid translating the same text repeatedly.
- **Symfony mechanisms**
  - JSON API endpoint using Symfony HTTP Client.
  - Separation between server‑side proxy and client‑side DOM manipulation.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
The core of this métier is interfacing with **MyMemory’s HTTP API** for translation, with the frontend acting as a dynamic client; it is essentially an API‑driven translation system.

**6️⃣ Technologies Used**  
Symfony (Controllers), Symfony HTTP Client, MyMemory API, JavaScript (DOM manipulation, Fetch API), Twig, localStorage.

**7️⃣ Validation Explanation (Oral Version)**  
“To offer multi‑language support without duplicating templates, I created a dynamic translation system. A JavaScript module scans all visible text nodes and sends them in batches to a Symfony API endpoint, which proxies the request to the MyMemory translation service. The translated sentences are then applied back to the DOM and cached in `localStorage`, providing a fluid multilingual experience.”

---

### 7. Stripe Card Payments for Unified Cart

**1️⃣ Functionality Name**  
Stripe‑based payment for a unified multi‑domain cart

**2️⃣ Functional Description**  
Users can pay their mixed cart (hébergements, activities, transports, products) by credit card via Stripe Checkout, and the system automatically finalizes reservations, orders and payments upon success.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/FrontOffice_Controller/PanierController.php`
  - `config/packages/stripe.yaml`
  - Templates: `templates/FrontOffice/panier/index.html.twig`, `payment.html.twig`, `success.html.twig`
  - `CartService` and domain entities: `Reservation`, `PaymentReservation`, `Commande`, `LigneDeCommande`, `Paiement`
- **Data flow**
  - The user builds a cart using AJAX actions (see dedicated métier below).
  - When selecting card payment, `PanierController` reads cart contents from the session/service and builds Stripe `line_items` with correct amounts and descriptions.
  - The controller sets the secret key from `.env` and creates a **Stripe Checkout Session** (mode `payment`), receiving a session URL.
  - The browser is redirected to Stripe’s hosted payment page.
  - After payment, Stripe redirects to a success route; the controller verifies the session status, converts cart items into `Reservation` and e‑commerce entities, persists them via Doctrine, and clears the cart.
- **Symfony mechanisms**
  - External SDK configuration through `stripe.yaml`.
  - Use of a dedicated controller action for payment initialization and success handling.
  - Integration with a domain‑centric cart and reservation model.

**4️⃣ Category**  
API Integration

**5️⃣ Justification**  
This métier is intrinsically tied to **Stripe’s payment API**, using its PHP SDK and Checkout Session workflow; the key added value is the correct orchestration of this external payment service.

**6️⃣ Technologies Used**  
Symfony (Controllers, Services), Stripe PHP SDK, Doctrine ORM, Twig, JavaScript (for initiating payment and handling redirects), Environment configuration (`.env`).

**7️⃣ Validation Explanation (Oral Version)**  
“I centralized all items—hébergement, activité, transport and boutique products—into a single cart and integrated Stripe Checkout to process card payments. The controller translates the cart into Stripe `line_items`, redirects the user to the hosted payment page, and on the success callback it creates the corresponding `Reservation`, `Commande`, `LigneDeCommande` and `Paiement` entities. This ensures transactional consistency between the external payment system and our internal domain model.”

---

## II. External Bundle / Library

### 8. Activity Brochure PDF Export (Dompdf)

**1️⃣ Functionality Name**  
PDF brochure generation for an individual activity

**2️⃣ Functional Description**  
From the activity detail or admin interface, users can generate a PDF brochure containing all the information about a specific activity (description, price, images, schedule).

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Activity/ActivityController.php` (e.g. `generatePdf` action)
  - `templates/ActivityTemplate/activity/pdf.html.twig`
  - Composer dependency: `dompdf/dompdf`
- **Data flow**
  - The controller fetches the `Activity` entity by ID and passes it to a dedicated Twig template designed for printing (layout, images, sections).
  - The HTML generated by Twig is given to Dompdf, configured for HTML5 and CSS3 support (fonts, page size).
  - Dompdf renders the document and returns binary PDF output.
  - The response is returned as a `StreamedResponse` or `BinaryFileResponse` with appropriate headers for download/open in browser.
- **Symfony mechanisms**
  - Controller action with response type PDF.
  - Usage of Twig templates as the canonical source of PDF layout.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
The métier is essentially an integration and orchestration of the **Dompdf** library for PDF generation, directly corresponding to the “External Bundle / Library” category.

**6️⃣ Technologies Used**  
Symfony (Controller, Twig), Dompdf library, Doctrine (Activity entity), HTTP responses for file download.

**7️⃣ Validation Explanation (Oral Version)**  
“For activities I provide a PDF brochure export. A controller action renders the activity into a dedicated Twig template and passes the HTML to Dompdf, which is an external PDF generation library. The resulting PDF is streamed back to the browser, allowing users to download or print a nicely formatted brochure.”

---

### 9. Email Verification after Registration (VerifyEmailBundle)

**1️⃣ Functionality Name**  
Email verification workflow for new accounts

**2️⃣ Functional Description**  
After registration, the user receives a confirmation email containing a secure link. The account is only fully activated once the user clicks the link.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Security/EmailVerifier.php`
  - `src/Controller/User/RegistrationController.php`
  - Templates: `templates/user/registration/register.html.twig`, views showing verification status
  - Composer dependency: `symfonycasts/verify-email-bundle`
- **Data flow**
  - On successful registration, `RegistrationController` uses `EmailVerifier` to generate a **signed URL** tied to the user ID and email.
  - A `TemplatedEmail` is sent to the user with that link.
  - When the user clicks the link, a route handled by `EmailVerifier`’s logic validates the signature and expiry.
  - If valid, `User.isVerified` is set to `true` and persisted.
- **Symfony mechanisms**
  - Integration of SymfonyCasts VerifyEmailBundle.
  - Custom `EmailVerifier` service wrapping the bundle for cleaner use in controllers.
  - Use of security tokens/signatures.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
The core verification logic, including signed URL generation and validation, comes from **VerifyEmailBundle**, making this feature mainly an integration of an external bundle.

**6️⃣ Technologies Used**  
Symfony (Controllers, Security), VerifyEmailBundle, Symfony Mailer, Twig, Doctrine ORM.

**7️⃣ Validation Explanation (Oral Version)**  
“To secure new accounts, I implemented an email verification flow using SymfonyCasts VerifyEmailBundle. The registration controller generates a signed URL via an `EmailVerifier` service, sends it by email, and the bundle validates the link when the user clicks it. Only then is the `isVerified` flag set on the user, which protects the application against bogus or disposable accounts.”

---

### 10. Password Reset Flow (ResetPasswordBundle)

**1️⃣ Functionality Name**  
Token‑based password reset via email

**2️⃣ Functional Description**  
Users who forget their password can request a reset link by email, then define a new password via a secure, time‑limited form.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/User/ResetPasswordController.php`
  - `src/Entity/ResetPasswordRequest.php`
  - `src/Repository/ResetPasswordRequestRepository.php`
  - `config/packages/reset_password.yaml`
  - Templates: `templates/reset_password/*.html.twig`
  - Composer dependency: SymfonyCasts ResetPasswordBundle
- **Data flow**
  - The user submits their email on a reset form; the controller checks the user exists and uses the bundle to create a `ResetPasswordRequest` entity with a token.
  - The bundle sends a reset email containing a secure URL including the token.
  - When the user clicks the link, the controller/bundle validate the token (expiry, usage).
  - On success, the user can enter a new password; the controller encodes it and clears the reset request.
- **Symfony mechanisms**
  - Heavy use of ResetPasswordBundle for token generation, storage and validation.
  - Standard Symfony forms and validators for password fields.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
The métier is built on top of **ResetPasswordBundle**, which manages token entities, email content and validation rules.

**6️⃣ Technologies Used**  
Symfony (Controllers, Forms, Security), SymfonyCasts ResetPasswordBundle, Doctrine ORM, Twig, Symfony Mailer.

**7️⃣ Validation Explanation (Oral Version)**  
“For password recovery I adopted SymfonyCasts ResetPasswordBundle. The controller delegates token creation and persistence to the bundle, which also generates a secure reset link. When the user follows the link, the token is validated and, if valid, a standard form lets them define a new password that is encoded and saved, providing a robust and secure reset mechanism.”

---

### 11. Email Sending Infrastructure (Symfony Mailer & Google Mailer)

**1️⃣ Functionality Name**  
Centralized email sending for verification and password reset

**2️⃣ Functional Description**  
The application sends transactional emails (verification, password reset, possibly notifications) using Symfony’s Mailer component, with the option to route via Google’s SMTP or API.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Security/EmailVerifier.php` (verification emails)
  - `src/Controller/User/ResetPasswordController.php` (reset emails)
  - `config/packages/mailer.yaml`
  - Composer dependencies: `symfony/mailer`, `symfony/google-mailer`
- **Data flow**
  - Business processes (registration, password reset) build `TemplatedEmail` instances with Twig templates and context.
  - Depending on configuration, the Mailer uses Google Mailer transport to send emails via Gmail / Google Workspace.
  - Error handling and logging follow Symfony Mailer conventions.
- **Symfony mechanisms**
  - Use of `MailerInterface` with DI.
  - Templated emails with Twig.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
Here the advanced métier is the proper integration and configuration of **Symfony Mailer and Google Mailer**, external components dedicated to email transport.

**6️⃣ Technologies Used**  
Symfony Mailer, Google Mailer, Twig (email templates), Controllers/Services, `.env` configuration.

**7️⃣ Validation Explanation (Oral Version)**  
“I centralized all transactional emails on Symfony Mailer, with a Google Mailer transport for sending messages. Both the registration and password reset flows build `TemplatedEmail` objects that embed Twig templates, which allows me to maintain email design separately from the controllers.”

---

### 12. CAPTCHA Integration (GregwarCaptchaBundle)

**1️⃣ Functionality Name**  
CAPTCHA protection on sensitive forms

**2️⃣ Functional Description**  
Key forms such as login or registration are protected with an image CAPTCHA to prevent automated abuse and brute‑force attempts.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `config/packages/gregwar_captcha.yaml`
  - Login and registration form types and templates:
    - e.g. `templates/user/security/login.html.twig`
    - `templates/user/registration/register.html.twig`
  - Composer dependency: `gregwar/captcha-bundle`
- **Data flow**
  - Forms include a `CaptchaType` field provided by the bundle.
  - On rendering, the bundle generates an image challenge and stores the expected value in the session.
  - On submission, form validation checks the user’s response against the stored value.
- **Symfony mechanisms**
  - Third‑party form type integration.
  - Validation via Symfony’s form component.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
This feature is almost entirely provided by **GregwarCaptchaBundle**, which delivers the form type and validation; the project’s role is integration and configuration.

**6️⃣ Technologies Used**  
Symfony (Forms, Twig), GregwarCaptchaBundle, PHP sessions.

**7️⃣ Validation Explanation (Oral Version)**  
“To harden sensitive forms, I integrated GregwarCaptchaBundle. The login and registration forms use the bundle’s `CaptchaType`, which generates an image challenge stored in the session. On submission, the form validation checks the user’s answer, which blocks most automated scripts.”

---

### 13. QR Code Generation for Invoices (Endroid QR Code)

**1️⃣ Functionality Name**  
QR‑sealed invoices with embedded verification data

**2️⃣ Functional Description**  
Generated invoices include a QR code that embeds key invoice data (ID, client, total, date, hash), allowing future verification of authenticity.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/FrontOffice_Controller/PanierController.php` (invoice generation methods)
  - `config/packages/endroid_qr_code.yaml`
  - Composer dependency: `endroid/qr-code-bundle`
  - PDF generation stack (TCPDF/Dompdf)
- **Data flow**
  - After a successful payment, invoice data is prepared (order ID, customer info, total, date).
  - The controller uses Endroid QR Code’s **Builder** (`Builder`, `PngWriter`, `ErrorCorrectionLevel`) to generate a PNG image in memory.
  - The QR image is integrated into the PDF invoice via TCPDF (as an image stream, e.g. `'@'.$qrCodeData`).
  - The invoice is then served as a PDF file.
- **Symfony mechanisms**
  - Use of Endroid QR Code Bundle services configured in YAML.
  - PDF creation integrated into a single controller method.

**4️⃣ Category**  
External Bundle / Library

**5️⃣ Justification**  
The QR code logic relies on the **Endroid QR Code Bundle**, which is an external library specifically for QR generation.

**6️⃣ Technologies Used**  
Symfony (Controller, Config), Endroid QR Code Bundle, TCPDF/Dompdf, Twig (for HTML representation), Doctrine (invoice/order entities).

**7️⃣ Validation Explanation (Oral Version)**  
“To make invoices verifiable, I added a QR code generated by the Endroid QR Code bundle. The controller builds a data string containing the invoice metadata and a hash, passes it to the bundle’s builder to generate a PNG, and embeds it into the TCPDF document. This provides a simple way to verify the integrity of an invoice.”

---

## III. Advanced Custom Functionality

### 14. Advanced Cart & Reservation/Payment Orchestration

**1️⃣ Functionality Name**  
Unified cart and multi‑domain reservation/payment orchestration

**2️⃣ Functional Description**  
Users can add heterogeneous items—hébergements, activities, transports, products—to a single cart, then confirm and pay. The system automatically creates consistent reservations, orders, and payment records for all domains.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/FrontOffice_Controller/PanierController.php`
  - `CartService` (session‑based cart management)
  - Entities:
    - `src/Entity/Reservation.php`
    - `src/Entity/PaymentReservation.php`
    - `src/Entity/Commande.php`, `src/Entity/LigneDeCommande.php`
    - `src/Entity/Paiement.php`
    - Enums under `src/Entity/Enum/*` (e.g. `ReservationType`, `PaymentMethod`, `PaymentStatus`)
  - Templates: `templates/FrontOffice/panier/*.html.twig`, `templates/FrontOffice/mes_reservations/*`
- **Data flow**
  - `PanierController` exposes endpoints for adding each type of item (`addHebergement`, `addActivity`, `addTransport`, `addProduit`) to the cart.
  - `CartService` stores a normalized representation in the session including type, quantity, price, and domain‑specific metadata (dates, number of guests, etc.).
  - At checkout, depending on the payment method (e.g. Stripe, cash), the controller calls a `finalizePayment()`‑style method:
    - Reservations are created from cart items with the appropriate `ReservationType` and initial status.
    - Commerce entities (`Commande`, `LigneDeCommande`, `Paiement`) are built to represent the commercial transaction.
    - Stocks or availability are checked before persisting.
  - After flush, the cart is cleared and confirmation pages are shown.
- **Symfony mechanisms**
  - Controllers using `JsonResponse` for cart operations and standard responses for checkout.
  - Doctrine ORM with enums to formalize business states.
  - Dependency injection for the cart and repositories.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
The orchestration between multiple business domains (tourism services and products), the design of reservation/payment entities and the cart service are all **custom application logic**, not tied to a single external API or bundle.

**6️⃣ Technologies Used**  
Symfony (Controllers, Services), Doctrine ORM (with enums), Twig, PHP session management, Stripe integration for one payment path.

**7️⃣ Validation Explanation (Oral Version)**  
“I designed a unified cart that can handle hébergements, activities, transports and products in a homogeneous way. A `CartService` stores normalized items in session, and at checkout the controller transforms them into domain entities like `Reservation`, `Commande`, `LigneDeCommande` and `Paiement`. This orchestrates several business processes in a single flow while keeping the model strongly typed with enums for reservation and payment status.”

---

### 15. PDF Invoice Generation with QR Code and Handwritten Signature

**1️⃣ Functionality Name**  
Legally styled PDF invoice with QR code and captured handwritten signature

**2️⃣ Functional Description**  
After payment, users can generate a detailed invoice in PDF format that includes line items, totals, a QR code, and an optional handwritten signature captured from the browser.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/FrontOffice_Controller/PanierController.php` (invoice methods like `facturePdf`)
  - `templates/FrontOffice/panier/facture_pdf.html.twig`
  - `templates/FrontOffice/panier/success.html.twig` (canvas signature UI)
  - PDF libraries: `dompdf/dompdf`, `tecnickcom/tcpdf`
  - Endroid QR code integration (see previous métier)
- **Data flow**
  - On the payment success page, the user can draw their signature on an HTML5 `<canvas>` element.
  - JavaScript captures the canvas as a base64 image (e.g. PNG) and posts it to the server when requesting the invoice.
  - `PanierController` decodes the base64 string, stores it temporarily or injects it directly into TCPDF.
  - The controller builds the invoice layout (order lines, totals, customer info) and adds both the QR code and signature image to the PDF.
  - The final PDF is streamed back to the browser.
- **Symfony mechanisms**
  - Combination of traditional Twig view and custom PDF output.
  - Processing of base64 file input and integration with a third‑party library.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
Although it uses external libraries, the way QR codes, signatures, and invoice data are combined and orchestrated is **highly custom business logic** specific to this project’s invoicing requirements.

**6️⃣ Technologies Used**  
Symfony (Controller, Twig), Dompdf/TCPDF, Endroid QR Code, HTML5 Canvas, JavaScript, Doctrine (orders and payments).

**7️⃣ Validation Explanation (Oral Version)**  
“For invoices I implemented a full PDF pipeline: after payment, the user can sign on an HTML5 canvas; the browser sends this base64 image to the backend, which creates a PDF using TCPDF or Dompdf. I also embed a QR code with invoice metadata and a hash. The result is a detailed invoice that visually resembles an official document and embeds both verification and the customer’s signature.”

---

### 16. Interactive Back-Office Dashboard with Aggregated Statistics & Charts

**1️⃣ Functionality Name**  
Admin dashboard with aggregated KPIs and visual analytics

**2️⃣ Functional Description**  
Administrators have access to a dashboard summarizing the platform’s KPIs (counts and distributions for activities, hébergements, transports, products) through animated charts and quick metrics.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/BackOffice_Controller/DashboardController.php`
  - `templates/BackOffice/dashboard.html.twig`
  - Repositories (sample):
    - `HebergementRepository` with methods like `getCountByCategory()`, `getChambresCountPerHebergement()`
    - `ChambreRepository` with `getCountByType()`
    - `ActivityCategoryRepository` with `getActivitiesCountByCategory()`
  - Chart.js via CDN in Twig
- **Data flow**
  - `DashboardController` aggregates various statistics from custom repository methods: counts per category, per type, per status, etc.
  - It structures these results into arrays of labels and values tailored for Chart.js.
  - The Twig template renders metric cards and injects the data into JavaScript variables.
  - Chart.js scripts instantiate line, bar, pie and doughnut charts with animations.
  - If a face descriptor exists for the admin, the access to this page is additionally gated by the face gate mechanism.
- **Symfony mechanisms**
  - Use of custom Doctrine queries for analytics.
  - Server‑side preparation of data for front‑end chart library.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
While Chart.js is an external JS library, the **design of the metrics, queries and dashboard structure** is custom business logic, so the métier is primarily an advanced custom feature.

**6️⃣ Technologies Used**  
Symfony (Controllers, Repositories, Twig), Doctrine ORM, Chart.js, JavaScript, CSS (dashboard design).

**7️⃣ Validation Explanation (Oral Version)**  
“I developed a back‑office dashboard that aggregates key indicators across activities, hébergements, transports and the boutique. Custom repository methods compute the counts and groupings, which the controller formats for Chart.js. The Twig template displays animated charts and KPI cards, giving administrators a direct overview of the platform’s performance.”

---

### 17. AJAX-Based Activity Search & Filtering

**1️⃣ Functionality Name**  
Asynchronous search and filtering of activities for administrators/users

**2️⃣ Functional Description**  
Without reloading the page, the user can filter activities by search term, price range, and availability; the list updates dynamically based on JSON responses.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Activity/ActivityController.php` (`index`, `searchActivities`)
  - `src/Repository/ActivityRepository.php` (`findByFilters`)
  - `templates/ActivityTemplate/activity/index.html.twig` (JavaScript for AJAX)
- **Data flow**
  - The index page renders the initial list and includes JS to listen for user input (search bar, price sliders, filters).
  - When filters change, JS sends an AJAX request (e.g. via `fetch`) to the same route or a dedicated JSON route.
  - If the request is XHR, the controller bypasses HTML rendering and returns a `JsonResponse` with activity data.
  - `ActivityRepository::findByFilters` builds a Doctrine query dynamically based on the filter parameters.
  - The front‑end JS then updates the DOM (list of activities) based on the returned JSON.
- **Symfony mechanisms**
  - Content‑negotiation based on request type (HTML vs. JSON).
  - Custom repository methods with dynamic query building.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
The functionality is mainly a **bespoke AJAX search system** built on Doctrine queries and JSON endpoints, not dependent on any major external service.

**6️⃣ Technologies Used**  
Symfony (Controllers, Repositories), Doctrine ORM, Twig, JavaScript (Fetch API, DOM updates), JSON.

**7️⃣ Validation Explanation (Oral Version)**  
“For the activity management I implemented an AJAX‑based search and filtering feature. The controller detects XHR requests and returns only JSON lists of activities obtained from a dynamic `findByFilters` repository method. The front‑end JavaScript updates the DOM without a page reload, which makes the interface much more responsive for admins.”

---

### 18. AJAX Cart Interactions & Reservation Restoration

**1️⃣ Functionality Name**  
Real‑time cart management and reservation restoration

**2️⃣ Functional Description**  
Users can add, remove or update items in the cart via AJAX, see the cart badge and totals update live, and even restore previous reservations back into the cart.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/FrontOffice_Controller/PanierController.php`
    - Methods: `addHebergement`, `addActivity`, `addTransport`, `addProduit`, `remove`, `updateQuantity`, `restoreReservation`
  - `CartService` (session logic)
  - `templates/FrontOffice/panier/index.html.twig` (JavaScript for cart updates)
  - Associated repositories for validation (e.g. `HebergementRepository`, `ActivityRepository`, etc.)
- **Data flow**
  - Each add/remove/update method returns a `JsonResponse` with the updated cart summary (items count, total price) and possibly HTML fragments.
  - JavaScript intercepts “Add to cart” and quantity change actions and calls these endpoints asynchronously.
  - The header cart badge and the cart table are updated using the JSON payload, providing instant feedback.
  - `restoreReservation` takes an existing `Reservation` (e.g. pending) and reconstructs equivalent cart items with the same parameters, then redirects the user to the cart page.
- **Symfony mechanisms**
  - Use of `JsonResponse` and route annotations to expose REST‑style endpoints.
  - Session‑based cart service.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
The system is a **custom AJAX layer** over the cart and reservation domain, involving specific business logic and data transformations.

**6️⃣ Technologies Used**  
Symfony (Controllers, Services), Doctrine ORM, Twig, JavaScript (Fetch API), JSON, PHP sessions.

**7️⃣ Validation Explanation (Oral Version)**  
“I implemented the cart as a fully asynchronous component: each action—add, remove, update quantity—calls a JSON endpoint in `PanierController` and updates the interface in JavaScript. There is also a ‘restore reservation’ feature that converts existing reservations back into cart items. This allows users to manage complex carts interactively and re‑book previous stays very quickly.”

---

### 19. Authentication, Custom Login Flow & Role-Based Redirection

**1️⃣ Functionality Name**  
Custom authenticator with role‑based redirection

**2️⃣ Functional Description**  
Users authenticate via a customized login form. After login, they are redirected differently depending on their role (admin vs. standard user), with integration into the back‑office and front‑office flows.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `config/packages/security.yaml`
  - `src/Security/UserAuthenticator.php`
  - `src/Entity/User.php` (enum‑based `Role` field and mapping to role strings)
  - `src/Controller/User/SecurityController.php`
  - `templates/user/security/login.html.twig`
- **Data flow**
  - `SecurityController` renders a custom login form.
  - On submission, `UserAuthenticator` processes credentials, validates CSRF, optionally remember‑me, and authenticates with the configured user provider.
  - After successful authentication, the authenticator computes a **target path** based on the user’s role:
    - Admins are redirected to the back‑office dashboard route.
    - Standard users are redirected to “Mon compte” or the home page.
- **Symfony mechanisms**
  - Custom authenticator implementing Symfony’s security interfaces.
  - Configuration of firewalls and access control in `security.yaml`.
  - Role management via an enum field mapped to serialized roles.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
This is a **custom security flow** using Symfony’s security component beyond the default maker‑generated behavior, particularly in role management and post‑login routing.

**6️⃣ Technologies Used**  
Symfony Security, Doctrine ORM, Twig, PHP enums, CSRF protection.

**7️⃣ Validation Explanation (Oral Version)**  
“I customized the authentication flow by writing my own `UserAuthenticator`. It reads the login form, validates CSRF tokens and, once the user is authenticated, redirects them according to their enum role—administrators are routed to the dashboard while regular users go to their account page. This leverages Symfony’s security component but adapts it closely to the project’s navigation model.”

---

### 20. File Uploads & Image Management for Activities and Other Modules

**1️⃣ Functionality Name**  
Custom file upload pipeline for images (Activities, Hébergements, Products)

**2️⃣ Functional Description**  
Admins can upload and modify images for activities and other entities. Files are validated, moved to public directories, and old files are cleaned when updated.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `src/Controller/Activity/ActivityController.php` (`new`, `edit` image logic)
  - `src/Form/ActivityType.php`
  - Public directories: `public/images/activities/*`
  - Similar patterns in controllers for hébergements and products
  - Optionally combined with Cloudinary URLs in some use cases
- **Data flow**
  - The form includes a `FileType` or `Image` field that maps to a non‑persisted property or path field.
  - On submission, the controller checks for an `UploadedFile`, generates a safe filename using `SluggerInterface`, and moves the file to a directory under `public/images/...`.
  - The relative path is stored in the entity and persisted.
  - On edit, if a new file is uploaded, the old file is deleted using Symfony’s `Filesystem` component.
- **Symfony mechanisms**
  - Manual management of file uploads in controller rather than relying solely on upload bundles.
  - Validation in form and entity constraints (e.g. max size, MIME types).

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
The upload process is **hand‑crafted**, controlling filenames, directories, and cleanup logic; this is business‑specific and not simply wrapping a single external library.

**6️⃣ Technologies Used**  
Symfony (Controllers, Forms, Filesystem), Doctrine ORM, Twig, PHP file handling.

**7️⃣ Validation Explanation (Oral Version)**  
“For image uploads I implemented a custom pipeline: the form provides an `UploadedFile`, the controller generates a slugified unique name, moves the file into a public directory and stores the path on the entity. On edit, the previous file is removed with the Filesystem component to avoid orphans, which gives full control over storage and naming.”

---

### 21. Front-Office Dynamic Home Page with Translation Hooks

**1️⃣ Functionality Name**  
Dynamic marketing home page integrated with translation system

**2️⃣ Functional Description**  
The home page showcases the main modules (hébergements, activities, testimonials, eco‑trip concept) with sections that can be dynamically translated and enhanced via JavaScript.

**3️⃣ Technical Implementation**  
- **Main files and components**
  - `templates/FrontOffice/home/index.html.twig`
  - `public/js/translator.js` (language switching)
  - CSS and JS assets to animate the hero and sections
- **Data flow**
  - Twig renders structured sections with semantic HTML and IDs, including the translation root used by the MyMemory integration.
  - The translation JS binds to the language selector and triggers the translation pipeline described earlier.
  - The page may also include AJAX‑driven elements such as featured activities or offers.
- **Symfony mechanisms**
  - Use of Twig includes for reusable blocks.
  - Clean separation between data and presentation suitable for SEO.

**4️⃣ Category**  
Advanced Custom Functionality

**5️⃣ Justification**  
Although it uses previously described services, the overall design and UX of the home page is a **custom front‑office feature** that orchestrates several advanced mechanisms: translation, marketing content and dynamic behaviors.

**6️⃣ Technologies Used**  
Symfony (Twig templates), JavaScript, CSS, Translator integration, potentially Doctrine for dynamic content.

**7️⃣ Validation Explanation (Oral Version)**  
“I designed the front‑office home page as a dynamic marketing showcase for the EcoTrip concept. The Twig template structures the sections and integrates with the translation script, which allows live language switching. This page also serves as a central entry point to the main modules—activities, hébergements, transport and boutique—ensuring a coherent user experience.”

---

## Conclusion

This technical summary highlights the project’s **advanced business features** across three axes:

- **API Integrations** (face recognition, AI assistants, translation, payments, media hosting)  
- **External Bundles/Libraries** (PDF, email verification and reset, CAPTCHA, QR code, mailer)  
- **Advanced Custom Functionalities** (multi‑domain cart, reservation orchestration, invoicing, dashboards, AJAX UX, security flows, file uploads).

Each métier avancé is supported by clearly identified files, services and data flows, which you can reference during your oral validation to demonstrate both **technical mastery of Symfony and web architecture** and **coherent business modeling of the EcoTrip platform**.

