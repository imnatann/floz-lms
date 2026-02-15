<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Grade;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Semester;
use App\Models\Tenant\Student;
use App\Models\Tenant\Subject;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OpenApi\Attributes as OA;

class GradeController extends Controller
{
    public function __construct(protected GradeCalculationService $gradeService) {}

    #[OA\Get(
        path: "/tenant/grades",
        tags: ["Grades"],
        summary: "List Grades",
        description: "Get list of grades with filtering"
    )]
    #[OA\Parameter(name: "class_id", in: "query", description: "Filter by class ID", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "semester_id", in: "query", description: "Filter by semester ID", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "subject_id", in: "query", description: "Filter by subject ID", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "List of grades")]
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', Grade::class);

        $classes = SchoolClass::where('status', 'active')->with('academicYear')->get();
        $semesters = Semester::where('is_active', true)->with('academicYear')->get();
        $subjects = Subject::active()->get();

        $grades = null;
        if ($request->class_id && $request->semester_id) {
            $cacheKey = 'grades_' . md5(json_encode($request->only(['class_id', 'semester_id', 'subject_id'])));
            
            $grades = cache()->remember($cacheKey, 60, function () use ($request) {
                return Grade::with(['student', 'subject', 'teacher'])
                    ->where('class_id', $request->class_id)
                    ->where('semester_id', $request->semester_id)
                    ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
                    ->get();
            });
        }

        return Inertia::render('Tenant/Grades/Index', [
            'classes'    => $classes,
            'semesters'  => $semesters,
            'subjects'   => $subjects,
            'grades'     => $grades,
            'filters'    => $request->only(['class_id', 'semester_id', 'subject_id']),
        ]);
    }

    #[OA\Get(
        path: "/tenant/grades/batch",
        tags: ["Grades"],
        summary: "Batch Input View",
        description: "Get view for batch grade input"
    )]
    #[OA\Parameter(name: "class_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "semester_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "subject_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Batch input view")]
    public function batchInput(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Grade::class);

        $class = SchoolClass::with('students')->findOrFail($request->class_id);
        $semester = Semester::findOrFail($request->semester_id);
        $subject = Subject::findOrFail($request->subject_id);

        $students = $class->students()->active()->orderBy('name')->get();

        // Load existing grades
        $existingGrades = Grade::where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->where('subject_id', $subject->id)
            ->get()
            ->keyBy('student_id');

        return Inertia::render('Tenant/Grades/BatchInput', [
            'class'          => $class,
            'semester'       => $semester,
            'subject'        => $subject,
            'students'       => $students,
            'existingGrades' => $existingGrades,
        ]);
    }

    #[OA\Post(
        path: "/tenant/grades/batch",
        tags: ["Grades"],
        summary: "Store Batch Grades",
        description: "Store multiple grades at once"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["class_id", "semester_id", "subject_id", "grades"],
            properties: [
                new OA\Property(property: "class_id", type: "integer"),
                new OA\Property(property: "semester_id", type: "integer"),
                new OA\Property(property: "subject_id", type: "integer"),
                new OA\Property(property: "grades", type: "array", items: new OA\Items(
                    type: "object",
                    properties: [
                        new OA\Property(property: "student_id", type: "integer"),
                        new OA\Property(property: "daily_test_avg", type: "number"),
                        new OA\Property(property: "mid_test", type: "number"),
                        new OA\Property(property: "final_test", type: "number"),
                        new OA\Property(property: "knowledge_score", type: "number"),
                        new OA\Property(property: "skill_score", type: "number"),
                    ]
                )),
            ]
        )
    )]
    #[OA\Response(response: 302, description: "Redirect to index")]
    public function storeBatch(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Grade::class);

        $validated = $request->validate([
            'class_id'    => 'required|exists:tenant.classes,id',
            'semester_id' => 'required|exists:tenant.semesters,id',
            'subject_id'  => 'required|exists:tenant.subjects,id',
            'grades'      => 'required|array',
            'grades.*.student_id' => 'required|exists:tenant.students,id',
        ]);

        $tenant = app('currentTenant');
        $subject = Subject::findOrFail($validated['subject_id']);
        $educationLevel = $tenant->education_level->value ?? 'SMA';

        foreach ($validated['grades'] as $gradeData) {
            $calculated = [];

            if ($educationLevel === 'SD') {
                $calculated = $this->gradeService->calculateSD(
                    $gradeData['daily_test_avg'] ?? 0,
                    $gradeData['mid_test'] ?? 0,
                    $gradeData['final_test'] ?? 0
                );
            } else {
                $calculated = $this->gradeService->calculateSMPSMA(
                    $gradeData['knowledge_score'] ?? 0,
                    $gradeData['skill_score'] ?? 0
                );
            }

            Grade::updateOrCreate(
                [
                    'student_id'  => $gradeData['student_id'],
                    'subject_id'  => $validated['subject_id'],
                    'semester_id' => $validated['semester_id'],
                ],
                array_merge($gradeData, $calculated, [
                    'class_id'   => $validated['class_id'],
                    'teacher_id' => auth()->user()->id ?? null,
                ])
            );
        }

        return redirect()->route('tenant.grades.index', [
            'class_id'    => $validated['class_id'],
            'semester_id' => $validated['semester_id'],
            'subject_id'  => $validated['subject_id'],
        ])->with('success', 'Nilai berhasil disimpan.');
    }
}
