<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsePersonnel extends Model
{
    protected $table = 'response_personnel';

    protected $fillable = [
        'user_id',
        'name',
        'specialization',
        'phone_number',
        'latitude',
        'longitude',
        'is_available',
        'current_workload',
        'last_location_update',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_location_update' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(ResponseAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->assignments()->whereNull('completed_at');
    }

    public static function specializations(): array
    {
        return ['medical', 'fire', 'peace_order', 'disaster', 'general_assistance'];
    }
}
