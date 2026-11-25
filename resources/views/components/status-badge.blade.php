@props(['status'])

@php
    use App\Models\MaintenanceRequest;

    $color = match ($status) {
        MaintenanceRequest::STATUS_PENDING => 'yellow',
        MaintenanceRequest::STATUS_IN_PROGRESS => 'blue',
        MaintenanceRequest::STATUS_COMPLETED => 'green',
        default => 'zinc',
    };

    $label = MaintenanceRequest::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
@endphp

<flux:badge :color="$color" size="sm" {{ $attributes }}>{{ $label }}</flux:badge>
