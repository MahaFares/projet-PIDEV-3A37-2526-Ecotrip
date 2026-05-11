# EcoTrip Symfony Web Application

## Project Overview

EcoTrip is a Symfony-based web application designed to manage travel-related content, reservations, and services. The project includes both a front-office user experience and an administrative back-office to manage products, transportation, accommodations, reservations, and user accounts. It combines modern PHP web development practices with a service-driven architecture, templating, and rich frontend assets.

This repository is best suited for developers working with:

- Symfony 6.4
- PHP 8.1+
- MVC architecture
- Web application development
- REST-style routing and controllers
- Content management for tourism / travel services
- Admin dashboard and reservation workflows

## Key Topics

- Symfony
- PHP
- Web Development
- MVC
- Doctrine ORM
- Twig Templates
- REST API
- User Authentication
- Reservation System
- Admin Panel
- Asset Bundling
- Frontend Build Tools

## Tech Stack

- PHP 8.1+ with Symfony 6.4
- Doctrine ORM and Doctrine Migrations
- Twig templating engine
- Webpack Encore for frontend asset management
- Bootstrap-based UI dependencies
- Cloudinary image management
- Stripe and Twilio integrations
- VichUploader bundle for file uploads
- QR code generation and PDF generation

## Installation & Setup

1. Clone the repository:

```bash
git clone <repository-url>
cd PI_EcoTrip
```

2. Install PHP dependencies with Composer:

```bash
composer install
```

3. Install frontend dependencies with npm:

```bash
npm install
```

4. Build frontend assets:

```bash
npm run build
```

5. Create or update environment variables:

- Copy `.env` to create `.env.local` if needed.
- Add or customize database and service credentials.

## Configuration

### Environment Variables

This application uses Symfony environment files located at the project root:

- `.env`
- `.env.local`
- `.env.dev`
- `.env.test`

Important variables to configure:

```dotenv
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0"
MAILER_DSN=smtp://localhost
CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
STRIPE_SECRET_KEY=sk_test_...
TWILIO_SID=AC... 
TWILIO_AUTH_TOKEN=...
```

> Replace the placeholder values with your own database and service credentials.

### Database Setup

1. Create the database:

```bash
php bin/console doctrine:database:create
```

2. Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

3. Optionally load fixtures if available or seed data manually.

## Run Locally

### Start the Symfony application

If you have the Symfony CLI installed:

```bash
symfony serve -d
```

Or using PHP built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open:

```text
http://127.0.0.1:8000
```

### Frontend development

For live asset rebuilding during development:

```bash
npm run watch
```

### Useful Symfony commands

- Clear cache:

```bash
php bin/console cache:clear
```

- Dump routing:

```bash
php bin/console debug:router
```

- Run tests:

```bash
php bin/phpunit
```

## Project Structure

- `config/` — Symfony configuration files
- `src/` — PHP source code, controllers, entities, services
- `templates/` — Twig templates for front-end and admin views
- `public/` — Public web root and compiled assets
- `assets/` — JavaScript, styles, and frontend sources
- `migrations/` — Doctrine migration files
- `tests/` — Automated tests

## Notes

- This project includes integrations for Cloudinary, Stripe, Twilio, PDF generation, QR codes, and file uploads.
- The application is built to support tourism and travel booking workflows, including product display, reservation handling, and administration.

## Keywords

Symfony, PHP, Doctrine, Twig, Webpack Encore, MVC, REST, Travel, Tourism, Reservation, Booking, Admin Panel, Web App, API, Cloudinary, Stripe, Twilio, QR code, PDF generation, file upload.
