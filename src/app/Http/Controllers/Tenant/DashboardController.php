<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Student;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Grade;
use App\Models\Tenant\ReportCard;
use App\Models\Tenant\Teacher;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\TeachingAssignment;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: "/tenant/dashboard",
        tags: ["Tenant Dashboard"],
        summary: "Tenant Dashboard Stats",
        description: "Get statistics for the tenant dashboard"
    )]
    #[OA\Response(
        response: 200,
        description: "Dashboard stats",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "stats", type: "object",
                    properties: [
                        new OA\Property(property: "total_students", type: "integer"),
                        new OA\Property(property: "total_teachers", type: "integer"),
                        new OA\Property(property: "total_classes", type: "integer"),
                        new OA\Property(property: "total_staff", type: "integer"),
                        new OA\Property(property: "attendance_present", type: "integer"),
                        new OA\Property(property: "attendance_absent", type: "integer"),
                    ]
                ),
                new OA\Property(property: "recent_announcements", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'total_students'   => Student::active()->count(),
            'total_teachers'   => Teacher::where('status', 'active')->count(),
            'total_classes'    => SchoolClass::count(),
            'total_staff'      => Teacher::count(),
            'attendance_present' => Attendance::whereDate('date', $today)->where('status', 'present')->count(),
            'attendance_absent'  => Attendance::whereDate('date', $today)->where('status', '!=', 'present')->count(),
        ];

        $recentAnnouncements = Announcement::where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        // Role-based Dashboard Logic
        $user = auth()->user();

        if ($user->isStudent()) {
            $student = $user->student()->with(['class.homeroomTeacher'])->first();
            
            if (!$student) {
                return Inertia::render('Tenant/Dashboard/StudentDashboard', [
                    'student' => $user,
                    'stats' => [
                        'attendance_percentage' => 0,
                    ],
                    'recentAnnouncements' => $recentAnnouncements,
                ]);
            }
            
            // Student Stats
            $totalAttendance = Attendance::where('student_id', $student->id)->count();
            $presentAttendance = Attendance::where('student_id', $student->id)->where('status', 'present')->count();
            $attendancePercentage = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100) : 0;

            $studentStats = [
                'attendance_percentage' => $attendancePercentage,
                // 'assignments_pending' => ...
            ];

            return Inertia::render('Tenant/Dashboard/StudentDashboard', [
                'student' => $student,
                'stats' => $studentStats,
                'recentAnnouncements' => $recentAnnouncements,
            ]);
        }

        if ($user->isTeacher()) {
            $teacher = $user->teacher;

            if (!$teacher) {
                return Inertia::render('Tenant/Dashboard/TeacherDashboard', [
                    'teacher' => $user,
                    'stats' => [
                        'my_classes_count'   => 0,
                        'my_students_count'  => 0,
                    ],
                    'recentAnnouncements' => $recentAnnouncements,
                ]);
            }

            // Teacher Stats
            // Get classes where teacher is homeroom OR has a teaching assignment
            $classIds = TeachingAssignment::where('teacher_id', $teacher->id)->pluck('class_id')
                ->merge(SchoolClass::where('homeroom_teacher_id', $teacher->id)->pluck('id'))
                ->unique();

            $teacherStats = [
                'my_classes_count'   => $classIds->count(),
                'my_students_count'  => Student::whereIn('class_id', $classIds)->active()->count(),
            ];

            return Inertia::render('Tenant/Dashboard/TeacherDashboard', [
                'teacher' => $teacher,
                'stats' => $teacherStats,
                'recentAnnouncements' => $recentAnnouncements,
            ]);
        }

        // Admin Dashboard (Default)
        return Inertia::render('Tenant/Dashboard/AdminDashboard', [
            'stats'        => $stats,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }
}
