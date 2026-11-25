<?php

use App\Actions\Admin\OverrideRequestStatus;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('it updates the status and records a status log', function () {
    $request = MaintenanceRequest::factory()->create(['status' => 'pending']);

    $result = (new OverrideRequestStatus)->execute($request, 'in_progress', $this->admin);

    expect($result->status)->toBe('in_progress')
        ->and($result->statusLogs)->toHaveCount(1);

    $log = $result->statusLogs->first();

    expect($log->from_status)->toBe('pending')
        ->and($log->status)->toBe('in_progress')
        ->and($log->changed_by)->toBe($this->admin->id);
});

test('it saves admin remarks when given', function () {
    $request = MaintenanceRequest::factory()->create();

    $result = (new OverrideRequestStatus)->execute($request, 'completed', $this->admin, 'Closed by admin.');

    expect($result->admin_remarks)->toBe('Closed by admin.');
});

test('it keeps existing admin remarks when none are given', function () {
    $request = MaintenanceRequest::factory()->create(['admin_remarks' => 'Old note']);

    $result = (new OverrideRequestStatus)->execute($request, 'completed', $this->admin);

    expect($result->admin_remarks)->toBe('Old note');
});

test('it rejects an unknown status', function () {
    $request = MaintenanceRequest::factory()->create();

    (new OverrideRequestStatus)->execute($request, 'cancelled', $this->admin);
})->throws(ValidationException::class);

test('it rejects the same status and writes no log', function () {
    $request = MaintenanceRequest::factory()->create(['status' => 'pending']);

    try {
        (new OverrideRequestStatus)->execute($request, 'pending', $this->admin);
    } catch (ValidationException) {
        // expected
    }

    expect($request->statusLogs()->count())->toBe(0);
});
