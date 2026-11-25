<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreRemarkRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Please enter a remark before saving.',
            'body.max' => 'Remark must not exceed 2000 characters.',
        ];
    }
}