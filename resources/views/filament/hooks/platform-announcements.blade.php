@if($announcements->isNotEmpty())
    <div style="display:grid;gap:.45rem;padding:.65rem 1rem 0;">
        @foreach($announcements as $announcement)
            <div style="border-radius:.75rem;padding:.7rem .85rem;background:{{ $announcement->severityColor() }};color:white;box-shadow:0 8px 24px rgba(15,23,42,.12);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                    <div>
                        <strong style="display:block;font-size:.82rem;">{{ $announcement->title }}</strong>
                        <span style="display:block;font-size:.74rem;line-height:1.45;margin-top:.12rem;opacity:.96;">{{ $announcement->message }}</span>
                    </div>
                    <span style="font-size:.62rem;text-transform:uppercase;letter-spacing:.06em;opacity:.82;">{{ \App\Models\PlatformAnnouncement::SEVERITIES[$announcement->severity] ?? $announcement->severity }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif
