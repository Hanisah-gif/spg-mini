<?php

use App\Models\MaintenanceRequest;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tech = User::factory()->create(['role' => 'technician', 'name' => 'Tech Ahmad']);
    $this->otherTech = User::factory()->create(['role' => 'technician']);
    $this->resident = User::factory()->create(['role' => 'resident']);
});

test('technician dashboard lists only jobs assigned to the technician', function () {
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'title' => 'My assigned job']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->otherTech->id, 'title' => 'Someone else job']);

    $this->actingAs($this->tech)
        ->get(route('technician.dashboard'))
        ->assertOk()
        ->assertSee('My assigned job')
        ->assertDontSee('Someone else job');
});

test('resident and admin cannot open the technician area', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('technician.dashboard'))->assertForbidden();
})->with(['resident', 'admin']);

test('technician cannot open a job assigned to another technician', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->otherTech->id]);

    $this->actingAs($this->tech)
        ->get(route('technician.requests.show', $job))
        ->assertForbidden();
});

test('technician can open an assigned job with its history and remarks', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'title' => 'Broken fan']);
    $job->statusLogs()->create(['from_status' => null, 'status' => 'pending', 'changed_by' => $this->resident->id]);
    $job->remarks()->create(['technician_id' => $this->tech->id, 'remark' => 'Checked the wiring']);

    $this->actingAs($this->tech)
        ->get(route('technician.requests.show', $job))
        ->assertOk()
        ->assertSee('Broken fan')
        ->assertSee('Checked the wiring')
        ->assertSee('Tech Ahmad');
});

test('start work moves the job to in progress and logs the change', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'status' => 'pending']);

    Livewire::actingAs($this->tech)
        ->test('pages::technician.request-details', ['request' => $job])
        ->set('progressRemark', 'Starting inspection')
        ->call('startWork')
        ->assertHasNoErrors();

    expect($job->fresh()->status)->toBe('in_progress');

    $this->assertDatabaseHas('status_logs', [
        'maintenance_request_id' => $job->id,
        'from_status' => 'pending',
        'status' => 'in_progress',
        'changed_by' => $this->tech->id,
    ]);
    $this->assertDatabaseHas('remarks', [
        'maintenance_request_id' => $job->id,
        'technician_id' => $this->tech->id,
        'remark' => 'Starting inspection',
    ]);
});

test('complete job moves an in progress job to completed', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'status' => 'in_progress']);

    Livewire::actingAs($this->tech)
        ->test('pages::technician.request-details', ['request' => $job])
        ->call('completeJob')
        ->assertHasNoErrors();

    expect($job->fresh()->status)->toBe('completed');
});

test('technician can add a remark to an assigned job', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'status' => 'in_progress']);

    Livewire::actingAs($this->tech)
        ->test('pages::technician.request-details', ['request' => $job])
        ->set('newRemark', 'Replaced the capacitor')
        ->call('addRemark')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('remarks', [
        'maintenance_request_id' => $job->id,
        'technician_id' => $this->tech->id,
        'remark' => 'Replaced the capacitor',
    ]);
});

test('remark is required', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'status' => 'in_progress']);

    Livewire::actingAs($this->tech)
        ->test('pages::technician.request-details', ['request' => $job])
        ->set('newRemark', '')
        ->call('addRemark')
        ->assertHasErrors(['body']);

    $this->assertDatabaseCount('remarks', 0);
});

test('completed job is locked for progress updates and remarks', function () {
    $job = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'technician_id' => $this->tech->id, 'status' => 'completed']);

    Livewire::actingAs($this->tech)
        ->test('pages::technician.request-details', ['request' => $job])
        ->set('newRemark', 'Too late')
        ->call('addRemark')
        ->assertForbidden();

    $this->assertDatabaseCount('remarks', 0);
});
