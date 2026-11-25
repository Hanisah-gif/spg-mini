<?php

use App\Actions\AddRemark;
use App\Actions\UpdateProgress;
use App\Http\Requests\StoreRemarkRequest;
use App\Http\Requests\UpdateProgressRequest;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public MaintenanceRequest $request;

    public string $progressRemark = '';

    public string $newRemark = '';

    public function mount(MaintenanceRequest $request): void
    {
        $this->authorize('view', $request);

        $this->request = $request->load(['block', 'resident', 'technician']);
    }

    #[Computed]
    public function canUpdateProgress(): bool
    {
        return auth()->user()?->can('updateProgress', $this->request) ?? false;
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

    private function applyStatusTransition(string $targetStatus): void
    {
        $this->authorize('updateProgress', $this->request);

        $formRequest = new UpdateProgressRequest();
        $formRequest->setUserResolver(fn () => auth()->user());
        $formRequest->setRouteResolver(fn () => new class($this->request) {
            public function __construct(private MaintenanceRequest $req) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'request' ? $this->req : $default;
            }
        });

        $validated = Validator::make(
            [
                'status' => $targetStatus,
                'remarks' => $this->progressRemark,
            ],
            $formRequest->rules(),
            $formRequest->messages()
        )->validate();

        $this->request = app(UpdateProgress::class)->execute(
            $this->request,
            auth()->user(),
            $validated['status'],
            $validated['remarks'] ?? null
        );

        $this->progressRemark = '';
        unset($this->canUpdateProgress, $this->statusLogs, $this->remarks);

        session()->flash(
            'success',
            $targetStatus === MaintenanceRequest::STATUS_IN_PROGRESS
                ? 'Job started and marked as In Progress.'
                : 'Job marked as Completed.'
        );
    }

    public function startWork(): void
    {
        $this->applyStatusTransition(MaintenanceRequest::STATUS_IN_PROGRESS);
    }

    public function completeJob(): void
    {
        $this->applyStatusTransition(MaintenanceRequest::STATUS_COMPLETED);
    }

    public function addRemark(): void
    {
        $this->authorize('updateProgress', $this->request);

        $formRequest = new StoreRemarkRequest();
        $validated = Validator::make(
            ['body' => $this->newRemark],
            $formRequest->rules(),
            $formRequest->messages()
        )->validate();

        app(AddRemark::class)->execute(
            $this->request,
            auth()->user(),
            $validated['body']
        );

        $this->newRemark = '';
        unset($this->remarks);

        session()->flash('success', 'Remark added successfully.');
    }
};
?>

<div class="mx-auto w-full max-w-3xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Job Details</flux:heading>
            <flux:subheading>Inspect request details, update progress, and record remarks.</flux:subheading>
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
                <flux:subheading>Reported by</flux:subheading>
                <flux:text>{{ $request->resident?->name ?? 'Unknown resident' }}</flux:text>
            </div>

            <div>
                <flux:subheading>Submitted on</flux:subheading>
                <flux:text>{{ $request->created_at?->format('d M Y, h:i A') ?? '—' }}</flux:text>
            </div>
        </div>

        <div>
            <flux:subheading>Description</flux:subheading>
            <flux:text class="whitespace-pre-line">{{ $request->description }}</flux:text>
        </div>
    </flux:card>

    <flux:card class="space-y-4" x-data="{ showWorkflowHelp: false }">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">Work Progress</flux:heading>
            <flux:button
                type="button"
                size="sm"
                variant="subtle"
                x-on:click="showWorkflowHelp = !showWorkflowHelp"
            >
                <span x-text="showWorkflowHelp ? 'Hide Workflow Guide' : 'Workflow Guide'"></span>
            </flux:button>
        </div>

        <div
            x-show="showWorkflowHelp"
            x-transition
            style="display: none;"
            class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50"
        >
            <flux:text>
                Pending → Start Work (In Progress) → Complete Job (Completed). Once completed, status and remarks are locked.
            </flux:text>
        </div>

        @if ($this->canUpdateProgress)
            <div class="space-y-4">
                <flux:textarea
                    wire:model="progressRemark"
                    label="Progress / Completion Note (Optional)"
                    placeholder="Add work notes or completion details..."
                    rows="3"
                />
                @error('remarks')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div class="flex flex-wrap gap-3">
                    @if ($request->status === \App\Models\MaintenanceRequest::STATUS_PENDING)
                        <x-confirm-modal
                            action="startWork"
                            title="Start work on this job?"
                            message="This will mark the request status as In Progress and log the change."
                            confirm-label="Start Work"
                            variant="primary"
                        >
                            <x-slot:trigger>
                                <flux:button type="button" variant="primary">Start Work</flux:button>
                            </x-slot:trigger>
                        </x-confirm-modal>
                    @endif

                    @if ($request->status === \App\Models\MaintenanceRequest::STATUS_IN_PROGRESS)
                        <x-confirm-modal
                            action="completeJob"
                            title="Complete this maintenance job?"
                            message="This will mark the request as Completed. Further progress updates and remarks will be locked."
                            confirm-label="Complete Job"
                            variant="primary"
                        >
                            <x-slot:trigger>
                                <flux:button type="button" variant="primary">Complete Job</flux:button>
                            </x-slot:trigger>
                        </x-confirm-modal>
                    @endif
                </div>
            </div>
        @else
            <flux:text>
                @if ($request->isCompleted())
                    This job is completed. Progress updates and new remarks are locked.
                @else
                    Progress updates are currently unavailable for this request.
                @endif
            </flux:text>
        @endif
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Remarks</flux:heading>

        @if ($this->canUpdateProgress)
            <form wire:submit="addRemark" class="space-y-3">
                <flux:textarea
                    wire:model="newRemark"
                    label="Add Technician Remark"
                    placeholder="Write a note about parts checked, repairs made, or follow-ups..."
                    rows="3"
                />
                @error('body')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <flux:button type="submit" variant="primary">Save Remark</flux:button>
            </form>
        @endif

        <div class="space-y-3">
            @forelse ($this->remarks as $remark)
                <div wire:key="remark-{{ $remark->id }}" class="space-y-1 border-b border-zinc-200 pb-3 last:border-0 last:pb-0 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:text class="font-medium">{{ $remark->author?->name ?? 'Unknown user' }}</flux:text>
                        @if ($remark->author)
                            <flux:badge size="sm">{{ ucfirst($remark->author->role) }}</flux:badge>
                        @endif
                        <flux:text class="ms-auto text-sm">{{ $remark->created_at?->format('d M Y, h:i A') ?? '' }}</flux:text>
                    </div>
                    <flux:text class="whitespace-pre-line">{{ $remark->body }}</flux:text>
                </div>
            @empty
                <flux:text>No remarks yet.</flux:text>
            @endforelse
        </div>
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Status History</flux:heading>

        @forelse ($this->statusLogs as $log)
            <div wire:key="log-{{ $log->id }}" class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-3 last:border-0 last:pb-0 dark:border-zinc-700">
                @if ($log->from_status)
                    <x-status-badge :status="$log->from_status" />
                    <flux:text>→</flux:text>
                @endif
                <x-status-badge :status="$log->status" />

                <flux:text class="ms-auto text-sm">
                    {{ $log->changedBy?->name ?? 'Unknown user' }} · {{ $log->created_at?->format('d M Y, h:i A') ?? '' }}
                </flux:text>
            </div>
        @empty
            <flux:text>No status changes recorded yet.</flux:text>
        @endforelse
    </flux:card>

    <div>
        <flux:button :href="route('technician.dashboard')" wire:navigate>Back to Dashboard</flux:button>
    </div>
</div>