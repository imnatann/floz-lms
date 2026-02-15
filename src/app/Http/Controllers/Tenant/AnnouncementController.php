<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $announcements = Announcement::with('author')
            ->where('is_published', true)
            ->where(function ($query) use ($user) {
                // Show 'all'
                $query->where('target_audience', 'all');
                
                // Show role-specific
                if ($user->isTeacher()) {
                    $query->orWhere('target_audience', 'teachers');
                } elseif ($user->isStudent()) {
                    $query->orWhere('target_audience', 'students');
                }
            })
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('is_pinned', 'desc') // Pinned first
            ->orderBy('created_at', 'desc')
            ->paginate(12) // Grid layout usually takes more space
            ->withQueryString();

        return inertia('Tenant/Announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Announcement $announcement)
    {
        // Add authorization check if needed (e.g. student shouldn't see teacher announcements)
        
        return inertia('Tenant/Announcements/Show', [
            'announcement' => $announcement->load('author'),
        ]);
    }

    public function create()
    {
        return inertia('Tenant/Announcements/Form');
    }

    public function edit(Announcement $announcement)
    {
        return inertia('Tenant/Announcements/Form', [
            'announcement' => $announcement,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string', // Rich text HTML
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|image|max:2048', // Allow file upload
            'cover_image_url' => 'nullable|string', // Fallback for direct URL
            'target_audience' => 'required|in:all,teachers,students',
            'type' => 'required|in:info,event,alert',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
        ]);

        // Handle Image Upload
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('announcements', 'public');
            $validated['cover_image_url'] = Storage::url($path);
        }

        // Auto-generate Excerpt if empty
        if (empty($validated['excerpt'])) {
            $validated['excerpt'] = Str::limit(strip_tags($validated['content']), 150);
        }

        $request->user()->announcements()->create($validated);

        return redirect()->route('tenant.announcements.index')
            ->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|image|max:2048',
            'cover_image_url' => 'nullable|string',
            'target_audience' => 'required|in:all,teachers,students',
            'type' => 'required|in:info,event,alert',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
        ]);

        // Handle Image Upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists and is local
            if ($announcement->cover_image_url && Str::startsWith($announcement->cover_image_url, '/storage/')) {
                 $oldPath = str_replace('/storage/', '', $announcement->cover_image_url);
                 Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('cover_image')->store('announcements', 'public');
            $validated['cover_image_url'] = Storage::url($path);
        }

        // Auto-generate Excerpt if empty
        if (empty($validated['excerpt'])) {
            $validated['excerpt'] = Str::limit(strip_tags($validated['content']), 150);
        }

        $announcement->update($validated);

        return redirect()->route('tenant.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement)
    {
        // Delete cover image if exists and is local
        if ($announcement->cover_image_url && Str::startsWith($announcement->cover_image_url, '/storage/')) {
             $path = str_replace('/storage/', '', $announcement->cover_image_url);
             Storage::disk('public')->delete($path);
        }

        $announcement->delete();
        
        // Redirect to index if we are on the show page, or back if we are on index
        return redirect()->route('tenant.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
