<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class CounselingNote extends Model
{
    use UsesTenantConnection, Auditable;

    protected $fillable = [
        'student_id',
        'counselor_id',
        'date',
        'category', // behavior, academic, social, career
        'severity', // low, medium, high, critical
        'title',
        'description',
        'follow_up_action',
        'status', // open, in_progress, resolved
        'is_confidential',
    ];

    protected $casts = [
        'date' => 'date',
        'is_confidential' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'counselor_id');
    }
}
