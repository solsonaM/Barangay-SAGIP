<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MlClassificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'emergency_request_id',
        'endpoint',
        'request_payload',
        'response_payload',
        'response_time_ms',
        'was_successful',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'was_successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function emergencyRequest()
    {
        return $this->belongsTo(EmergencyRequest::class);
    }
}
