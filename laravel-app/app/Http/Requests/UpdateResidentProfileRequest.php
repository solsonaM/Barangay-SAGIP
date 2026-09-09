<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResidentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isResident() ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', 'in:male,female'],
            'civil_status' => ['nullable', 'in:single,married,widowed,separated'],
            'purok_sitio' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'household_members_count' => ['required', 'integer', 'min:1', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
            'home_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'home_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
