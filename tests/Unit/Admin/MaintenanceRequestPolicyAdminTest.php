<?php

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\MaintenanceRequestPolicy;

beforeEach(function () {
    $this->policy = new MaintenanceRequestPolicy;
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->technician = User::factory()->create(['role' => 'technician']);
    $this->resident = User::factory()->create(['role' => 'resident']);
});

test('only admin can view the list of all requests', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue()
        ->and($this->policy->viewAny($this->technician))->toBeFalse()
        ->and($this->policy->viewAny($this->resident))->toBeFalse();
});

test('admin can view any request', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    expect($this->policy->view($this->admin, $request))->toBeTrue();
});

test('only admin can assign a technician', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    expect($this->policy->assign($this->admin, $request))->toBeTrue()
        ->and($this->policy->assign($this->technician, $request))->toBeFalse()
        ->and($this->policy->assign($this->resident, $request))->toBeFalse();
});

test('admin cannot assign a technician to a completed request', function () {
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'status' => 'completed',
    ]);

    expect($this->policy->assign($this->admin, $request))->toBeFalse();
});

test('only admin can override status', function () {
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'technician_id' => $this->technician->id,
    ]);

    expect($this->policy->overrideStatus($this->admin, $request))->toBeTrue()
        ->and($this->policy->overrideStatus($this->technician, $request))->toBeFalse()
        ->and($this->policy->overrideStatus($this->resident, $request))->toBeFalse();
});

test('gate resolves the policy for admin abilities', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    expect($this->admin->can('assign', $request))->toBeTrue()
        ->and($this->resident->can('assign', $request))->toBeFalse()
        ->and($this->admin->can('viewAny', MaintenanceRequest::class))->toBeTrue();
});
