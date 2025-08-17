<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidLog extends Model
{
    protected $fillable = [
        'card_uid',
        'action',
        'status',
        'message',
        'card_data',
        'timestamp',
        'device_id',
    ];

    protected $casts = [
        'card_data' => 'array',
        'timestamp' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('timestamp', today());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUnknownCards($query)
    {
        return $query->where('action', 'unknown_card');
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function getFormattedMessageAttribute(): string
    {
        $timestamp = $this->timestamp->format('H:i:s');
        return "[{$timestamp}] {$this->message}";
    }
}
