<?php

namespace App\Models\Central;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'plan_name', 'price', 'billing_cycle',
        'starts_at', 'ends_at', 'status', 'auto_renew',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'auto_renew' => 'boolean',
        'status'     => SubscriptionStatus::class,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active && $this->ends_at->isFuture();
    }
}
