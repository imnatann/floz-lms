<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'cover_image_url',
        'target_audience', // enum: all, teachers, students
        'is_pinned',
        'type',
        'is_published',
        'user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
