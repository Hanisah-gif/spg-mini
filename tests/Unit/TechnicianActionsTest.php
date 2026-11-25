<?php

use App\Actions\AddRemark;
use App\Actions\UpdateProgress;
use App\Models\MaintenanceRequest;
use App\Models\StatusLog;
use App\Models\User;

test('UpdateProgress transitions pending to in progress and logs status change', function () {
    $tech = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create([
        'technician_id' => $tech->id,
        'status' => MaintenanceRequest::STATUS_PENDING,
    ]);

    $updated = app(UpdateProgress::class)->execute(
        $request,
        $tech,
        MaintenanceRequest::STATUS_IN_PROGRESS,
        'Starting inspection'
    );

    expect($updated->status)->toBe(MaintenanceRequest::STATUS_IN_PROGRESS);

    $log = StatusLog::where('maintenance_request_id', $request->id)->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->from_status)->toBe(MaintenanceRequest::STATUS_PENDING)
        ->and($log->status)->toBe(MaintenanceRequest::STATUS_IN_PROGRESS)
        ->and($log->changed_by)->toBe($tech->id);

    $this->assertDatabaseHas('remarks', [
        'maintenance_request_id' => $request->id,
        'technician_id' => $tech->id,
        'remark' => 'Starting inspection',
    ]);
});

test('UpdateProgress rejects skipping directly from pending to completed', function () {
    $tech = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create([
        'technician_id' => $tech->id,
        'status' => MaintenanceRequest::STATUS_PENDING,
    ]);

    expect(fn () => app(UpdateProgress::class)->execute(
        $request,
        $tech,
        MaintenanceRequest::STATUS_COMPLETED
    ))->toThrow(InvalidArgumentException::class);
});

test('AddRemark stores a remark record in the remarks table', function () {
    $tech = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create([
        'technician_id' => $tech->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);

    $remark = app(AddRemark::class)->execute($request, $tech, 'Replaced worn seal');

    expect($remark->maintenance_request_id)->toBe($request->id)
        ->and($remark->technician_id)->toBe($tech->id)
        ->and($remark->remark)->toBe('Replaced worn seal');
});