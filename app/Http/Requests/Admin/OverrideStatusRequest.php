<?php

namespace App\Http\Requests\Admin;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverrideStatusRequest extends FormRequest
{
    /**
     * Authorization is handled by MaintenanceRequestPolicy::overrideStatus()
     * inside the Livewire component.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Whitelist: only the three known statuses are accepted.
            'newStatus' => ['required', 'string', Rule::in(array_keys(MaintenanceRequest::STATUSES))],
            'adminRemarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'newStatus.required' => 'Please choose a status.',
            'newStatus.in' => 'Invalid status selected.',
            'adminRemarks.max' => 'Remarks may not be longer than 1000 characters.',
        ];
    }

    /**
     * Input sanitization: trim and strip HTML tags from the remarks
     * before they are validated and saved.
     */
    public static function sanitize(?string $remarks): ?string
    {
        $clean = trim(strip_tags((string) $remarks));

        return $clean === '' ? null : $clean;
    }
}
