<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\UrgencyLevel;
use Illuminate\Database\Eloquent\Model;

class EmergencyRequest extends Model
{
    protected $fillable = [
        'resident_id',
        'description',
        'category',
        'category_confidence',
        'urgency',
        'urgency_confidence',
        'needs_review',
        'review_reason',
        'validated_at',
        'validated_by',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category_confidence' => 'decimal:4',
            'urgency_confidence' => 'decimal:4',
            'urgency' => UrgencyLevel::class,
            'status' => RequestStatus::class,
            'needs_review' => 'boolean',
            'validated_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function resident()
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function statusLogs()
    {
        return $this->hasMany(RequestStatusLog::class)->orderBy('created_at');
    }

    public function assignments()
    {
        return $this->hasMany(ResponseAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(ResponseAssignment::class)->whereNull('completed_at')->latestOfMany();
    }

    public function classificationLogs()
    {
        return $this->hasMany(TokenizationClassificationLog::class);
    }

    /**
     * Record a status change and append it to the tracking timeline
     * (Feature 7: Real-Time Urgent Status Tracking).
     */
    public function transitionTo(RequestStatus $status, ?string $note = null, ?int $changedBy = null): void
    {
        $this->update(['status' => $status]);
        $this->statusLogs()->create([
            'status' => $status->value,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);
    }
}
