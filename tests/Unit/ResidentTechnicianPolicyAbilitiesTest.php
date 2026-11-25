<?php

use App\Models\MaintenanceRequest;
use App\Models\User;

function p1User(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

// create ---------------------------------------------------------------

test('only residents can create requests', function () {
    expect(p1User('resident')->can('create', MaintenanceRequest::class))->toBeTrue()
        ->and(p1User('technician')->can('create', MaintenanceRequest::class))->toBeFalse()
        ->and(p1User('admin')->can('create', MaintenanceRequest::class))->toBeFalse();
});

// view -----------------------------------------------------------------

test('view is limited to the owner, the assigned technician and admins', function () {
    $owner = p1User('resident');
    $technician = p1User('technician');
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $owner->id,
        'technician_id' => $technician->id,
    ]);

    expect($owner->can('view', $request))->toBeTrue()
        ->and($technician->can('view', $request))->toBeTrue()
        ->and(p1User('admin')->can('view', $request))->toBeTrue()
        ->and(p1User('resident')->can('view', $request))->toBeFalse()
        ->and(p1User('technician')->can('view', $request))->toBeFalse();
});

// update / delete (resident) -------------------------------------------

test('resident can update and delete a pending unassigned request', function () {
    $owner = p1User('resident');
    $request = MaintenanceRequest::factory()->create(['resident_id' => $owner->id]);

    expect($owner->can('update', $request))->toBeTrue()
        ->and($owner->can('delete', $request))->toBeTrue();
});

test('resident cannot update or delete once a technician is assigned', function () {
    $owner = p1User('resident');
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $owner->id,
        'technician_id' => p1User('technician')->id,
    ]);

    expect($owner->can('update', $request))->toBeFalse()
        ->and($owner->can('delete', $request))->toBeFalse();
});

test('resident cannot update or delete once the request is in progress or completed', function (string $status) {
    $owner = p1User('resident');
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $owner->id,
        'status' => $status,
    ]);

    expect($owner->can('update', $request))->toBeFalse()
        ->and($owner->can('delete', $request))->toBeFalse();
})->with([MaintenanceRequest::STATUS_IN_PROGRESS, MaintenanceRequest::STATUS_COMPLETED]);

test('another resident cannot update or delete the request', function () {
    $request = MaintenanceRequest::factory()->create();
    $other = p1User('resident');

    expect($other->can('update', $request))->toBeFalse()
        ->and($other->can('delete', $request))->toBeFalse();
});

// update (technician) --------------------------------------------------

test('assigned technician can update an open job but not a completed one', function () {
    $technician = p1User('technician');

    $open = MaintenanceRequest::factory()->create([
        'technician_id' => $technician->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);
    $done = MaintenanceRequest::factory()->create([
        'technician_id' => $technician->id,
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);

    expect($technician->can('update', $open))->toBeTrue()
        ->and($technician->can('update', $done))->toBeFalse();
});

test('a technician who is not assigned cannot update the request', function () {
    $request = MaintenanceRequest::factory()->create([
        'technician_id' => p1User('technician')->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);

    expect(p1User('technician')->can('update', $request))->toBeFalse();
});

// updateProgress -------------------------------------------------------

test('updateProgress is only for the assigned technician on a job that is not completed', function () {
    $technician = p1User('technician');

    $pending = MaintenanceRequest::factory()->create(['technician_id' => $technician->id]);
    $inProgress = MaintenanceRequest::factory()->create([
        'technician_id' => $technician->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);
    $completed = MaintenanceRequest::factory()->create([
        'technician_id' => $technician->id,
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);

    expect($technician->can('updateProgress', $pending))->toBeTrue()
        ->and($technician->can('updateProgress', $inProgress))->toBeTrue()
        ->and($technician->can('updateProgress', $completed))->toBeFalse()
        ->and(p1User('technician')->can('updateProgress', $pending))->toBeFalse()
        ->and(p1User('resident')->can('updateProgress', $pending))->toBeFalse()
        ->and(p1User('admin')->can('updateProgress', $pending))->toBeFalse();
});

test('a request with no technician cannot be progressed by anyone', function () {
    $request = MaintenanceRequest::factory()->create();

    expect(p1User('technician')->can('updateProgress', $request))->toBeFalse();
});

// Person 2's abilities must still be admin only ------------------------

test('viewAny, assign and overrideStatus stay admin only', function () {
    $request = MaintenanceRequest::factory()->create();

    expect(p1User('admin')->can('viewAny', MaintenanceRequest::class))->toBeTrue()
        ->and(p1User('admin')->can('assign', $request))->toBeTrue()
        ->and(p1User('admin')->can('overrideStatus', $request))->toBeTrue()
        ->and(p1User('resident')->can('viewAny', MaintenanceRequest::class))->toBeFalse()
        ->and(p1User('technician')->can('assign', $request))->toBeFalse()
        ->and(p1User('resident')->can('overrideStatus', $request))->toBeFalse();
});
