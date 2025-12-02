@extends('layouts.announcements')

@section('content')
    <div class="card">
        <h2 class="title">Edit Announcement (VULNERABLE)</h2>

        <form action="{{ route('announcements.update', $announcement) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="field">
                <label>Title</label>
                <input type="text" name="title" value="{{ $announcement->title }}" />
            </div>

            <div class="field">
                <label>Content (HTML allowed)</label>
                <textarea name="content" rows="8">{{ $announcement->content }}</textarea>
            </div>

            <div>
                <button class="btn">Save (raw)</button>
                <a class="btn ghost" href="{{ route('announcements.index') }}">Back</a>
            </div>
        </form>
    </div>
@endsection
