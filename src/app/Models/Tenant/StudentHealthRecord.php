<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class StudentHealthRecord extends Model
{
    use UsesTenantConnection, Auditable;

    protected $fillable = [
        'student_id',
        'blood_type',
        'height',
        'weight',
        'medical_history',
        'allergies',
        'special_needs',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
