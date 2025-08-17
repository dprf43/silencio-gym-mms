<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ActiveSession extends Model
{
    protected $fillable = [
        'member_id',
        'attendance_id',
        'check_in_time',
        'session_duration',
        'status',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function getCurrentDurationAttribute(): string
    {
        if ($this->status !== 'active') {
            return $this->session_duration ?? 'N/A';
        }

        $duration = $this->check_in_time->diffInSeconds(now());
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function updateDuration(): void
    {
        if ($this->status === 'active') {
            $this->session_duration = $this->current_duration;
            $this->save();
        }
    }
}
