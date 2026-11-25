<?php

use App\Models\MaintenanceRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        $byStatus = auth()->user()->assignedMaintenanceRequests()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['total' => (int) $byStatus->sum()];

        foreach (array_keys(MaintenanceRequest::STATUSES) as $statusKey) {
            $counts[$statusKey] = (int) ($byStatus[$statusKey] ?? 0);
        }

        return $counts;
    }

    #[Computed]
    public function jobs()
    {
        return auth()->user()->assignedMaintenanceRequests()
            ->with(['block', 'resident'])
            ->when(
                array_key_exists($this->status, MaintenanceRequest::STATUSES),
                fn ($query) => $query->where('status', $this->status)
            )
            ->latest('id')
            ->paginate(5);
    }
};
?>

<div class="space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Technician Dashboard</flux:heading>
            <flux:subheading>
                Welcome back, {{ auth()->user()->name }}. View and update your assigned maintenance jobs.
            </flux:subheading>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" :heading="session('success')" />
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <flux:card>
            <flux:subheading>Total Assigned</flux:subheading>
            <flux:heading size="xl">{{ $this->counts['total'] }}</flux:heading>
        </flux:card>

        @foreach (\App\Models\MaintenanceRequest::STATUSES as $value => $label)
            <flux:card>
                <flux:subheading>{{ $label }}</flux:subheading>
                <flux:heading size="xl">{{ $this->counts[$value] }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">My Assigned Jobs</flux:heading>

            <div class="w-full sm:w-56">
                <flux:select wire:model.live="status" aria-label="Filter by status">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    @foreach (\App\Models\MaintenanceRequest::STATUSES as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($this->jobs as $job)
                <a
                    href="{{ route('technician.requests.show', $job->id) }}"
                    wire:navigate
                    wire:key="job-{{ $job->id }}"
                    class="block space-y-2 rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading>{{ $job->title }}</flux:heading>
                        <x-status-badge :status="$job->status" />
                    </div>

                    <flux:text class="text-sm">
                        {{ $job->category }} | {{ $job->locationLabel() }} | Reported by: {{ $job->resident?->name ?? 'Unknown resident' }}
                    </flux:text>

                    <flux:text class="line-clamp-2">{{ $job->description }}</flux:text>
                </a>
            @empty
                <flux:text>
                    @if ($status !== '')
                        No assigned jobs match the selected status.
                    @else
                        No maintenance jobs have been assigned to you yet.
                    @endif
                </flux:text>
            @endforelse
        </div>

        <div>
            {{ $this->jobs->links() }}
        </div>
    </flux:card>
</div>