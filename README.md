# Romy and Allit Food Haven

Laravel application for the canteen storefront, customer accounts and order tracking, checkout, and admin tools. The original procedural PHP files remain in `additional file/` as source reference; the Laravel routes do not execute those standalone scripts.

## Render deployment

Deploy this PHP application as a Docker web service using the included `Dockerfile` and `render.yaml`. The image builds Vite assets, enables both MySQL and PostgreSQL PDO drivers, and starts Apache on Render's `PORT`. The `/up` health endpoint confirms the web process is responding without requiring a database connection.

Create a Render Blueprint from this repository in the Singapore region (or choose a region close to your users and Supabase project). Review the Blueprint's generated `APP_KEY`, and enter the variables marked `sync: false` in the Render dashboard. For Supabase, use the **Session Pooler** host, port, and username shown in the project's Connect dialog; use database `postgres` and set `DB_SSLMODE=require`. Production selects PostgreSQL while the local `.env` continues selecting XAMPP MySQL. Do not substitute the Supabase direct connection or transaction pooler without checking compatibility with the app's IPv4 and session requirements.

The container intentionally does **not** migrate or seed the database at startup. Before any schema migration, back up the Supabase database, compare its schema and data with the local database, review the migration plan, and get approval. The migrations in this repository create a new Laravel schema; they do not import existing MySQL records. Do not run `migrate:fresh`, `db:wipe`, or reset commands. After approval, run reviewed migrations manually from the Render shell with `php artisan migrate --force`; run seeders only if separately reviewed, since they create starter menu and optional administrator records. Uploaded product images are written to the public disk, and local container storage is temporary; configure a persistent disk or object storage before relying on uploads in production.

Render variables include `APP_NAME`, `APP_ENV`, `APP_DEBUG`, generated `APP_KEY`, `APP_URL`, `LOG_CHANNEL`, `LOG_LEVEL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK`, and SMTP settings. Add any administrator seed credentials only for an explicitly approved seed operation; do not set them preemptively.

## Vercel deployment

Vercel runs Laravel through the PHP function configured in `vercel.json`; deploy the repository root and keep the framework preset set to **Other** (the config sets this automatically). Add `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Vercel's Environment Variables. Add `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` to enable Gmail delivery. Do not copy `.env` into the deployment. The database must be hosted somewhere Vercel can reach; local XAMPP MySQL is not reachable from Vercel. Run the migrations against that database before serving traffic. Vercel's function filesystem is temporary, so use object storage for uploaded product images.

## Local setup

1. Install PHP dependencies and create the environment file:

   ```sh
   composer install
   copy .env.example .env
   php artisan key:generate
   ```

2. Configure `.env` for an empty database. For XAMPP MySQL, create a new database (for example `romy_allit_food_haven_laravel`) and set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=romy_allit_food_haven_laravel`, and the appropriate database username/password.

3. Configure an administrator before seeding:

   ```dotenv
   ADMIN_NAME="Canteen Administrator"
   ADMIN_USERNAME=admin
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD="use-a-unique-password"
   ```

   Then create the schema and starter menu:

   ```sh
   php artisan migrate --seed
   php artisan storage:link
   ```

   An administrator is created only when both `ADMIN_USERNAME` and `ADMIN_PASSWORD` are set. Do not use a default or shared password.

4. Configure outgoing mail in `.env` to enable registration verification and password resets. Set `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`. The default `log` mailer is for local development only.

5. Point the web server document root to this project's `public/` directory, then run the app with your normal Laravel/PHP development server.

Product image uploads are stored on Laravel's public disk and require the `storage:link` step. Existing menu photos and the GCash QR image are served from `public/assets/`.

The migration creates the Laravel schema; it does not import rows from a separately installed legacy `db_karenderya` database. Back up existing data and plan an explicit data import before pointing production at an existing database.
