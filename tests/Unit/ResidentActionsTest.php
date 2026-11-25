<?php

use App\Actions\CreateMaintenanceRequest;
use App\Actions\DeleteMaintenanceRequestAction;
use App\Actions\UpdateMaintenanceRequestAction;
use App\Models\Block;
use App\Models\MaintenanceRequest;
use App\Models\StatusLog;
use App\Models\User;

test('CreateMaintenanceRequest saves a pending request and its first status log', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $block = Block::factory()->create();

    $request = app(CreateMaintenanceRequest::class)->execute([
        'resident_id' => $resident->id,
        'block_id' => $block->id,
        'level' => 4,
        'room' => '410',
        'title' => 'Broken lock',
        'category' => 'Furniture',
        'description' => 'The wardrobe lock is jammed.',
    ]);

    expect($request->status)->toBe(MaintenanceRequest::STATUS_PENDING)
        ->and($request->resident_id)->toBe($resident->id)
        ->and($request->technician_id)->toBeNull();

    $log = StatusLog::where('maintenance_request_id', $request->id)->sole();

    expect($log->from_status)->toBeNull()
        ->and($log->status)->toBe(MaintenanceRequest::STATUS_PENDING)
        ->and($log->changed_by)->toBe($resident->id);
});

test('CreateMaintenanceRequest ignores a status passed in by the caller', function () {
    $resident = User::factory()->create(['role' => 'resident']);

    $request = app(CreateMaintenanceRequest::class)->execute([
        'resident_id' => $resident->id,
        'block_id' => Block::factory()->create()->id,
        'level' => 1,
        'room' => '1',
        'title' => 'Try to skip the queue',
        'category' => 'Other',
        'description' => 'Trying to start as completed.',
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);

    expect($request->status)->toBe(MaintenanceRequest::STATUS_PENDING);
});

test('UpdateMaintenanceRequestAction updates the editable details', function () {
    $request = MaintenanceRequest::factory()->create(['title' => 'Old title']);

    app(UpdateMaintenanceRequestAction::class)->execute($request, [
        'title' => 'New title',
        'room' => '999',
    ]);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $request->id,
        'title' => 'New title',
        'room' => '999',
    ]);
});

test('UpdateMaintenanceRequestAction never changes owner, status or technician', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);
    $technician = User::factory()->create(['role' => 'technician']);
    $stranger = User::factory()->create(['role' => 'resident']);

    app(UpdateMaintenanceRequestAction::class)->execute($request, [
        'title' => 'Allowed change',
        'resident_id' => $stranger->id,
        'status' => MaintenanceRequest::STATUS_COMPLETED,
        'technician_id' => $technician->id,
    ]);

    $request->refresh();

    expect($request->title)->toBe('Allowed change')
        ->and($request->resident_id)->toBe($resident->id)
        ->and($request->status)->toBe(MaintenanceRequest::STATUS_PENDING)
        ->and($request->technician_id)->toBeNull();
});

test('DeleteMaintenanceRequestAction removes the request', function () {
    $request = MaintenanceRequest::factory()->create();

    app(DeleteMaintenanceRequestAction::class)->execute($request);

    $this->assertDatabaseMissing('maintenance_requests', ['id' => $request->id]);
});

test('DeleteMaintenanceRequestAction also removes the status history', function () {
    $request = MaintenanceRequest::factory()->create();
    StatusLog::create([
        'maintenance_request_id' => $request->id,
        'from_status' => null,
        'status' => MaintenanceRequest::STATUS_PENDING,
        'changed_by' => $request->resident_id,
    ]);

    app(DeleteMaintenanceRequestAction::class)->execute($request);

    $this->assertDatabaseMissing('status_logs', ['maintenance_request_id' => $request->id]);
});
