<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MaintenanceRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(MaintenanceRequest::CATEGORIES)],
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'level' => ['required', 'integer', 'min:1', 'max:30'],
            'room' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a request title.',
            'category.required' => 'Please select a category.',
            'category.in' => 'Please choose a category from the list.',
            'block_id.required' => 'Please select your hostel block.',
            'block_id.exists' => 'The selected block does not exist.',
            'level.required' => 'Please enter your level (floor).',
            'level.integer' => 'The level must be a number.',
            'level.min' => 'The level must be at least 1.',
            'level.max' => 'The level must not be higher than 30.',
            'room.required' => 'Please enter your room number.',
            'description.required' => 'Please describe the maintenance problem.',
        ];
    }
}
