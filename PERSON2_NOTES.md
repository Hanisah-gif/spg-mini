# Person 2 – Admin module + shared schema (merged on top of feature/resident-module)

Branch: `feature/admin-module` (based on Person 1's `feature/resident-module`, so all resident work is kept).

After pulling:

    composer install
    npm install && npm run build
    php artisan optimize:clear
    php artisan migrate:fresh --seed
    php artisan test

## Demo accounts (password: `password`)
| Role | Email | Lives in |
|---|---|---|
| Admin | admin@hostel.test | – |
| Technician | tech1@hostel.test, tech2@hostel.test | – |
| Resident | test@example.com (A-101), resident1@hostel.test (A-203), resident2@hostel.test (C-315) | |

## Person 1 stand-ins replaced (as PERSON1_NOTES.md asked)
| Stand-in (Person 1) | Shared version now | Why |
|---|---|---|
| `blocks.name` | `blocks.block_name` | Stage 1 table spec |
| `remarks.user_id`, `remarks.body`, `Remark::author()` | `remarks.technician_id`, `remarks.remark`, `Remark::technician()` | Stage 1 table spec + relationship names |
| `StatusLog::changedBy()` | `StatusLog::changer()` | Stage 1 section 6.2 |
| blocks migration `2026_11_23_191900` | `0001_01_00_000000_create_blocks_table` | Must run before `users` (users.block_id FK) |
| `add_role_to_users_table` migration | `role` inside the original users migration | Task division: modify the original, no duplicate |
| `confirm-modal` stand-in | Shared version: same props (`action`, `title`, `message`, `confirm-label`, `variant`) + slot, `params`, `cancel-label` | Person 1's usage works unchanged |

Person 1 files touched only for these renames: resident `create-request` / `edit-request` (`block_name`), `request-details` (`changer`, `technician`, `remark`), `ResidentMaintenanceRequestTest` (2 lines).

## Kept from Person 1
`resident_id`, `level`, `STATUS_*` / `STATUSES` / `CATEGORIES` constants, merged Policy, `role` not fillable, Actions and Form Requests, `status-badge` component.

## Added by Person 2
- `users.block_id` + `users.room`; registration asks for block + room (role always resident)
- `maintenance_requests.admin_remarks` (reason for an admin override)
- Policy `assign`: admin only **and** not on a completed job
- Admin pages: `pages/admin/⚡dashboard`, `pages/admin/⚡request-details`, route `admin.requests.show`, sidebar link
- `App\Actions\Admin\AssignTechnician`, `App\Actions\Admin\OverrideRequestStatus` (status + log in one transaction)
- `App\Http\Requests\Admin\AssignTechnicianRequest`, `OverrideStatusRequest` (whitelist + `strip_tags`)
- `RemarkFactory`, realistic titles in `MaintenanceRequestFactory`, full multi-role `DatabaseSeeder`
- Tests: `tests/Feature/Admin/`, `tests/Unit/Admin/`, registration tests

## onDelete rules
| Column | onDelete | Reason |
|---|---|---|
| `users.block_id` | SET NULL | Deleting a block must not delete accounts |
| `maintenance_requests.resident_id` | CASCADE | A request belongs to its resident |
| `maintenance_requests.technician_id` | SET NULL | Job becomes unassigned, not lost |
| `maintenance_requests.block_id` | RESTRICT | A block with requests cannot be deleted (safer than Stage 1 CASCADE) |
| `status_logs.maintenance_request_id` | CASCADE | History belongs to the request |
| `status_logs.changed_by` | SET NULL | Audit history is kept ("Unknown user") |
| `remarks.maintenance_request_id` | CASCADE | Remarks belong to the request |
| `remarks.technician_id` | SET NULL | Notes are kept after an account is deleted |

## For Person 3 (Technician)
Base your branch on `feature/admin-module`. Use `MaintenanceRequest::STATUS_*`, the `updateProgress` Policy ability and:

```php
// progress change + log in one transaction (inside your UpdateProgress action)
DB::transaction(function () use ($request, $newStatus) {
    $old = $request->status;
    $request->update(['status' => $newStatus]);
    $request->statusLogs()->create([
        'from_status' => $old,
        'status' => $newStatus,
        'changed_by' => auth()->id(),
    ]);
});

// add a remark (inside your AddRemark action)
$request->remarks()->create([
    'technician_id' => auth()->id(),
    'remark' => $validated['remark'],
]);
```

Confirm button for Start / Complete:
```blade
<x-confirm-modal action="completeJob" title="Mark job as completed?" message="You cannot change it afterwards." confirm-label="Yes, complete" variant="primary">
    <x-slot:trigger><flux:button variant="primary">Complete Job</flux:button></x-slot:trigger>
</x-confirm-modal>
```
