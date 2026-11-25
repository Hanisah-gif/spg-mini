<?php

use App\Actions\UpdateMaintenanceRequestAction;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\Block;
use App\Models\MaintenanceRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public MaintenanceRequest $request;

    public string $title = '';

    public string $category = '';

    public string $block_id = '';

    public string $level = '';

    public string $room = '';

    public string $description = '';

    public function mount(MaintenanceRequest $request)
    {
        // Policy: resident owns it, it is Pending and unassigned.
        $this->authorize('update', $request);

        $this->request = $request;

        $this->title = $request->title;
        $this->category = $request->category;
        $this->block_id = (string) $request->block_id;
        $this->level = (string) $request->level;
        $this->room = $request->room;
        $this->description = $request->description;
    }

    #[Computed]
    public function blocks()
    {
        return Block::orderBy('name')->get();
    }

    public function update()
    {
        // 1. Policy (checked again, the request may have been assigned since the page loaded)
        $this->authorize('update', $this->request);

        // 2. Form Request
        $formRequest = new UpdateMaintenanceRequest;
        $validated = $this->validate($formRequest->rules(), $formRequest->messages());

        // 3. Action
        app(UpdateMaintenanceRequestAction::class)->execute($this->request, $validated);

        // 4. Response
        session()->flash('success', 'Maintenance request updated successfully!');

        return $this->redirect(route('resident.requests.show', $this->request->getKey()));
    }
};
?>

<div class="mx-auto w-full max-w-3xl space-y-6 p-6">
    <div>
        <flux:heading size="xl" level="1">Edit Maintenance Request</flux:heading>
        <flux:subheading>Update your pending maintenance request.</flux:subheading>
    </div>

    <form wire:submit="update" class="space-y-5">
        <flux:input wire:model="title" label="Request title" />

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

            <flux:input wire:model="level" type="number" min="1" max="30" label="Level" />

            <flux:input wire:model="room" label="Room" />
        </div>

        <flux:textarea wire:model="description" label="Description" rows="5" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
            <flux:button :href="route('resident.requests.show', $request->getKey())" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
