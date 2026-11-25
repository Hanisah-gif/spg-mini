<?php

use App\Models\MaintenanceRequest;
use App\Models\User;

// Role guards: only residents reach the resident area.

test('guests are redirected to login from the resident area', function () {
    $request = MaintenanceRequest::factory()->create();

    $this->get(route('resident.dashboard'))->assertRedirect(route('login'));
    $this->get(route('resident.requests.create'))->assertRedirect(route('login'));
    $this->get(route('resident.requests.show', $request->id))->assertRedirect(route('login'));
    $this->get(route('resident.requests.edit', $request->id))->assertRedirect(route('login'));
});

test('technicians and admins cannot open resident pages', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $request = MaintenanceRequest::factory()->create(['technician_id' => $role === 'technician' ? $user->id : null]);

    $this->actingAs($user)->get(route('resident.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('resident.requests.create'))->assertForbidden();
    $this->actingAs($user)->get(route('resident.requests.show', $request->id))->assertForbidden();
    $this->actingAs($user)->get(route('resident.requests.edit', $request->id))->assertForbidden();
})->with(['technician', 'admin']);

test('the dashboard route sends a resident to the resident dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => 'resident']))
        ->get(route('dashboard'))
        ->assertRedirect(route('resident.dashboard'));
});

// Mass assignment: nobody can promote themselves through a form.

test('role cannot be mass assigned', function () {
    $user = User::create([
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'role' => 'admin',
    ]);

    expect($user->fresh()->role)->toBe('resident');
});

test('registering with a role field still creates a resident', function () {
    $this->post(route('register.store'), [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    expect(User::where('email', 'sneaky@example.com')->firstOrFail()->role)->toBe('resident');
});

test('resident cannot change the owner, status or technician through the edit form', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $request = MaintenanceRequest::factory()->create(['resident_id' => $resident->id]);

    Livewire\Livewire::actingAs($resident)
        ->test('pages::resident.edit-request', ['request' => $request])
        ->set('title', 'Fine title')
        ->call('update');

    $request->refresh();

    expect($request->resident_id)->toBe($resident->id)
        ->and($request->status)->toBe(MaintenanceRequest::STATUS_PENDING)
        ->and($request->technician_id)->toBeNull();
});

// XSS: user input must always be escaped.

test('request title and description are escaped on the details page', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $request = MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => '<script>alert("xss")</script>',
        'description' => '<img src=x onerror=alert(1)>',
    ]);

    $this->actingAs($resident)
        ->get(route('resident.requests.show', $request->id))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
});

test('request title is escaped on the dashboard', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    MaintenanceRequest::factory()->create([
        'resident_id' => $resident->id,
        'title' => '<script>alert("xss")</script>',
    ]);

    $this->actingAs($resident)
        ->get(route('resident.dashboard'))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false);
});
