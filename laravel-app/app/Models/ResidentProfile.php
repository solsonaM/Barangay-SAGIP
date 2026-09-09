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
        'home_latitude',
        'home_longitude',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'home_latitude' => 'decimal:7',
            'home_longitude' => 'decimal:7',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
