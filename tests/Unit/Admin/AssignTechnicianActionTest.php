<?php

use App\Actions\Admin\AssignTechnician;
use App\Models\MaintenanceRequest;
use App\Models\User;

test('it assigns the technician to the request', function () {
    $technician = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create();

    $result = (new AssignTechnician)->execute($request, $technician);

    expect($result->technician_id)->toBe($technician->id)
        ->and($result->technician->is($technician))->toBeTrue();
});

test('it does not change the request status when assigning', function () {
    $technician = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create(['status' => 'pending']);

    $result = (new AssignTechnician)->execute($request, $technician);

    expect($result->status)->toBe('pending');
});

test('it refuses to assign a non-technician user', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $request = MaintenanceRequest::factory()->create();

    (new AssignTechnician)->execute($request, $resident);
})->throws(InvalidArgumentException::class, 'Only technicians');

test('it refuses to reassign a completed request', function () {
    $technician = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create(['status' => 'completed']);

    (new AssignTechnician)->execute($request, $technician);
})->throws(InvalidArgumentException::class, 'completed');
