{{--
    Reusable Alpine.js confirmation dialog, shared by all modules (owned by Person 2).

    Short form (plain text message):
    <x-confirm-modal action="delete" title="Delete request?" message="This cannot be undone." confirm-label="Delete">
        <x-slot:trigger><flux:button variant="danger">Delete</flux:button></x-slot:trigger>
    </x-confirm-modal>

    Long form (custom content in the slot, can use Alpine x-text):
    <x-confirm-modal action="overrideStatus" title="Confirm status override" confirm-label="Yes, update">
        <x-slot:trigger><flux:button variant="primary">Update status</flux:button></x-slot:trigger>
        Change status to <strong x-text="..."></strong>?
    </x-confirm-modal>

    Props:
      action         Livewire method called on confirm (required), e.g. "delete", "startJob"
      params         Optional array of arguments for that method
      title          Dialog heading
      message        Optional plain-text message (escaped)
      confirm-label  Confirm button text
      cancel-label   Cancel button text
      variant        Flux variant of the confirm button: danger | primary

    Security: show user data with {{ }} or x-text only, never {!! !!} or x-html.
--}}
@props([
    'action',
    'params' => [],
    'title' => 'Are you sure?',
    'message' => null,
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'variant' => 'danger',
])

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" {{ $attributes }}>
    {{-- Whatever is passed as the trigger opens the dialog --}}
    <div x-on:click="open = true">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
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

            @if ($slot->isNotEmpty())
                <div class="text-sm">{{ $slot }}</div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" x-on:click="open = false">{{ $cancelLabel }}</flux:button>

                <flux:button
                    type="button"
                    :variant="$variant"
                    x-on:click="open = false; $wire.call({{ \Illuminate\Support\Js::from($action) }}, ...{{ \Illuminate\Support\Js::from(array_values($params)) }})"
                    data-test="confirm-modal-confirm"
                >
                    {{ $confirmLabel }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
