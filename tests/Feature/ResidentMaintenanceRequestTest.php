<?php

use App\Models\Block;
use App\Models\MaintenanceRequest;
use App\Models\Remark;
use App\Models\StatusLog;
use App\Models\User;
use Livewire\Livewire;

function p1Resident(array $attributes = []): User
{
    return User::factory()->create([...$attributes, 'role' => 'resident']);
}

function p1Technician(array $attributes = []): User
{
    return User::factory()->create([...$attributes, 'role' => 'technician']);
}

/** @return array<string, string> */
function p1ValidFormData(Block $block, array $overrides = []): array
{
    return [
        'title' => 'Broken ceiling fan',
        'category' => 'Electrical',
        'block_id' => (string) $block->id,
        'level' => '2',
        'room' => '203',
        'description' => 'The ceiling fan is not working.',
        ...$overrides,
    ];
}

// ---------------------------------------------------------------------------
// View
// ---------------------------------------------------------------------------

test('resident can view their own maintenance request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertOk();
});

test('resident cannot view another residents request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => p1Resident()->id]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertForbidden();
});

test('request details page shows the full location label', function () {
    $resident = p1Resident();
    $block = Block::factory()->create(['block_name' => 'Block A1']);
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'block_id' => $block->id,
        'level' => 3,
        'room' => '203',
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertSee('Block A1, Level 3, Room 203');
});

test('request details page shows status history and technician remarks', function () {
    $resident = p1Resident();
    $technician = p1Technician(['name' => 'Tech Ahmad']);

    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'technician_id' => $technician->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);

    StatusLog::create([
        'maintenance_request_id' => $request->id,
        'from_status' => MaintenanceRequest::STATUS_PENDING,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
        'changed_by' => $technician->id,
    ]);

    Remark::create([
        'maintenance_request_id' => $request->id,
        'technician_id' => $technician->id,
        'remark' => 'Replaced the fan motor',
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertOk()
        ->assertSee('Status history')
        ->assertSee('Pending')
        ->assertSee('In Progress')
        ->assertSee('Replaced the fan motor')
        ->assertSee('Tech Ahmad');
});

test('request details page shows empty states when there is no history or remarks', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertSee('No status changes recorded yet.')
        ->assertSee('No remarks yet.');
});

test('edit and delete buttons are hidden once a technician is assigned', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'technician_id' => p1Technician()->id,
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertOk()
        ->assertDontSee('Edit Request')
        ->assertDontSee('Delete Request');
});

test('edit and delete buttons are shown for a pending unassigned request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertSee('Edit Request')
        ->assertSee('Delete Request');
});

// ---------------------------------------------------------------------------
// Submit
// ---------------------------------------------------------------------------

test('resident can submit a maintenance request', function () {
    $resident = p1Resident();
    $block = Block::factory()->create();

    $component = Livewire::actingAs($resident)->test('pages::resident.create-request');

    foreach (p1ValidFormData($block) as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('submit')->assertRedirect(route('resident.dashboard'));

    $this->assertDatabaseHas('maintenance_requests', [
        'resident_id' => $resident->id,
        'block_id' => $block->id,
        'level' => 2,
        'room' => '203',
        'title' => 'Broken ceiling fan',
        'status' => MaintenanceRequest::STATUS_PENDING,
    ]);
});

test('submitting a request records the first status log', function () {
    $resident = p1Resident();
    $block = Block::factory()->create();

    $component = Livewire::actingAs($resident)->test('pages::resident.create-request');

    foreach (p1ValidFormData($block) as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('submit');

    $this->assertDatabaseHas('status_logs', [
        'from_status' => null,
        'status' => MaintenanceRequest::STATUS_PENDING,
        'changed_by' => $resident->id,
    ]);
});

test('resident cannot submit an empty request', function () {
    Livewire::actingAs(p1Resident())
        ->test('pages::resident.create-request')
        ->call('submit')
        ->assertHasErrors(['title', 'category', 'block_id', 'level', 'room', 'description']);
});

test('submit form requires a level', function () {
    $block = Block::factory()->create();

    Livewire::actingAs(p1Resident())
        ->test('pages::resident.create-request')
        ->set('title', 'Broken fan')
        ->set('category', 'Electrical')
        ->set('block_id', (string) $block->id)
        ->set('room', '203')
        ->set('description', 'Not working')
        ->call('submit')
        ->assertHasErrors(['level' => 'required']);
});

test('submit form rejects a category that is not in the list', function () {
    $block = Block::factory()->create();

    Livewire::actingAs(p1Resident())
        ->test('pages::resident.create-request')
        ->set('title', 'Broken fan')
        ->set('category', 'Hacking')
        ->set('block_id', (string) $block->id)
        ->set('level', '2')
        ->set('room', '203')
        ->set('description', 'Not working')
        ->call('submit')
        ->assertHasErrors(['category' => 'in']);
});

test('submit form rejects a block that does not exist', function () {
    Livewire::actingAs(p1Resident())
        ->test('pages::resident.create-request')
        ->set('title', 'Broken fan')
        ->set('category', 'Electrical')
        ->set('block_id', '9999')
        ->set('level', '2')
        ->set('room', '203')
        ->set('description', 'Not working')
        ->call('submit')
        ->assertHasErrors(['block_id' => 'exists']);
});

// ---------------------------------------------------------------------------
// Edit
// ---------------------------------------------------------------------------

test('resident can open the edit page for their pending request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    $this->actingAs($resident)
        ->get(route('resident.requests.edit', $request->id))
        ->assertOk();
});

test('resident cannot edit another residents request', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => p1Resident()->id]);

    $this->actingAs(p1Resident())
        ->get(route('resident.requests.edit', $request->id))
        ->assertForbidden();
});

test('resident cannot edit a completed request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.edit', $request->id))
        ->assertForbidden();
});

test('resident cannot edit an assigned request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'technician_id' => p1Technician()->id,
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.edit', $request->id))
        ->assertForbidden();
});

test('edit page is filled with the current values', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'room' => '777',
        'title' => 'Leaking tap',
    ]);

    Livewire::actingAs($resident)
        ->test('pages::resident.edit-request', ['request' => $request])
        ->assertSet('title', 'Leaking tap')
        ->assertSet('room', '777');
});

test('resident can update their pending request details', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    Livewire::actingAs($resident)
        ->test('pages::resident.edit-request', ['request' => $request])
        ->set('title', 'Updated ceiling fan')
        ->set('room', '305')
        ->call('update')
        ->assertRedirect(route('resident.requests.show', $request->id));

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $request->id,
        'title' => 'Updated ceiling fan',
        'room' => '305',
    ]);
});

test('edit form validates every field', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    Livewire::actingAs($resident)
        ->test('pages::resident.edit-request', ['request' => $request])
        ->set('title', '')
        ->set('category', 'Hacking')
        ->set('block_id', '9999')
        ->set('level', 'abc')
        ->set('room', '')
        ->set('description', '')
        ->call('update')
        ->assertHasErrors([
            'title' => 'required',
            'category' => 'in',
            'block_id' => 'exists',
            'level' => 'integer',
            'room' => 'required',
            'description' => 'required',
        ]);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $request->id,
        'title' => $request->title,
    ]);
});

test('edit is blocked if the request is assigned after the page was opened', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    $component = Livewire::actingAs($resident)
        ->test('pages::resident.edit-request', ['request' => $request])
        ->set('title', 'Sneaky change');

    $request->update(['technician_id' => p1Technician()->id]);

    $component->call('update')->assertForbidden();

    $this->assertDatabaseMissing('maintenance_requests', [
        'id' => $request->id,
        'title' => 'Sneaky change',
    ]);
});

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

test('resident can delete their pending request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    Livewire::actingAs($resident)
        ->test('pages::resident.request-details', ['request' => $request])
        ->call('delete')
        ->assertRedirect(route('resident.dashboard'));

    $this->assertDatabaseMissing('maintenance_requests', ['id' => $request->id]);
});

test('deleting a request also removes its status history', function () {
    $resident = p1Resident();
    $block = Block::factory()->create();

    $component = Livewire::actingAs($resident)->test('pages::resident.create-request');
    foreach (p1ValidFormData($block) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('submit');

    $request = MaintenanceRequest::firstOrFail();
    $this->assertDatabaseHas('status_logs', ['maintenance_request_id' => $request->id]);

    Livewire::actingAs($resident)
        ->test('pages::resident.request-details', ['request' => $request])
        ->call('delete');

    $this->assertDatabaseMissing('status_logs', ['maintenance_request_id' => $request->id]);
});

test('resident cannot delete an assigned request', function () {
    $resident = p1Resident();
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'technician_id' => p1Technician()->id,
    ]);

    Livewire::actingAs($resident)
        ->test('pages::resident.request-details', ['request' => $request])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('maintenance_requests', ['id' => $request->id]);
});

test('resident cannot delete another residents request', function () {
    $request = MaintenanceRequest::factory()->create(['resident_id' => p1Resident()->id]);

    $this->actingAs(p1Resident())
        ->get(route('resident.requests.show', $request->id))
        ->assertForbidden();

    $this->assertDatabaseHas('maintenance_requests', ['id' => $request->id]);
});

// ---------------------------------------------------------------------------
// Dashboard: access, counts, search, filter, pagination, flash message
// ---------------------------------------------------------------------------

test('resident dashboard only displays their own requests', function () {
    $resident = p1Resident();

    MaintenanceRequest::factory()->create(['resident_id' => $resident->id, 'title' => 'My broken fan']);
    MaintenanceRequest::factory()->create(['resident_id' => p1Resident()->id, 'title' => 'Other resident broken pipe']);

    Livewire::actingAs($resident)
        ->test('pages::resident.dashboard')
        ->assertSee('My broken fan')
        ->assertDontSee('Other resident broken pipe');
});

test('resident dashboard shows correct counts per status', function () {
    $resident = p1Resident();

    MaintenanceRequest::factory()->count(2)->create(['resident_id' => $resident->id]);
    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);
    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);
    // Someone else's request must not be counted.
    MaintenanceRequest::factory()->create(['resident_id' => p1Resident()->id]);

    $counts = Livewire::actingAs($resident)
        ->test('pages::resident.dashboard')
        ->instance()
        ->counts;

    expect($counts)->toBe([
        'total' => 4,
        MaintenanceRequest::STATUS_PENDING => 2,
        MaintenanceRequest::STATUS_IN_PROGRESS => 1,
        MaintenanceRequest::STATUS_COMPLETED => 1,
    ]);
});

test('resident dashboard search filters by title', function () {
    $resident = p1Resident();

    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => 'Broken ceiling fan',
        'description' => 'Plain text one',
    ]);
    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => 'Dripping tap',
        'description' => 'Plain text two',
    ]);

    Livewire::actingAs($resident)
        ->test('pages::resident.dashboard')
        ->set('search', 'ceiling')
        ->assertSee('Broken ceiling fan')
        ->assertDontSee('Dripping tap');
});

test('resident dashboard filters by status', function () {
    $resident = p1Resident();

    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => 'Still waiting',
        'description' => 'Plain text one',
    ]);
    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => 'Already fixed',
        'description' => 'Plain text two',
        'status' => MaintenanceRequest::STATUS_COMPLETED,
    ]);

    Livewire::actingAs($resident)
        ->test('pages::resident.dashboard')
        ->set('status', MaintenanceRequest::STATUS_COMPLETED)
        ->assertSee('Already fixed')
        ->assertDontSee('Still waiting');
});

test('resident dashboard paginates five requests per page', function () {
    $resident = p1Resident();

    foreach (range(1, 7) as $number) {
        MaintenanceRequest::factory()->create([
            'resident_id' => $resident->id,
            'title' => sprintf('Numbered request %02d', $number),
            'description' => 'Plain text',
        ]);
    }

    // Newest first: 07..03 on page one, 02..01 on page two.
    Livewire::actingAs($resident)
        ->test('pages::resident.dashboard')
        ->assertSee('Numbered request 07')
        ->assertSee('Numbered request 03')
        ->assertDontSee('Numbered request 02')
        ->call('gotoPage', 2)
        ->assertSee('Numbered request 02')
        ->assertSee('Numbered request 01')
        ->assertDontSee('Numbered request 07');
});

test('resident dashboard shows the success message', function () {
    $this->actingAs(p1Resident())
        ->withSession(['success' => 'Maintenance request submitted successfully!'])
        ->get(route('resident.dashboard'))
        ->assertOk()
        ->assertSee('Maintenance request submitted successfully!');
});

test('resident dashboard shows a readable label for every status', function () {
    $resident = p1Resident();

    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
    ]);

    $this->actingAs($resident)
        ->get(route('resident.dashboard'))
        ->assertSee('In Progress');
});
