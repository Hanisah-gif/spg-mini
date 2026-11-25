<?php

use App\Actions\Admin\AssignTechnician;
use App\Actions\Admin\OverrideRequestStatus;
use App\Http\Requests\Admin\AssignTechnicianRequest;
use App\Http\Requests\Admin\OverrideStatusRequest;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Request')] class extends Component
{
    public MaintenanceRequest $maintenanceRequest;

    // Assign technician form
    public $technicianId = null;

    // Override status form
    public string $newStatus = '';
    public ?string $adminRemarks = null;

    public function mount(MaintenanceRequest $maintenanceRequest): void
    {
        $this->authorize('view', $maintenanceRequest);

        $this->maintenanceRequest = $maintenanceRequest;
        $this->technicianId = $maintenanceRequest->technician_id;
        $this->newStatus = $maintenanceRequest->status;
    }

    #[Computed]
    public function technicians()
    {
        return User::query()
            ->where('role', 'technician')
            ->withCount(['assignedMaintenanceRequests as active_jobs_count' => fn ($q) => $q->where('status', '!=', MaintenanceRequest::STATUS_COMPLETED)])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function remarks()
    {
        return $this->maintenanceRequest->remarks()
            ->with('technician:id,name')
            ->latest()
            ->get();
    }

    #[Computed]
    public function statusLogs()
    {
        return $this->maintenanceRequest->statusLogs()
            ->with('changer:id,name,role')
            ->latest()
            ->get();
    }

    /**
     * Livewire component = slim orchestrator:
     * 1) authorize (Policy) -> 2) validate (Form Request) -> 3) run Action -> 4) respond.
     */
    public function assignTechnician(AssignTechnician $action): void
    {
        $this->authorize('assign', $this->maintenanceRequest);

        $form = new AssignTechnicianRequest();
        $validated = $this->validate($form->rules(), $form->messages());

        $technician = User::findOrFail($validated['technicianId']);

        $this->maintenanceRequest = $action->execute($this->maintenanceRequest, $technician);

        Flux::modal('assign-technician')->close();
        Flux::toast(variant: 'success', text: "Assigned to {$technician->name}.");
    }

    public function overrideStatus(OverrideRequestStatus $action): void
    {
        $this->authorize('overrideStatus', $this->maintenanceRequest);

        // Sanitize free text before validation.
        $this->adminRemarks = OverrideStatusRequest::sanitize($this->adminRemarks);

        $form = new OverrideStatusRequest();
        $validated = $this->validate($form->rules(), $form->messages());

        $this->maintenanceRequest = $action->execute(
            $this->maintenanceRequest,
            $validated['newStatus'],
            Auth::user(),
            $validated['adminRemarks'] ?? null,
        );

        $this->adminRemarks = null;
        unset($this->statusLogs);

        Flux::toast(variant: 'success', text: 'Status updated to '.$this->maintenanceRequest->statusLabel().'.');
    }
};
?>


<div class="p-6 space-y-6 max-w-5xl">
    @php
        $req = $maintenanceRequest;
    @endphp

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:link :href="route('admin.dashboard')" wire:navigate class="text-sm">&larr; Back to all requests</flux:link>
            <flux:heading size="xl" level="1" class="mt-2">Request #{{ $req->id }}: {{ $req->title }}</flux:heading>
            <div class="mt-2 flex items-center gap-2">
                <x-status-badge :status="$req->status" data-test="current-status" />
                <flux:text class="text-sm">Submitted {{ $req->created_at->format('d M Y, h:i A') }}</flux:text>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- LEFT: Alpine.js tabs (client-side only, no server round trip) --}}
        <div class="lg:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700" x-data="{ tab: 'details' }">
            <div class="flex border-b border-zinc-200 dark:border-zinc-700" role="tablist">
                @foreach (['details' => 'Details', 'history' => 'Status History', 'remarks' => 'Remarks'] as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="tab = '{{ $key }}'"
                        :aria-selected="tab === '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-b-2 border-zinc-900 font-semibold dark:border-white'
                            : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                        class="px-4 py-3 text-sm"
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Tab: details --}}
            <div x-show="tab === 'details'" class="p-6 space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm">Category</flux:text>
                        <p class="font-medium">{{ $req->category }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm">Problem Location</flux:text>
                        <p class="font-medium" data-test="problem-location">{{ $req->locationLabel() }}</p>
                    </div>
                </div>
                <div>
                    <flux:text class="text-sm">Description</flux:text>
                    {{-- Escaped output; whitespace-pre-line keeps line breaks without using {!! !!} --}}
                    <p class="whitespace-pre-line">{{ $req->description }}</p>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm">Resident Information</flux:heading>
                    <div class="mt-3 grid gap-5 sm:grid-cols-2">
                        <div>
                            <flux:text class="text-sm">Name</flux:text>
                            <p class="font-medium">{{ $req->resident?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <flux:text class="text-sm">Email</flux:text>
                            <p class="font-medium">{{ $req->resident?->email ?? '—' }}</p>
                        </div>
                        <div>
                            <flux:text class="text-sm">Lives in</flux:text>
                            <p class="font-medium" data-test="resident-home">
                                {{ $req->resident?->block?->block_name ?? '—' }}{{ $req->resident?->room ? ', Room '.$req->resident->room : '' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: status history --}}
            <div x-show="tab === 'history'" x-cloak class="p-6">
                @forelse ($this->statusLogs as $log)
                    <div wire:key="log-{{ $log->id }}" class="flex gap-3 border-l-2 border-zinc-200 pb-5 pl-4 last:pb-0 dark:border-zinc-700">
                        <div>
                            <p class="text-sm">
                                <span class="font-medium">{{ $log->changer?->name ?? 'Unknown user' }}</span>
                                <span class="text-zinc-500">({{ ucfirst($log->changer?->role ?? '-') }})</span>
                                changed status
                                @if ($log->from_status)
                                    from <strong>{{ \App\Models\MaintenanceRequest::STATUSES[$log->from_status] ?? $log->from_status }}</strong>
                                @endif
                                to <strong>{{ \App\Models\MaintenanceRequest::STATUSES[$log->status] ?? $log->status }}</strong>
                            </p>
                            <flux:text class="text-xs">{{ $log->created_at->format('d M Y, h:i A') }}</flux:text>
                        </div>
                    </div>
                @empty
                    <flux:text>No status changes recorded yet.</flux:text>
                @endforelse
            </div>

            {{-- Tab: remarks --}}
            <div x-show="tab === 'remarks'" x-cloak class="p-6 space-y-5">
                <div>
                    <flux:text class="text-sm">Admin Remarks</flux:text>
                    <p class="whitespace-pre-line">{{ $req->admin_remarks ?? 'No remarks yet.' }}</p>
                </div>
                <div>
                    <flux:text class="text-sm">Technician Remarks</flux:text>
                    @forelse ($this->remarks as $remark)
                        <div wire:key="remark-{{ $remark->id }}" class="mt-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <p class="whitespace-pre-line">{{ $remark->remark }}</p>
                            <flux:text class="mt-1 text-xs">{{ $remark->technician?->name ?? 'Unknown' }} · {{ $remark->created_at->format('d M Y, h:i A') }}</flux:text>
                        </div>
                    @empty
                        <p>No updates from technician yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: admin actions --}}
        <div class="space-y-6">

            {{-- Assign technician (Flux modal) --}}
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 space-y-3">
                <flux:heading size="sm">Assigned Technician</flux:heading>

                @if ($req->technician)
                    <p class="font-medium" data-test="assigned-technician">{{ $req->technician->name }}</p>
                @else
                    <flux:badge color="red" size="sm">Unassigned</flux:badge>
                @endif

                @can('assign', $req)
                    <flux:modal.trigger name="assign-technician">
                        <flux:button size="sm" class="w-full" icon="user-plus">
                            {{ $req->technician ? 'Reassign technician' : 'Assign technician' }}
                        </flux:button>
                    </flux:modal.trigger>
                @else
                    <flux:text class="text-xs">Completed requests cannot be reassigned.</flux:text>
                @endcan
            </div>

            {{-- Override status (Alpine.js confirmation dialog) --}}
            <div
                class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 space-y-4"
                x-data="{ labels: @js(\App\Models\MaintenanceRequest::STATUSES) }"
            >
                <div>
                    <flux:heading size="sm">Override Status</flux:heading>
                    <flux:text class="text-xs mt-1">Use only when necessary. Every change is logged with your name and time.</flux:text>
                </div>

                <flux:select wire:model="newStatus" label="New status">
                    @foreach (\App\Models\MaintenanceRequest::STATUSES as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="adminRemarks" label="Admin remarks (optional)" rows="3" placeholder="Reason for the override..." />

                {{-- Shared Alpine confirmation dialog (resources/views/components/confirm-modal.blade.php).
                     Values are rendered with x-text (never x-html) so user input cannot inject HTML. --}}
                <x-confirm-modal title="Confirm status override" action="overrideStatus" confirm-label="Yes, update">
                    <x-slot:trigger>
                        <flux:button variant="primary" class="w-full">Update status</flux:button>
                    </x-slot:trigger>

                    <p>
                        Change status from
                        <strong>{{ $req->statusLabel() }}</strong>
                        to
                        <strong x-text="labels[$wire.newStatus] ?? $wire.newStatus"></strong>?
                    </p>
                    <p class="mt-2 text-zinc-500" x-show="$wire.adminRemarks">
                        Remarks: <span x-text="$wire.adminRemarks"></span>
                    </p>
                </x-confirm-modal>

                @error('newStatus')
                    <flux:text class="text-sm !text-red-500">{{ $message }}</flux:text>
                @enderror
                @error('adminRemarks')
                    <flux:text class="text-sm !text-red-500">{{ $message }}</flux:text>
                @enderror
            </div>
        </div>
    </div>

    {{-- Flux modal for assigning a technician --}}
    <flux:modal name="assign-technician" class="md:w-96">
        <form wire:submit="assignTechnician" class="space-y-5">
            <div>
                <flux:heading size="lg">Assign technician</flux:heading>
                <flux:text class="mt-1">Request #{{ $req->id }}: {{ $req->title }}</flux:text>
            </div>

            <flux:select wire:model="technicianId" label="Technician" placeholder="Select a technician...">
                @foreach ($this->technicians as $tech)
                    <flux:select.option value="{{ $tech->id }}">
                        {{ $tech->name }} ({{ $tech->active_jobs_count }} active {{ \Illuminate\Support\Str::plural('job', $tech->active_jobs_count) }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Assign</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
