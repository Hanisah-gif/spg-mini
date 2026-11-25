<?php

use App\Actions\CreateMaintenanceRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\Block;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $title = '';

    public string $category = '';

    public string $block_id = '';

    public string $level = '';

    public string $room = '';

    public string $description = '';

    #[Computed]
    public function blocks()
    {
        return Block::orderBy('name')->get();
    }

    public function submit()
    {
        // 1. Policy
        $this->authorize('create', MaintenanceRequest::class);

        // 2. Form Request (same rules as the HTTP layer)
        $formRequest = new StoreMaintenanceRequest;
        $validated = $this->validate($formRequest->rules(), $formRequest->messages());

        // 3. Action
        app(CreateMaintenanceRequest::class)->execute([
            ...$validated,
            'resident_id' => Auth::id(),
        ]);

        // 4. Response
        session()->flash('success', 'Maintenance request submitted successfully!');

        return $this->redirect(route('resident.dashboard'));
    }
};
?>

<div class="mx-auto w-full max-w-3xl space-y-6 p-6">
    <div>
        <flux:heading size="xl" level="1">Submit Maintenance Request</flux:heading>
        <flux:subheading>Describe the maintenance problem in your hostel.</flux:subheading>
    </div>

    <form wire:submit="submit" class="space-y-5">
        <flux:input
            wire:model="title"
            label="Request title"
            placeholder="e.g. Broken ceiling fan"
        />

        <flux:select wire:model="category" label="Category" placeholder="Select category">
            @foreach (\App\Models\MaintenanceRequest::CATEGORIES as $option)
                <flux:select.option :value="$option">{{ $option }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid gap-5 md:grid-cols-3">
            <flux:select wire:model="block_id" label="Block" placeholder="Select block">
                @foreach ($this->blocks as $block)
                    <flux:select.option :value="$block->id">{{ $block->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="level"
                type="number"
                min="1"
                max="30"
                label="Level"
                placeholder="e.g. 3"
            />

            <flux:input
                wire:model="room"
                label="Room"
                placeholder="e.g. 203"
            />
        </div>

        <flux:textarea
            wire:model="description"
            label="Description"
            rows="5"
            placeholder="Explain the maintenance problem..."
        />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Submit Request</flux:button>
            <flux:button :href="route('resident.dashboard')" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
