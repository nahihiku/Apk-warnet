<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PcClient extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
        'session_end_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'current_member_id');
    }

    public function computeRemainingSeconds(): int
    {
        if ($this->is_locked) {
            return (int) $this->remaining_seconds_cache;
        }
        if (!$this->session_end_at) {
            return 0;
        }
        $diff = Carbon::now()->diffInSeconds($this->session_end_at, false);
        return max(0, (int) $diff);
    }

    public function isOnline(): bool
    {
        if (!$this->last_seen_at) return false;
        return $this->last_seen_at->diffInSeconds(Carbon::now()) <= 12;
    }
}
