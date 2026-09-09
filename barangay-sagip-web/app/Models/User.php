<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function residentProfile()
    {
        return $this->hasOne(ResidentProfile::class);
    }

    public function responsePersonnel()
    {
        return $this->hasOne(ResponsePersonnel::class);
    }

    public function emergencyRequests()
    {
        return $this->hasMany(EmergencyRequest::class, 'resident_id');
    }

    public function isResident(): bool
    {
        return $this->role === UserRole::Resident;
    }

    public function isPersonnel(): bool
    {
        return $this->role === UserRole::Personnel;
    }

    public function isOfficial(): bool
    {
        return $this->role === UserRole::Official;
    }
}
