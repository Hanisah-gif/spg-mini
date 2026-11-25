<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTechnicianRequest extends FormRequest
{
    /**
     * Authorization is handled by MaintenanceRequestPolicy::assign()
     * inside the Livewire component.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Must be an existing user whose role is technician
            // (stops an admin from "assigning" a resident or another admin).
            'technicianId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'technician'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'technicianId.required' => 'Please select a technician.',
            'technicianId.exists' => 'The selected user is not a valid technician.',
        ];
    }
}
