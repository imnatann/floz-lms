<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ReportCard;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Semester;
use App\Services\PdfGeneratorService;
use App\Services\ReportCardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OpenApi\Attributes as OA;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportCardService $reportCardService,
        protected PdfGeneratorService $pdfService,
    ) {}

    #[OA\Get(
        path: "/tenant/report-cards",
        tags: ["Report Cards"],
        summary: "List Report Cards",
        description: "Get list of report cards with filtering"
    )]
    #[OA\Parameter(name: "class_id", in: "query", description: "Filter by class ID", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "semester_id", in: "query", description: "Filter by semester ID", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "status", in: "query", description: "Filter by status", required: false, schema: new OA\Schema(type: "string", enum: ["draft", "published"]))]
    #[OA\Response(response: 200, description: "List of report cards")]
    public function index(Request $request)
    {
        $reportCards = ReportCard::query()
            ->with(['student', 'schoolClass', 'semester.academicYear'])
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->when($request->semester_id, fn($q, $s) => $q->where('semester_id', $s))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $classes = SchoolClass::where('status', 'active')->get(['id', 'name']);
        $semesters = Semester::with('academicYear')->get();

        return Inertia::render('Tenant/ReportCards/Index', [
            'reportCards' => $reportCards,
            'classes'     => $classes,
            'semesters'   => $semesters,
            'filters'     => $request->only(['class_id', 'semester_id', 'status']),
        ]);
    }

    #[OA\Post(
        path: "/tenant/report-cards/generate",
        tags: ["Report Cards"],
        summary: "Generate Report Cards",
        description: "Generate report cards for a class and semester"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["class_id", "semester_id"],
            properties: [
                new OA\Property(property: "class_id", type: "integer"),
                new OA\Property(property: "semester_id", type: "integer"),
            ]
        )
    )]
    #[OA\Response(response: 302, description: "Redirect to index")]
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'class_id'    => 'required|exists:tenant.classes,id',
            'semester_id' => 'required|exists:tenant.semesters,id',
        ]);

        $class = SchoolClass::with('students')->findOrFail($validated['class_id']);

        foreach ($class->students as $student) {
            $this->reportCardService->generate(
                $student->id,
                $validated['class_id'],
                $validated['semester_id']
            );
        }

        // Calculate rankings
        $this->reportCardService->calculateRankings(
            $validated['class_id'],
            $validated['semester_id']
        );

        return redirect()->route('tenant.report-cards.index', [
            'class_id'    => $validated['class_id'],
            'semester_id' => $validated['semester_id'],
        ])->with('success', 'Rapor berhasil digenerate untuk seluruh siswa di kelas ini.');
    }

    #[OA\Get(
        path: "/tenant/report-cards/{reportCard}",
        tags: ["Report Cards"],
        summary: "Show Report Card",
        description: "Get detailed view of a report card"
    )]
    #[OA\Parameter(name: "reportCard", in: "path", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Report card details")]
    public function show(ReportCard $reportCard)
    {
        $reportCard->load([
            'student',
            'schoolClass.homeroomTeacher',
            'semester.academicYear',
        ]);

        $grades = \App\Models\Tenant\Grade::where('student_id', $reportCard->student_id)
            ->where('semester_id', $reportCard->semester_id)
            ->with('subject')
            ->orderBy('subject_id')
            ->get();

        return Inertia::render('Tenant/ReportCards/Show', [
            'reportCard' => $reportCard,
            'grades'     => $grades,
        ]);
    }

    #[OA\Post(
        path: "/tenant/report-cards/{reportCard}/publish",
        tags: ["Report Cards"],
        summary: "Publish Report Card",
        description: "Mark a report card as published"
    )]
    #[OA\Parameter(name: "reportCard", in: "path", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 302, description: "Redirect back")]
    public function publish(ReportCard $reportCard)
    {
        $this->reportCardService->publish($reportCard);

        return back()->with('success', 'Rapor berhasil dipublikasikan.');
    }

    #[OA\Get(
        path: "/tenant/report-cards/{reportCard}/pdf",
        tags: ["Report Cards"],
        summary: "Download Report Card PDF",
        description: "Download the PDF version of the report card"
    )]
    #[OA\Parameter(name: "reportCard", in: "path", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(
        response: 200,
        description: "PDF File",
        content: new OA\MediaType(
            mediaType: "application/pdf",
            schema: new OA\Schema(type: "string", format: "binary")
        )
    )]
    public function downloadPdf(ReportCard $reportCard)
    {
        $path = $this->pdfService->generateReportCardPdf($reportCard);

        return response()->download($path);
    }
}
