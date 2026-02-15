<?php

namespace App\Services;

use App\Models\Tenant\Grade;
use App\Models\Tenant\ReportCard;
use App\Models\Tenant\Student;
use App\Models\Tenant\Attendance;
use Illuminate\Support\Facades\DB;

class ReportCardService
{
    /**
     * Generate or update a report card for a student.
     */
    public function generate(int $studentId, int $classId, int $semesterId): ReportCard
    {
        $grades = Grade::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->get();

        $totalScore = $grades->sum('final_score');
        $averageScore = $grades->count() > 0 ? round($totalScore / $grades->count(), 2) : 0;

        // Calculate attendance summary
        $attendance = $this->calculateAttendance($studentId, $semesterId);

        return ReportCard::updateOrCreate(
            [
                'student_id'  => $studentId,
                'semester_id' => $semesterId,
            ],
            [
                'class_id'           => $classId,
                'total_score'        => $totalScore,
                'average_score'      => $averageScore,
                'attendance_present' => $attendance['present'],
                'attendance_sick'    => $attendance['sick'],
                'attendance_permit'  => $attendance['permit'],
                'attendance_absent'  => $attendance['absent'],
                'status'             => 'draft',
            ]
        );
    }

    /**
     * Calculate class rankings for a semester.
     */
    public function calculateRankings(int $classId, int $semesterId): void
    {
        $reportCards = ReportCard::where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->orderByDesc('average_score')
            ->get();

        $rank = 1;
        foreach ($reportCards as $reportCard) {
            $reportCard->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Publish a report card.
     */
    public function publish(ReportCard $reportCard): void
    {
        $reportCard->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Calculate attendance summary for a student in a semester.
     */
    protected function calculateAttendance(int $studentId, int $semesterId): array
    {
        $semester = \App\Models\Tenant\Semester::find($semesterId);

        if (!$semester) {
            return ['present' => 0, 'sick' => 0, 'permit' => 0, 'absent' => 0];
        }

        $attendances = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$semester->start_date, $semester->end_date])
            ->get();

        return [
            'present' => $attendances->where('status', 'present')->count(),
            'sick'    => $attendances->where('status', 'sick')->count(),
            'permit'  => $attendances->where('status', 'permit')->count(),
            'absent'  => $attendances->where('status', 'absent')->count(),
        ];
    }
}
