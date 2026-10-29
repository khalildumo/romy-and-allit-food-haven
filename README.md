# Romy and Allit Food Haven

Laravel application for the canteen storefront, customer accounts and order tracking, checkout, and admin tools. The original procedural PHP files remain in `additional file/` as source reference; the Laravel routes do not execute those standalone scripts.

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
