<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tenant\ReportCard;
use App\Models\Tenant\Grade;

class PdfGeneratorService
{
    /**
     * Generate PDF for a report card.
     */
    public function generateReportCardPdf(ReportCard $reportCard): string
    {
        $reportCard->load(['student', 'schoolClass.homeroomTeacher', 'semester.academicYear']);

        $grades = Grade::where('student_id', $reportCard->student_id)
            ->where('semester_id', $reportCard->semester_id)
            ->with('subject')
            ->orderBy('subject_id')
            ->get();

        $tenant = app('currentTenant');
        $educationLevel = strtolower($tenant->education_level->value ?? 'sma');

        $template = "pdf.report-card-{$educationLevel}";

        $pdf = Pdf::loadView($template, [
            'reportCard' => $reportCard,
            'grades'     => $grades,
            'student'    => $reportCard->student,
            'class'      => $reportCard->schoolClass,
            'semester'   => $reportCard->semester,
            'tenant'     => $tenant,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = "rapor_{$reportCard->student->nis}_{$reportCard->semester_id}.pdf";
        $path = storage_path("app/report-cards/{$filename}");

        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        // Update report card with PDF path
        $reportCard->update(['pdf_url' => $path]);

        return $path;
    }
}
