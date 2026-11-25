<?php

use App\Models\MaintenanceRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        $byStatus = auth()->user()->maintenanceRequests()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['total' => (int) $byStatus->sum()];

        foreach (array_keys(MaintenanceRequest::STATUSES) as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        return $counts;
    }

    #[Computed]
    public function requests()
    {
        $search = trim($this->search);

        return auth()->user()->maintenanceRequests()
            ->with('block')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
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
            <flux:heading size="xl" level="1">Resident Dashboard</flux:heading>
            <flux:subheading>
                Welcome back, {{ auth()->user()->name }}! Manage your hostel maintenance requests here.
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('resident.requests.create')" wire:navigate>
            New Request
        </flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" :heading="session('success')" />
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <flux:card>
            <flux:subheading>Total</flux:subheading>
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
        <flux:heading size="lg">My Maintenance Requests</flux:heading>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search by title or description..."
                />
            </div>

            <flux:select wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach (\App\Models\MaintenanceRequest::STATUSES as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="space-y-3">
            @forelse ($this->requests as $request)
                <a
                    href="{{ route('resident.requests.show', $request->id) }}"
                    wire:navigate
                    wire:key="request-{{ $request->id }}"
                    class="block space-y-2 rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading>{{ $request->title }}</flux:heading>
                        <x-status-badge :status="$request->status" />
                    </div>

                    <flux:text class="text-sm">
                        {{ $request->category }} | {{ $request->locationLabel() }}
                    </flux:text>

                    <flux:text class="line-clamp-2">{{ $request->description }}</flux:text>
                </a>
            @empty
                <flux:text>
                    @if ($search !== '' || $status !== '')
                        No requests match your search or filter.
                    @else
                        You haven't submitted any maintenance requests yet.
                    @endif
                </flux:text>
            @endforelse
        </div>

        <div>
            {{ $this->requests->links() }}
        </div>
    </flux:card>
</div>
