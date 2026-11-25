<?php

use App\Models\MaintenanceRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Admin Dashboard')] class extends Component
{
    use WithPagination;

    // Filters are kept in the URL so the admin can refresh/share the view.
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $block = '';

    #[Url(except: false)]
    public bool $unassignedOnly = false;

    public function mount(): void
    {
        // Policy check (MaintenanceRequestPolicy::viewAny) on top of the role:admin route middleware.
        $this->authorize('viewAny', MaintenanceRequest::class);
    }

    // Go back to page 1 whenever any filter changes.
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category', 'block', 'unassignedOnly'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'category', 'block', 'unassignedOnly');
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => MaintenanceRequest::count(),
            'pending' => MaintenanceRequest::where('status', MaintenanceRequest::STATUS_PENDING)->count(),
            'in_progress' => MaintenanceRequest::where('status', MaintenanceRequest::STATUS_IN_PROGRESS)->count(),
            'completed' => MaintenanceRequest::where('status', MaintenanceRequest::STATUS_COMPLETED)->count(),
            'unassigned' => MaintenanceRequest::whereNull('technician_id')
                ->where('status', '!=', MaintenanceRequest::STATUS_COMPLETED)
                ->count(),
        ];
    }

    #[Computed]
    public function categories(): array
    {
        return MaintenanceRequest::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    #[Computed]
    public function blocks()
    {
        return \App\Models\Block::orderBy('block_name')->get(['id', 'block_name']);
    }

    #[Computed]
    public function requests()
    {
        $search = trim($this->search);

        return MaintenanceRequest::query()
            ->with(['resident:id,name', 'technician:id,name', 'block:id,block_name'])
            // Query builder bindings keep the search safe from SQL injection.
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('room', 'like', "%{$search}%")
                        ->orWhereHas('resident', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->block !== '', fn ($q) => $q->where('block_id', $this->block))
            ->when($this->unassignedOnly, fn ($q) => $q->whereNull('technician_id'))
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="p-6 space-y-6">

    <div>
        <flux:heading size="xl" level="1">Admin Dashboard</flux:heading>
        <flux:text class="mt-1">
            Welcome back, {{ auth()->user()->name }}. Monitor and manage every maintenance request in the hostel.
        </flux:text>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            'total' => 'Total Requests',
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'unassigned' => 'Unassigned',
        ] as $key => $label)
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700" wire:key="stat-{{ $key }}">
                <flux:text class="text-sm">{{ $label }}</flux:text>
                <p class="mt-2 text-3xl font-bold" data-test="stat-{{ $key }}">{{ $this->stats[$key] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 space-y-4">
        <div class="grid gap-4 md:grid-cols-5">
            <flux:input
                wire:model.live.debounce.400ms="search"
                icon="magnifying-glass"
                placeholder="Search title, room or resident..."
                class="md:col-span-2"
            />

            <flux:select wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach (\App\Models\MaintenanceRequest::STATUSES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="block">
                <flux:select.option value="">All blocks</flux:select.option>
                @foreach ($this->blocks as $b)
                    <flux:select.option value="{{ $b->id }}">{{ $b->block_name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="category">
                <flux:select.option value="">All categories</flux:select.option>
                @foreach ($this->categories as $cat)
                    <flux:select.option value="{{ $cat }}">{{ $cat }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:checkbox wire:model.live="unassignedOnly" label="Show unassigned requests only" />

            <div class="flex items-center gap-3">
                <flux:icon.loading wire:loading class="size-5 text-zinc-400" />
                <flux:button size="sm" variant="ghost" wire:click="clearFilters">Clear filters</flux:button>
            </div>
        </div>
    </div>

    {{-- Requests table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Request</th>
                    <th class="px-4 py-3">Resident</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Technician</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->requests as $req)
                    <tr wire:key="req-{{ $req->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-zinc-500">{{ $req->id }}</td>
                        <td class="px-4 py-3">
                            {{-- {{ }} escapes output: safe against XSS --}}
                            <div class="font-medium">{{ $req->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $req->category }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $req->resident?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $req->locationLabel() }}</td>
                        <td class="px-4 py-3">
                            @if ($req->technician)
                                {{ $req->technician->name }}
                            @else
                                <flux:badge size="sm" color="red">Unassigned</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$req->status" />
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" :href="route('admin.requests.show', $req)" wire:navigate>
                                Manage
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-500">
                            No maintenance requests match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $this->requests->links() }}
    </div>
</div>
