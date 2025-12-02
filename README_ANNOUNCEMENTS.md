# Announcements Module (Vulnerable Demo)

This module is intentionally vulnerable and provided for use in a controlled lab / CTF / security training environment only. Do NOT deploy this code to public or production systems.

Files added:

- `database/migrations/2025_12_01_000010_create_announcements_table.php` — migration for `announcements` table.
- `app/Models/Announcement.php` — Announcement model.
- `app/Http/Controllers/AnnouncementController.php` — Vulnerable controller (no input validation/sanitization in `store`/`update`).
- `resources/views/announcements/*` — views for listing/creating/editing announcements.
- `resources/views/welcome.blade.php` — updated to render announcements using unescaped `{!! $announcement->content !!}`.
- `routes/web.php` — added guest-accessible announcement routes and set root to `welcome`.

Quick setup

1. Run migrations:

```powershell
cd C:\laragon\www\DigitalForensik
php artisan migrate
```

2. Visit the homepage (`/`) in a browser. You will see the announcements section shown on the welcome page.

3. Create announcements via `/announcements/create`.

Testing (examples for lab only)

- Defacement: paste HTML such as `<h1 style="color:red">DEFACED</h1>` into the content field.
- Button Hijacking: paste `<a href="http://evil.example/" class="btn btn-primary">Login Here</a>` and observe a fake button/link.
- Stored XSS: paste `<script>alert('Hacked')</script>` and then open the homepage to see the alert execute.

Safety / operational guidance

- This code is intentionally insecure. Run it only on an isolated machine or container with no access to production networks or sensitive systems.
- Do not point live users, search engines, or automated crawlers at this site while the vulnerable feature is enabled.
- If you want to keep this capability for lab usage but reduce accidental exposure, consider enabling it only on a development branch or by toggling a config flag.

If you want, I can:

- Move the example payloads into a sample CSV for students to import.
- Add a config toggle (e.g., `app.vulnerable_announcements`) to enable/disable the vulnerability for safer lab management.
