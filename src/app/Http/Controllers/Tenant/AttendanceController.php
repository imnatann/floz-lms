<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\Tenant\StudentAbsentNotification;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        
        $selectedDate = $request->date ?? Carbon::today()->format('Y-m-d');
        $selectedClassId = $request->class_id;

        $students = [];
        
        if ($selectedClassId) {
            $students = Student::where('class_id', $selectedClassId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($student) use ($selectedDate) {
                    $attendance = Attendance::where('student_id', $student->id)
                        ->where('date', $selectedDate)
                        ->first();
                        
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->nis,
                        'status' => $attendance ? $attendance->status : 'present',
                        'notes' => $attendance ? $attendance->notes : '',
                    ];
                });
        }

        return inertia('Tenant/Attendance/Index', [
            'classes' => $classes,
            'students' => $students,
            'filters' => [
                'date' => $selectedDate,
                'class_id' => $selectedClassId,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:tenant.classes,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:tenant.students,id',
            'attendances.*.status' => 'required|in:present,sick,permit,absent',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        foreach ($validated['attendances'] as $data) {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'date' => $validated['date'],
                ],
                [
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]
            );

            // Notify if Absent (Alpha)
            if ($data['status'] === 'absent') {
                $attendance->load('student.user'); // Ensure relations are loaded
                if ($attendance->student && $attendance->student->user) {
                     // Check if notification already sent today? 
                     // For now, simpler is better. Triggers every time saved as absent.
                    $attendance->student->user->notify(new StudentAbsentNotification($attendance));
                }
            }
        }

        return redirect()->back()->with('success', 'Absensi berhasil disimpan.');
    }
}
