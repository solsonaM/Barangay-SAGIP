<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'birthdate',
        'sex',
        'civil_status',
        'purok_sitio',
        'address',
        'household_members_count',
        'emergency_contact_name',
        'emergency_contact_number',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
