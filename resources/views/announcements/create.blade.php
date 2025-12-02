@extends('layouts.announcements')

@section('content')
    <div class="card">
        <h2 class="title">Create Announcement (VULNERABLE)</h2>

        <form action="{{ route('announcements.store') }}" method="POST">
            @csrf

            <div class="field">
                <label>Title</label>
                <input type="text" name="title" />
            </div>

            <div class="field">
                <label>Content (HTML allowed)</label>
                <textarea name="content" rows="8"></textarea>
            </div>

            <div>
                <button class="btn">Publish (raw)</button>
                <a class="btn ghost" href="{{ route('announcements.index') }}">Back</a>
            </div>
        </form>
    </div>
@endsection
