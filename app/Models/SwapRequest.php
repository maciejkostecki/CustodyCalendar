<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SwapRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'date',
        'requested_by_role',
        'from_role',
        'to_role',
        'status',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
        'active_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Keep active_date in sync with status so the unique index enforces
        // "one pending request per day".
        static::saving(function (SwapRequest $request) {
            $request->active_date = $request->status === self::STATUS_PENDING
                ? $request->date
                : null;
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
