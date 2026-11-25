<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenanceRequest = $this->route('request');

        return $maintenanceRequest instanceof MaintenanceRequest
            && ($this->user()?->can('updateProgress', $maintenanceRequest) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    MaintenanceRequest::STATUS_IN_PROGRESS,
                    MaintenanceRequest::STATUS_COMPLETED,
                ]),
            ],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please choose a progress status.',
            'status.in' => 'Invalid status transition selected.',
            'remarks.max' => 'Remarks must not exceed 2000 characters.',
        ];
    }
}