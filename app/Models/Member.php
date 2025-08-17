<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Member extends Model
{
    protected $fillable = [
        'uid',
        'member_number',
        'full_name',
        'mobile_number',
        'email',
        'status',
        'current_membership_period_id',
        'membership_starts_at',
        'current_plan_type',
        'current_duration_type',
    ];

    protected $casts = [
        'membership_starts_at' => 'date',
    ];

    public function currentMembershipPeriod(): BelongsTo
    {
        return $this->belongsTo(MembershipPeriod::class, 'current_membership_period_id');
    }

    public function membershipPeriods(): HasMany
    {
        return $this->hasMany(MembershipPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(ActiveSession::class);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && 
               ($this->membership_expires_at === null || $this->membership_expires_at->isFuture());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->membership_expires_at !== null && $this->membership_expires_at->isPast();
    }

    public function getDaysUntilExpirationAttribute(): int
    {
        if ($this->membership_expires_at === null) {
            return -1; // No expiration
        }
        
        return max(0, now()->diffInDays($this->membership_expires_at, false));
    }

    public function getMembershipStatusAttribute(): string
    {
        if (!$this->currentMembershipPeriod) {
            return 'No Active Membership';
        }

        if ($this->currentMembershipPeriod->is_expired) {
            return 'Expired';
        }

        if ($this->currentMembershipPeriod->days_until_expiration <= 7) {
            return 'Expiring Soon';
        }

        return 'Active';
    }

    public function getIsMembershipActiveAttribute(): bool
    {
        return $this->currentMembershipPeriod && $this->currentMembershipPeriod->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                    ->where('membership_expires_at', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
                    ->where('membership_expires_at', '>', now())
                    ->where('membership_expires_at', '<=', now()->addDays($days));
    }
}
