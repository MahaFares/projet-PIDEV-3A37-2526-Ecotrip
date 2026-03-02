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
## II. External Bundle / Library

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

## Conclusion

This technical summary highlights the project’s **advanced business features** across three axes:

- **API Integrations** (face recognition, AI assistants, translation, payments, media hosting)  
- **External Bundles/Libraries** (PDF, email verification and reset, CAPTCHA, QR code, mailer)  
- **Advanced Custom Functionalities** (multi‑domain cart, reservation orchestration, invoicing, dashboards, AJAX UX, security flows, file uploads).

Each métier avancé is supported by clearly identified files, services and data flows, which you can reference during your oral validation to demonstrate both **technical mastery of Symfony and web architecture** and **coherent business modeling of the EcoTrip platform**.
