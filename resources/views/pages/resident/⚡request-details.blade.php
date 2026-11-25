<?php

use App\Actions\DeleteMaintenanceRequestAction;
use App\Models\MaintenanceRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public MaintenanceRequest $request;

    public function mount(MaintenanceRequest $request)
    {
        $this->authorize('view', $request);

        $this->request = $request;
    }

    #[Computed]
    public function statusLogs()
    {
        return $this->request->statusLogs()->with('changedBy')->get();
    }

    #[Computed]
    public function remarks()
    {
        return $this->request->remarks()->with('author')->get();
    }

    public function delete()
    {
        // 1. Policy: resident owns it, it is Pending and unassigned.
        $this->authorize('delete', $this->request);

        // 2. Action
        app(DeleteMaintenanceRequestAction::class)->execute($this->request);

        // 3. Response
        session()->flash('success', 'Maintenance request deleted successfully!');

        return $this->redirect(route('resident.dashboard'));
    }
};
?>

<div class="mx-auto w-full max-w-3xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Request Details</flux:heading>
            <flux:subheading>View your maintenance request information.</flux:subheading>
        </div>

        <x-status-badge :status="$request->status" />
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" :heading="session('success')" />
    @endif

    <flux:card class="space-y-5">
        <div>
            <flux:subheading>Request title</flux:subheading>
            <flux:heading size="lg">{{ $request->title }}</flux:heading>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <flux:subheading>Category</flux:subheading>
                <flux:text>{{ $request->category }}</flux:text>
            </div>

            <div>
                <flux:subheading>Location</flux:subheading>
                <flux:text>{{ $request->locationLabel() }}</flux:text>
            </div>

            <div>
                <flux:subheading>Submitted on</flux:subheading>
                <flux:text>{{ $request->created_at->format('d M Y, h:i A') }}</flux:text>
            </div>

            <div>
                <flux:subheading>Assigned technician</flux:subheading>
                <flux:text>{{ $request->technician?->name ?? 'Not assigned yet' }}</flux:text>
            </div>
        </div>

        <div>
            <flux:subheading>Description</flux:subheading>
            <flux:text class="whitespace-pre-line">{{ $request->description }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('update', $request)
                <flux:button variant="primary" :href="route('resident.requests.edit', $request->getKey())" wire:navigate>
                    Edit Request
                </flux:button>
            @endcan

            @can('delete', $request)
                <x-confirm-modal
                    action="delete"
                    title="Delete this request?"
                    message="Are you sure you want to delete this maintenance request? This cannot be undone."
                    confirm-label="Delete"
                >
                    <x-slot:trigger>
                        <flux:button type="button" variant="danger">Delete Request</flux:button>
                    </x-slot:trigger>
                </x-confirm-modal>
            @endcan
        </div>
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Status history</flux:heading>

        @forelse ($this->statusLogs as $log)
            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-3 last:border-0 last:pb-0 dark:border-zinc-700">
                @if ($log->from_status)
                    <x-status-badge :status="$log->from_status" />
                    <flux:text>→</flux:text>
                @endif
                <x-status-badge :status="$log->status" />

                <flux:text class="ms-auto text-sm">
                    {{ $log->changedBy?->name ?? 'Unknown user' }} · {{ $log->created_at->format('d M Y, h:i A') }}
                </flux:text>
            </div>
        @empty
            <flux:text>No status changes recorded yet.</flux:text>
        @endforelse
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Remarks</flux:heading>

        @forelse ($this->remarks as $remark)
            <div class="space-y-1 border-b border-zinc-200 pb-3 last:border-0 last:pb-0 dark:border-zinc-700">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text class="font-medium">{{ $remark->author?->name ?? 'Unknown user' }}</flux:text>
                    @if ($remark->author)
                        <flux:badge size="sm">{{ ucfirst($remark->author->role) }}</flux:badge>
                    @endif
                    <flux:text class="ms-auto text-sm">{{ $remark->created_at->format('d M Y, h:i A') }}</flux:text>
                </div>
                <flux:text class="whitespace-pre-line">{{ $remark->body }}</flux:text>
            </div>
        @empty
            <flux:text>No remarks yet.</flux:text>
        @endforelse
    </flux:card>

    <div>
        <flux:button :href="route('resident.dashboard')" wire:navigate>Back to Dashboard</flux:button>
    </div>
</div>
