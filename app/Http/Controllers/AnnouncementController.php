<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $announcements = Announcement::latest()->get();
        return view('announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('announcements.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * IMPORTANT: This method intentionally does NOT validate or sanitize input.
     * It stores raw input to simulate a vulnerable app (stored XSS / HTML injection lab).
     */
    public function store(Request $request)
    {
        // Intentionally accept raw input without validation or sanitization
        $announcement = Announcement::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);

        return redirect()->route('announcements.create')->with('status', 'Announcement created (raw).');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        return view('announcements.edit', compact('announcement'));
    }

    /**
     * Update the specified resource in storage.
     *
     * IMPORTANT: This method intentionally does NOT validate or sanitize input.
     */
    public function update(Request $request, Announcement $announcement)
    {
        // Intentionally accept raw input without validation or sanitization
        $announcement->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);

        return redirect()->route('announcements.index')->with('status', 'Announcement updated (raw).');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('announcements.index')->with('status', 'Announcement deleted.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        return view('announcements.show', compact('announcement'));
    }
}
