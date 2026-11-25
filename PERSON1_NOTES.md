# Person 1 – Resident module (rebuilt)

Copy these files over the project (same paths). Then:

    php artisan migrate:fresh --seed
    php artisan test

Delete before zipping: `.env`, `storage/logs/laravel.log`, `.phpunit.result.cache`.

## What changed (from the task division)
- `user_id` -> `resident_id` (migration, model, factory, User relation, Policy, tests)
- Level, block and room on submit/edit; `locationLabel()` replaces `location`
- `MaintenanceRequest::STATUSES` / `CATEGORIES` used everywhere (no raw strings)
- Form Requests: `StoreMaintenanceRequest`, `UpdateMaintenanceRequest` (same rules)
- Actions: `CreateMaintenanceRequest` (+ first status log, one transaction),
  `UpdateMaintenanceRequestAction`, `DeleteMaintenanceRequestAction`
- Policy merged: viewAny/assign/overrideStatus (admin) + view/create/update/delete/updateProgress;
  technician can no longer update a completed job
- All resident pages use Flux; dashboard has search, status filter, pagination (5/page),
  per-status badge colour, success message; details page shows status history and remarks
- `role` removed from `User` fillable
- Pest: 13 original tests updated + new tests (edit validation, counts, search, filter,
  pagination, Actions, Policy, role guards, XSS, mass assignment)
- Flow in every page: authorize (Policy) -> Form Request -> Action -> response

## STAND-INS – replace with Person 2's version when merging
These exist only so Person 1's module runs on its own. Keep Person 2's files if they differ:
- `app/Models/Block.php`, `StatusLog.php`, `Remark.php`
- migrations `..._create_blocks_table`, `..._create_status_logs_table`, `..._create_remarks_table`
- `database/factories/BlockFactory.php`
- `resources/views/components/confirm-modal.blade.php`
- `MaintenanceRequest::STATUSES`, `locationLabel()`, `statusLogs()`, `remarks()`
- Policy abilities `viewAny`, `assign`, `overrideStatus` (written as admin-only)

## Assumptions to check with Person 2 (column names)
- `maintenance_requests`: resident_id, technician_id, block_id, level, room, title, description, category, status
- `status_logs`: maintenance_request_id, from_status (NULLABLE), status, changed_by
- `remarks`: maintenance_request_id, user_id, body
- `STATUSES` is `value => label`; values are pending, in_progress, completed

## Not done here (other people's tasks)
- Seeder, README.txt, Person 2's admin pages, Person 3's technician pages
- Screenshots + Implementation Brief text
