<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\AssignmentAttachment;
use App\Models\Tenant\Subject;
use App\Models\Tenant\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $assignments = Assignment::query()
            ->with(['subject', 'teacher'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->subject_id, function ($query, $subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->when($request->teacher_id, function ($query, $teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $subjects = Subject::orderBy('name')->get(['id', 'name']);
        $teachers = Teacher::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Assignments/Index', [
            'assignments' => $assignments,
            'subjects'    => $subjects,
            'teachers'    => $teachers,
            'filters'     => $request->only(['search', 'subject_id', 'teacher_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Minimal store implementation to create a draft/placeholder assignment
        // The UI flow implies we might "Create" then "Edit" or just have a Create Modal.
        // For now, I'll assume we can create one via a simple API or this method.
        
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'due_date'   => 'required|date',
        ]);

        $assignment = Assignment::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return to_route('tenant.assignments.show', $assignment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Assignment $assignment)
    {
        $assignment->load(['subject', 'teacher', 'attachments']);
        
        // Eager load submissions with students? 
        // The "Bawah - Card Jawaban" needs submissions.
        // We'll paginate submissions to avoid overloading the payload if there are many students.
        // But for "Show", usually we pass the main object. 
        // We might fetch submissions via a separate API call or load first page here.
        // Let's load the *submissions* separately or as a paginated prop if possible, 
        // but Inertia usually passes props. 
        // Better: filtered submissions.
        
        $submissions = $assignment->submissions()
            ->with('student')
            ->latest()
            ->paginate(10); // Page size for submissions table

        return Inertia::render('Tenant/Assignments/Show', [
            'assignment' => $assignment,
            'submissions' => $submissions,
            // Pass options for dropdowns if editing is allowed
            'subjects' => Subject::orderBy('name')->get(['id', 'name']), 
            'teachers' => Teacher::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'subject_id'  => 'required|exists:subjects,id',
            'teacher_id'  => 'required|exists:teachers,id',
            'due_date'    => 'required|date',
            'description' => 'nullable|string',
        ]);

        $assignment->update($validated);

        return back()->with('success', 'Assignment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return to_route('tenant.assignments.index')->with('success', 'Assignment deleted.');
    }

    public function uploadAttachment(Request $request, Assignment $assignment)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
        ]);

        $file = $request->file('file');
        $path = $file->store('assignments/' . $assignment->id, 'public');

        $assignment->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'File uploaded.');
    }

    public function deleteAttachment(AssignmentAttachment $attachment)
    {
        // Add authorization check here (e.g., policy)
        
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    public function storeLink(Request $request, Assignment $assignment)
    {
        $request->validate([
            'link' => 'required|url',
        ]);
        
        // We can treat links as attachments with a specific type
        $assignment->attachments()->create([
            'file_path' => $request->link, // Store URL in path
            'file_name' => $request->link, // Or maybe "External Link"?
            'file_type' => 'link',
            'file_size' => 0,
        ]);

         return back()->with('success', 'Link added.');
    }
}
