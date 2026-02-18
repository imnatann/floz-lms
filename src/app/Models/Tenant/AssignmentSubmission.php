<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentSubmission extends Model
{
    use HasFactory, UsesTenantConnection, Auditable;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submitted_at',
        'status',
        'grade',
        'feedback',
        'link',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'grade'        => 'decimal:2',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class, 'submission_id');
    }
}
