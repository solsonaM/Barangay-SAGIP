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
            'address' => [
                'required',
                'string',
                'max:500',
                'regex:/^\s*(?:house\s+|unit\s+|#\s*)?\d+[A-Za-z]?(?:[-\/]\d+[A-Za-z0-9]*)?\s*,\s*[^,\s][^,]*\s*,\s*[^,\s][^,]*\s*,\s*[^,\s][^,]*\s*,\s*[^,\s][^,]*\s*$/iu',
            ],
            'household_members_count' => ['required', 'integer', 'min:1', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'address.regex' => 'Address must follow: House/Unit Number, Street/Road, Barangay, Municipality/City, Province. Example: 225, Provincial Road, Calatagan Tibang, Virac, Catanduanes.',
        ];
    }
}
