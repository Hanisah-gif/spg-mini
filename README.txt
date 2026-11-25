HOSTEL MAINTENANCE REQUEST TRACKER
SPG 0553 Advanced Web Programming - Mini Project (July 2026)
German-Malaysian Institute

=====================================================================
1. REQUIREMENTS
=====================================================================
- Laravel Herd (PHP 8.4, Composer, Node.js)
- Git (optional)
- No MySQL needed: the project uses SQLite (database/database.sqlite)

=====================================================================
2. SETUP
=====================================================================
1. Extract the project into the Herd folder, for example:
     C:\Users\<you>\Herd\hostel-maintenance-tracker

2. Open a terminal in the project folder and run:
     composer install
     npm install
     npm run build

3. Environment variables:
     copy .env.example .env          (macOS/Linux: cp .env.example .env)
     php artisan key:generate

   Important values in .env:
     APP_NAME="Hostel Maintenance Tracker"
     APP_URL=http://hostel-maintenance-tracker.test
     DB_CONNECTION=sqlite
   No database username or password is needed.

4. Database creation, migrations and demo data:
     php artisan migrate:fresh --seed
   If asked "Would you like to create it?", answer yes. This creates
   database/database.sqlite, runs all migrations and seeds the demo data.

5. Open the site in the browser:
     http://hostel-maintenance-tracker.test
   (Herd uses the folder name as the site name.)

6. Run the automated tests:
     php artisan test

Troubleshooting
- "419 Page Expired": refresh the login page and sign in again.
- Errors after copying the project: run  php artisan optimize:clear

=====================================================================
3. DEFAULT LOGIN (password for all accounts: password)
=====================================================================
Role         Name                Email
Admin        Hostel Admin        admin@hostel.test
Technician   Ahmad Technician    tech1@hostel.test
Technician   Siva Technician     tech2@hostel.test
Resident     Test User           test@example.com        (Block A, 101)
Resident     Aisyah Resident     resident1@hostel.test   (Block A, 203)
Resident     Mei Ling Resident   resident2@hostel.test   (Block C, 315)

=====================================================================
4. MODULE ALLOCATION
=====================================================================
Student 1: Nur Hanisah Binti Sazali
  Resident Module & Security Lead
  - Resident dashboard, submit / edit / delete request, request details
  - Fortify authentication, role guards, CSRF, XSS, .env isolation

Student 2: Nurin Khairunnisa Binti Arman (SWE24070253)
  Admin Module & Architecture Lead
  - Admin dashboard, request details, assign technician, status override
  - Shared database schema, onDelete rules, multi-role seeder,
    reusable Alpine confirmation component, final merge

Student 3: Syazwani Binti Mohamad
  Technician Module, Alpine UI & QA Lead
  - Technician dashboard, update progress, maintenance remarks
  - Alpine.js interactions and technician Pest tests

=====================================================================
5. WHERE THINGS ARE
=====================================================================
Livewire pages   resources/views/pages/{resident,admin,technician}/
Form Requests    app/Http/Requests/ (Admin/ for admin)
Action classes   app/Actions/ (Admin/ for admin)
Policy           app/Policies/MaintenanceRequestPolicy.php
Role middleware  app/Http/Middleware/RoleMiddleware.php
Migrations       database/migrations/
Seeder           database/seeders/DatabaseSeeder.php
Pest tests       tests/Feature/ and tests/Unit/
