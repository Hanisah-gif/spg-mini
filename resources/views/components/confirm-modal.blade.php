{{--
    STAND-IN for Person 2's reusable Alpine confirmation modal.
    On merge, keep Person 2's version and adjust the props in the resident pages if they differ.

    Usage:
    <x-confirm-modal action="delete" title="Delete request?" message="..." confirm-label="Delete">
        <x-slot:trigger><flux:button variant="danger">Delete</flux:button></x-slot:trigger>
    </x-confirm-modal>

    `action` is the name of the Livewire method that runs when the user confirms.
--}}
@props([
    'action',
    'title' => 'Are you sure?',
    'message' => null,
    'confirmLabel' => 'Confirm',
    'variant' => 'danger',
])

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" {{ $attributes }}>
    <div x-on:click="open = true">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/60" x-on:click="open = false"></div>

        <div class="relative w-full max-w-md space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ $title }}</flux:heading>

            @if ($message)
                <flux:text>{{ $message }}</flux:text>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="open = false">Cancel</flux:button>

                <flux:button
                    type="button"
                    :variant="$variant"
                    wire:click="{{ $action }}"
                    x-on:click="open = false"
                >
                    {{ $confirmLabel }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
