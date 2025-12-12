<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcements — Vulnerable Demo</title>
    <style>
        :root{--bg:#f6f7fb;--card:#ffffff;--accent:#1f6feb;--muted:#6b7280}
        html,body{height:100%;margin:0;font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial}
        body{background:linear-gradient(180deg,#f8fafc 0%,var(--bg) 100%);color:#0f172a;display:flex;align-items:flex-start;justify-content:center;padding:32px}
        .ann-container{width:100%;max-width:880px}
        header.ann-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
        header.ann-header h1{font-size:20px;margin:0}
        .btn{display:inline-block;padding:8px 12px;background:var(--accent);color:#fff;border-radius:6px;text-decoration:none}
        .btn.ghost{background:transparent;color:var(--accent);border:1px solid rgba(31,111,235,.12)}
        .card{background:var(--card);border-radius:10px;padding:18px;margin-bottom:14px;box-shadow:0 6px 18px rgba(15,23,42,0.06);border:1px solid rgba(15,23,42,0.03)}
        h2.title{margin:0 0 8px 0;font-size:16px}
        .meta{font-size:13px;color:var(--muted);margin-bottom:10px}
        form .field{margin-bottom:12px}
        form label{display:block;font-size:13px;margin-bottom:6px;color:#111827}
        form input[type="text"], form textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6edf3;background:#fff}
        .footer{margin-top:18px;font-size:13px;color:var(--muted)}
        .prose img{max-width:100%;height:auto}
    </style>
</head>
<body>
    <div class="ann-container">
        <header class="ann-header">
            <h1>Announcements (Lab Demo)</h1>
            <nav>
                <a class="btn ghost" href="{{ route('announcements.index') }}">Home</a>
                <a class="btn" href="{{ route('announcements.create') }}">New Announcement</a>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        <div class="footer">This announcements area uses a dedicated layout and styling for the vulnerable demo.</div>
    </div>
</body>
</html>
