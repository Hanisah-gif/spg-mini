<?php

use App\Models\Block;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->technician = User::factory()->create(['role' => 'technician', 'name' => 'Tech Ali']);
    $this->resident = User::factory()->create(['role' => 'resident', 'name' => 'Resident Aina']);
});

/*
|--------------------------------------------------------------------------
| Access control (role middleware + Policy)
|--------------------------------------------------------------------------
*/

test('guest is redirected to login from the admin dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('admin can open the admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard');
});

test('non-admin users cannot open the admin dashboard', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
})->with(['resident', 'technician']);

test('non-admin users cannot open the admin request details page', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    $this->actingAs($user)
        ->get(route('admin.requests.show', $request))
        ->assertForbidden();
})->with(['resident', 'technician']);

test('dashboard redirect sends admin to the admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

/*
|--------------------------------------------------------------------------
| Dashboard: list, search, filters
|--------------------------------------------------------------------------
*/

test('admin dashboard lists requests from every resident', function () {
    $otherResident = User::factory()->create(['role' => 'resident']);

    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Broken fan in A1']);
    MaintenanceRequest::factory()->create(['resident_id' => $otherResident->id, 'title' => 'Leaking pipe in B2']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->assertSee('Broken fan in A1')
        ->assertSee('Leaking pipe in B2');
});

test('admin dashboard shows correct status counts', function () {
    MaintenanceRequest::factory()->count(2)->create(['resident_id' => $this->resident->id, 'status' => 'pending']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'status' => 'in_progress', 'technician_id' => $this->technician->id]);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'status' => 'completed', 'technician_id' => $this->technician->id]);

    $component = Livewire::actingAs($this->admin)->test('pages::admin.dashboard');

    expect($component->instance()->stats)->toMatchArray([
        'total' => 4,
        'pending' => 2,
        'in_progress' => 1,
        'completed' => 1,
        'unassigned' => 2,
    ]);
});

test('admin can search requests by title', function () {
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Broken ceiling fan']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Clogged sink']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('search', 'ceiling')
        ->assertSee('Broken ceiling fan')
        ->assertDontSee('Clogged sink');
});

test('admin can search requests by resident name', function () {
    $otherResident = User::factory()->create(['role' => 'resident', 'name' => 'Zulkifli']);

    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Aina request']);
    MaintenanceRequest::factory()->create(['resident_id' => $otherResident->id, 'title' => 'Zul request']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('search', 'Zulkifli')
        ->assertSee('Zul request')
        ->assertDontSee('Aina request');
});

test('admin can filter requests by status', function () {
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Pending job', 'status' => 'pending']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Finished job', 'status' => 'completed']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('status', 'completed')
        ->assertSee('Finished job')
        ->assertDontSee('Pending job');
});

test('admin can filter unassigned requests only', function () {
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Nobody yet']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Already given', 'technician_id' => $this->technician->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('unassignedOnly', true)
        ->assertSee('Nobody yet')
        ->assertDontSee('Already given');
});

test('admin can filter requests by block', function () {
    $blockA = Block::factory()->create(['block_name' => 'Block A']);
    $blockB = Block::factory()->create(['block_name' => 'Block B']);

    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Fan in A', 'block_id' => $blockA->id]);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Pipe in B', 'block_id' => $blockB->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('block', (string) $blockB->id)
        ->assertSee('Pipe in B')
        ->assertDontSee('Fan in A');
});

test('admin can search requests by room', function () {
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Corridor job', 'room' => 'Level 2 corridor']);
    MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'title' => 'Room job', 'room' => '203']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.dashboard')
        ->set('search', 'corridor')
        ->assertSee('Corridor job')
        ->assertDontSee('Room job');
});

test('admin dashboard paginates results', function () {
    MaintenanceRequest::factory()->count(15)->create(['resident_id' => $this->resident->id]);

    $component = Livewire::actingAs($this->admin)->test('pages::admin.dashboard');

    expect($component->instance()->requests)->toHaveCount(10)
        ->and($component->instance()->requests->total())->toBe(15);
});

/*
|--------------------------------------------------------------------------
| Request details
|--------------------------------------------------------------------------
*/

test('admin can view request details with resident information', function () {
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'title' => 'Window will not close',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request))
        ->assertOk()
        ->assertSee('Window will not close')
        ->assertSee('Resident Aina')
        ->assertSee($this->resident->email);
});

test('request details show problem location and where the resident lives', function () {
    $home = Block::factory()->create(['block_name' => 'Block A']);
    $other = Block::factory()->create(['block_name' => 'Block B']);
    $this->resident->update(['block_id' => $home->id, 'room' => '203']);

    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'block_id' => $other->id,
        'level' => 2,
        'room' => 'Corridor',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request))
        ->assertOk()
        ->assertSee('Block B, Level 2, Room Corridor')
        ->assertSee('Block A, Room 203');
});

test('request details list all technician remarks', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);
    $request->remarks()->createMany([
        ['technician_id' => $this->technician->id, 'remark' => 'Part ordered'],
        ['technician_id' => $this->technician->id, 'remark' => 'Fixed and tested'],
    ]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->assertSee('Part ordered')
        ->assertSee('Fixed and tested')
        ->assertSee('Tech Ali');
});

test('request details escape html in user input (XSS protection)', function () {
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'title' => '<script>alert("xss")</script>',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

/*
|--------------------------------------------------------------------------
| Assign technician
|--------------------------------------------------------------------------
*/

test('admin can assign a technician to a request', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('technicianId', $this->technician->id)
        ->call('assignTechnician')
        ->assertHasNoErrors();

    expect($request->fresh()->technician_id)->toBe($this->technician->id);
});

test('admin can reassign a request to another technician', function () {
    $newTech = User::factory()->create(['role' => 'technician']);
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'technician_id' => $this->technician->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('technicianId', $newTech->id)
        ->call('assignTechnician');

    expect($request->fresh()->technician_id)->toBe($newTech->id);
});

test('technician is required when assigning', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('technicianId', null)
        ->call('assignTechnician')
        ->assertHasErrors(['technicianId' => 'required']);
});

test('admin cannot assign a user who is not a technician', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('technicianId', $this->resident->id)
        ->call('assignTechnician')
        ->assertHasErrors(['technicianId']);

    expect($request->fresh()->technician_id)->toBeNull();
});

test('admin cannot reassign a completed request', function () {
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $this->resident->id,
        'status' => 'completed',
        'technician_id' => $this->technician->id,
    ]);
    $newTech = User::factory()->create(['role' => 'technician']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('technicianId', $newTech->id)
        ->call('assignTechnician')
        ->assertForbidden();

    expect($request->fresh()->technician_id)->toBe($this->technician->id);
});

/*
|--------------------------------------------------------------------------
| Override status
|--------------------------------------------------------------------------
*/

test('admin can override status and the change is logged', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('newStatus', 'completed')
        ->set('adminRemarks', 'Fixed during routine check.')
        ->call('overrideStatus')
        ->assertHasNoErrors();

    expect($request->fresh())
        ->status->toBe('completed')
        ->admin_remarks->toBe('Fixed during routine check.');

    $this->assertDatabaseHas('status_logs', [
        'maintenance_request_id' => $request->id,
        'from_status' => 'pending',
        'status' => 'completed',
        'changed_by' => $this->admin->id,
    ]);
});

test('override rejects an invalid status value', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('newStatus', 'hacked')
        ->call('overrideStatus')
        ->assertHasErrors(['newStatus']);

    expect($request->fresh()->status)->toBe('pending');
    $this->assertDatabaseCount('status_logs', 0);
});

test('override rejects setting the same status again', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('newStatus', 'pending')
        ->call('overrideStatus')
        ->assertHasErrors(['newStatus']);

    $this->assertDatabaseCount('status_logs', 0);
});

test('html tags are stripped from admin remarks (input sanitization)', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->set('newStatus', 'in_progress')
        ->set('adminRemarks', '<b>Urgent</b><script>alert(1)</script>')
        ->call('overrideStatus');

    expect($request->fresh()->admin_remarks)->toBe('Urgentalert(1)');
});

test('status history shows who changed the status', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);
    $request->statusLogs()->create([
        'from_status' => 'pending',
        'status' => 'in_progress',
        'changed_by' => $this->technician->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->assertSee('Tech Ali')
        ->assertSee('In Progress');
});

/*
|--------------------------------------------------------------------------
| onDelete rules & shared confirm modal
|--------------------------------------------------------------------------
*/

test('status history is kept when the user who changed it is deleted', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);
    $log = $request->statusLogs()->create([
        'from_status' => 'pending',
        'status' => 'in_progress',
        'changed_by' => $this->technician->id,
    ]);

    $this->technician->delete();

    expect($log->fresh())->not->toBeNull()
        ->and($log->fresh()->changed_by)->toBeNull();

    Livewire::actingAs($this->admin)
        ->test('pages::admin.request-details', ['maintenanceRequest' => $request])
        ->assertSee('Unknown user');
});

test('remarks are kept when the technician account is deleted', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);
    $remark = $request->remarks()->create([
        'technician_id' => $this->technician->id,
        'remark' => 'Replaced the fuse',
    ]);

    $this->technician->delete();

    expect($remark->fresh())->not->toBeNull()
        ->and($remark->fresh()->technician_id)->toBeNull();
});

test('deleting a request removes its status logs and remarks', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);
    $request->statusLogs()->create(['status' => 'in_progress', 'changed_by' => $this->admin->id]);
    $request->remarks()->create(['technician_id' => $this->technician->id, 'remark' => 'Note']);

    $request->delete();

    $this->assertDatabaseCount('status_logs', 0);
    $this->assertDatabaseCount('remarks', 0);
});

test('request details page uses the shared confirm modal for status override', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => $this->resident->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.requests.show', $request))
        ->assertOk()
        ->assertSee('Confirm status override')
        ->assertSee('data-test="confirm-modal-confirm"', false);
});
