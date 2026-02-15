<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolClassController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SchoolClass::class, 'class');
    }

    public function index(Request $request)
    {
        $classes = SchoolClass::query()
            ->with(['academicYear', 'homeroomTeacher'])
            ->withCount('students')
            ->when($request->academic_year_id, fn($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tenant/Classes/Index', [
            'classes'       => $classes,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'filters'       => $request->only(['search', 'academic_year_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Classes/Form', [
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'teachers'      => Teacher::where('status', 'active')->orderBy('name')->get(['id', 'name', 'nip']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:50',
            'grade_level'         => 'required|integer|min:1|max:12',
            'academic_year_id'    => 'required|exists:tenant.academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:tenant.teachers,id',
            'max_students'        => 'required|integer|min:1|max:100',
            'status'              => 'required|in:active,inactive',
        ]);

        SchoolClass::create($validated);

        return redirect()->route('tenant.classes.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(SchoolClass $class)
    {
        return Inertia::render('Tenant/Classes/Form', [
            'classData'     => $class,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'teachers'      => Teacher::where('status', 'active')->orderBy('name')->get(['id', 'name', 'nip']),
        ]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:50',
            'grade_level'         => 'required|integer|min:1|max:12',
            'academic_year_id'    => 'required|exists:tenant.academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:tenant.teachers,id',
            'max_students'        => 'required|integer|min:1|max:100',
            'status'              => 'required|in:active,inactive',
        ]);

        $class->update($validated);

        return redirect()->route('tenant.classes.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $class)
    {
        if ($class->students()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Kelas masih memiliki siswa. Pindahkan siswa terlebih dahulu.');
        }

        $class->delete();

        return redirect()->route('tenant.classes.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }
}
