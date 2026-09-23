<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    protected $fillable = ['user_id', 'design_id', 'amount', 'currency', 'status', 'provider', 'provider_ref', 'paid_at'];

    protected $casts = ['amount' => 'float', 'paid_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function markPaid(?string $ref = null): void
    {
        if ($this->status === 'paid') {
            return;
        }
        $this->status = 'paid';
        $this->paid_at = now();
        if ($ref) {
            $this->provider_ref = $ref;
        }
        $this->save();
        $this->design()->increment('purchases');
        $this->design()->increment('trending_score', 25);
    }
}
