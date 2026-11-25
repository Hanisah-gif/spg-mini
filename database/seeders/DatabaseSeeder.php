<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a fully usable demo environment for all three roles.
     * Every account's password is: password
     */
    public function run(): void
    {
        // --- Blocks (pre-populated by admin, PBL assumption 3) ---
        $blocks = collect(['Block A', 'Block B', 'Block C', 'Block D'])
            ->mapWithKeys(fn ($name) => [$name => Block::create(['block_name' => $name])]);

        // --- Admin (no block/room) ---
        $admin = User::factory()->create([
            'name' => 'Hostel Admin',
            'email' => 'admin@hostel.test',
            'role' => 'admin',
        ]);

        // --- Technicians (no block/room) ---
        $tech1 = User::factory()->create(['name' => 'Ahmad Technician', 'email' => 'tech1@hostel.test', 'role' => 'technician']);
        $tech2 = User::factory()->create(['name' => 'Siva Technician', 'email' => 'tech2@hostel.test', 'role' => 'technician']);

        // --- Residents (each lives in a block + room) ---
        $test = User::factory()->create([
            'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'resident',
            'block_id' => $blocks['Block A']->id, 'room' => '101',
        ]);
        $aisyah = User::factory()->create([
            'name' => 'Aisyah Resident', 'email' => 'resident1@hostel.test', 'role' => 'resident',
            'block_id' => $blocks['Block A']->id, 'room' => '203',
        ]);
        $meiLing = User::factory()->create([
            'name' => 'Mei Ling Resident', 'email' => 'resident2@hostel.test', 'role' => 'resident',
            'block_id' => $blocks['Block C']->id, 'room' => '315',
        ]);

        // Helper: a request located in the resident's own room (room 203 -> level 2).
        $inOwnRoom = fn (User $r) => [
            'resident_id' => $r->id,
            'block_id' => $r->block_id,
            'level' => (int) substr($r->room, 0, 1),
            'room' => $r->room,
        ];

        // --- Pending, unassigned (waiting for admin) ---
        foreach ([$test, $aisyah, $meiLing] as $resident) {
            MaintenanceRequest::factory()->count(2)->create($inOwnRoom($resident));
        }

        // --- Problem outside the resident's own room (common area) ---
        MaintenanceRequest::factory()->create([
            'resident_id' => $aisyah->id,
            'title' => 'Corridor light not working',
            'category' => 'Electrical',
            'block_id' => $blocks['Block B']->id,
            'level' => 2,
            'room' => 'Corridor',
        ]);

        // --- Pending, already assigned ---
        MaintenanceRequest::factory()->create([
            ...$inOwnRoom($aisyah),
            'title' => 'Leaking bathroom tap',
            'category' => 'Plumbing',
            'technician_id' => $tech1->id,
        ]);

        // --- In progress (status changed by technician) ---
        $inProgress = MaintenanceRequest::factory()->create([
            ...$inOwnRoom($meiLing),
            'title' => 'Ceiling fan not spinning',
            'category' => 'Electrical',
            'technician_id' => $tech2->id,
            'status' => 'in_progress',
        ]);
        $inProgress->statusLogs()->create(['from_status' => 'pending', 'status' => 'in_progress', 'changed_by' => $tech2->id]);
        $inProgress->remarks()->create(['technician_id' => $tech2->id, 'remark' => 'Checked wiring. Capacitor needs replacement, part ordered.']);

        // --- Completed (full history + remarks) ---
        $completed = MaintenanceRequest::factory()->create([
            ...$inOwnRoom($test),
            'title' => 'Broken wardrobe door',
            'category' => 'Furniture',
            'technician_id' => $tech1->id,
            'status' => 'completed',
        ]);
        $completed->statusLogs()->createMany([
            ['from_status' => 'pending', 'status' => 'in_progress', 'changed_by' => $tech1->id],
            ['from_status' => 'in_progress', 'status' => 'completed', 'changed_by' => $tech1->id],
        ]);
        $completed->remarks()->createMany([
            ['technician_id' => $tech1->id, 'remark' => 'Inspected. Hinge is broken.'],
            ['technician_id' => $tech1->id, 'remark' => 'Hinge replaced and door realigned.'],
        ]);

        // --- Admin override example ---
        $overridden = MaintenanceRequest::factory()->create([
            'resident_id' => $meiLing->id,
            'title' => 'Flickering stairway light',
            'category' => 'Electrical',
            'block_id' => $blocks['Block C']->id,
            'level' => 1,
            'room' => 'Staircase',
            'technician_id' => $tech2->id,
            'status' => 'completed',
            'admin_remarks' => 'Closed by admin: fixed during block-wide wiring check.',
        ]);
        $overridden->statusLogs()->create(['from_status' => 'pending', 'status' => 'completed', 'changed_by' => $admin->id]);
    }
}
