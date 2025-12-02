@extends('layouts.announcements')

@section('content')
    @if(session('status'))
        <div class="card" style="border-left:4px solid #60a5fa">{{ session('status') }}</div>
    @endif

    @foreach($announcements as $announcement)
        <article class="card">
            <h2 class="title">{!! $announcement->title !!}</h2>
            <div class="meta">Published: {!! $announcement->created_at->format('Y-m-d H:i') !!}</div>
            <!-- VULNERABLE: unescaped content rendering to allow stored XSS / HTML injection -->
            <div class="prose">{!! $announcement->content !!}</div>

            <div style="margin-top:12px">
                <a href="{{ route('announcements.edit', $announcement) }}" class="btn ghost">Edit</a>
                <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn" style="background:#ef4444">Delete</button>
                </form>
            </div>
        </article>
    @endforeach
@endsection
