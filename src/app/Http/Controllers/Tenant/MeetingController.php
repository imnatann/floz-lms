<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Meeting;
use App\Models\Tenant\MeetingMaterial;
use App\Models\Tenant\TeachingAssignment;
use App\Models\Tenant\OfflineAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MeetingController extends Controller
{
    /**
     * Course list page — shows all teaching assignments as courses.
     * Teacher: all their own teaching assignments.
     * Student: all subjects for their class.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = TeachingAssignment::with(['teacher', 'subject', 'schoolClass', 'academicYear']);

        if ($user->isTeacher() && $user->teacher) {
            $query->where('teacher_id', $user->teacher->id);
        } elseif ($user->isStudent() && $user->student) {
            $query->where('class_id', $user->student->class_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        $courses = $query->latest()->get();

        // For each course, generate meetings if they don't exist yet (for existing teaching assignments)
        foreach ($courses as $course) {
            if ($course->meetings()->count() === 0) {
                Meeting::generateForTeachingAssignment($course->id);
            }
        }

        return Inertia::render('Tenant/Courses/Index', [
            'courses' => $courses,
            'is_teacher' => $user->isTeacher(),
        ]);
    }

    /**
     * Course detail page — shows all meetings with their contents.
     */
    public function show(TeachingAssignment $teachingAssignment)
    {
        $user = auth()->user();
        $this->authorize('viewCourse', [Meeting::class, $teachingAssignment]);

        // Generate meetings if they don't exist (for pre-existing teaching assignments)
        if ($teachingAssignment->meetings()->count() === 0) {
            Meeting::generateForTeachingAssignment($teachingAssignment->id);
        }

        $teachingAssignment->load(['teacher', 'subject', 'schoolClass', 'academicYear']);

        $meetings = $teachingAssignment->meetings()
            ->with([
                'materials',
                'assignments' => function ($q) {
                    $q->with('questions');
                },
            ])
            ->get();

        // For students: filter to only unlocked meetings & load their submissions
        $isStudent = $user->isStudent();
        if ($isStudent && $user->student) {
            // Load submission status for each assignment
            $meetings->each(function ($meeting) use ($user) {
                $meeting->assignments->each(function ($assignment) use ($user) {
                    $submission = $assignment->submissions()
                        ->where('student_id', $user->student->id)
                        ->first();
                    $assignment->student_submission = $submission;
                });
            });
        }

        return Inertia::render('Tenant/Courses/Show', [
            'course' => $teachingAssignment,
            'meetings' => $meetings,
            'is_teacher' => $user->isTeacher(),
            'is_student' => $isStudent,
        ]);
    }

    /**
     * Toggle meeting lock status or update meeting details.
     */
    public function updateMeeting(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_locked' => 'sometimes|boolean',
        ]);

        $meeting->update($validated);

        return back()->with('success', 'Pertemuan berhasil diperbarui.');
    }

    /**
     * Add material (file/link/text) to a meeting.
     */
    public function storeMaterial(Request $request, Meeting $meeting)
    {
        $this->authorize('manageMaterial', $meeting);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:file,link,text',
            'content' => 'nullable|string',
            'url' => 'nullable|url|max:500',
            'file' => 'nullable|file|max:20480', // 20MB max
        ]);

        $data = [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'sort_order' => $meeting->materials()->count(),
        ];

        if ($validated['type'] === 'text') {
            $data['content'] = $validated['content'] ?? '';
        } elseif ($validated['type'] === 'link') {
            $data['url'] = $validated['url'] ?? '';
        } elseif ($validated['type'] === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store("meetings/{$meeting->id}/materials", 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        $meeting->materials()->create($data);

        return back()->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Delete a material from a meeting.
     */
    public function destroyMaterial(MeetingMaterial $meetingMaterial)
    {
        $meeting = $meetingMaterial->meeting;
        $this->authorize('manageMaterial', $meeting);

        // Delete file from storage if exists
        if ($meetingMaterial->file_path) {
            Storage::disk('public')->delete($meetingMaterial->file_path);
        }

        $meetingMaterial->delete();

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
