<?php

namespace App\Models\Central;

use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'name', 'slug', 'database_name', 'domain', 'education_level',
        'npsn', 'email', 'phone', 'address', 'logo_url', 'status',
        'subscription_plan', 'max_students', 'expires_at',
    ];

    protected $casts = [
        'education_level' => EducationLevel::class,
        'expires_at'      => 'datetime',
        'max_students'    => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
