<x-filament-panels::page>
    <x-ui-polish />

    @php
        $overview = $this->overview();
        $checks = $this->checks();
        $failedJobs = $this->failedJobs();
    @endphp

    <style>
        .mc-health{display:grid;gap:1rem}.mc-health-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.8rem}.mc-health-stat{border:1px solid rgba(148,163,184,.18);border-radius:1rem;padding:.95rem;background:rgba(255,255,255,.02);display:grid;gap:.32rem}.mc-health-stat-value{font-size:1.5rem;font-weight:900;line-height:1;color:#111827}.dark .mc-health-stat-value{color:#fff}.mc-health-stat-label{font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.035em}.mc-health-stat-note{font-size:.69rem;line-height:1.4;color:#64748b}.mc-health-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-health-card{border:1px solid rgba(148,163,184,.18);border-radius:1rem;padding:1rem;background:rgba(255,255,255,.02);display:grid;gap:.55rem}.mc-health-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.mc-health-label{font-weight:800;color:#111827}.dark .mc-health-label{color:#fff}.mc-health-detail{font-size:.76rem;color:#64748b;line-height:1.45;overflow-wrap:anywhere}.mc-health-badge{border-radius:999px;padding:.2rem .58rem;font-size:.66rem;font-weight:850;white-space:nowrap}.mc-health-ok{background:rgba(16,185,129,.1);color:#047857}.mc-health-warn{background:rgba(245,158,11,.13);color:#b45309}.mc-health-bad{background:rgba(239,68,68,.12);color:#dc2626}.mc-health-note{border:1px solid rgba(14,165,233,.15);background:rgba(14,165,233,.08);border-radius:1rem;padding:1rem;color:#0369a1;font-size:.8rem;line-height:1.55}.mc-health-failed{display:grid;gap:.65rem}.mc-health-job{border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.035);border-radius:.85rem;padding:.85rem;display:grid;gap:.42rem}.mc-health-job-top{display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start}.mc-health-job-name{font-weight:850;font-size:.79rem;color:#111827;overflow-wrap:anywhere}.dark .mc-health-job-name{color:#fff}.mc-health-job-meta{font-size:.69rem;color:#64748b;line-height:1.4}.mc-health-job-error{font:500 .71rem/1.45 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;color:#b91c1c;overflow-wrap:anywhere}.mc-health-job-id{font-size:.65rem;color:#94a3b8}.mc-health-help{display:grid;gap:.4rem;font-size:.74rem;line-height:1.5;color:#64748b}@media(max-width:1200px){.mc-health-summary{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:900px){.mc-health-grid{grid-template-columns:1fr}}@media(max-width:650px){.mc-health-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
    </style>

    <div class="mc-health">
        <div class="mc-health-summary">
            <div class="mc-health-stat">
                <div class="mc-health-stat-label">Checks passed</div>
                <div class="mc-health-stat-value">{{ $overview['ok'] }}/{{ $overview['total'] }}</div>
                <div class="mc-health-stat-note">Checked {{ $overview['checked_at'] }}</div>
            </div>
            <div class="mc-health-stat">
                <div class="mc-health-stat-label">Needs review</div>
                <div class="mc-health-stat-value">{{ $overview['warn'] + $overview['bad'] }}</div>
                <div class="mc-health-stat-note">{{ $overview['bad'] }} issue(s) · {{ $overview['warn'] }} warning(s)</div>
            </div>
            <div class="mc-health-stat">
                <div class="mc-health-stat-label">Pending queue jobs</div>
                <div class="mc-health-stat-value">{{ $overview['queued_jobs'] }}</div>
                <div class="mc-health-stat-note">Jobs waiting for the worker</div>
            </div>
            <div class="mc-health-stat">
                <div class="mc-health-stat-label">Failed jobs</div>
                <div class="mc-health-stat-value">{{ $overview['failed_jobs'] }}</div>
                <div class="mc-health-stat-note">Use retry only after resolving the cause</div>
            </div>
            <div class="mc-health-stat">
                <div class="mc-health-stat-label">Disk usage</div>
                <div class="mc-health-stat-value">{{ $overview['storage']['used_percent'] ?? '—' }}</div>
                <div class="mc-health-stat-note">
                    @if($overview['storage'])
                        {{ $overview['storage']['free'] }} free of {{ $overview['storage']['total'] }}
                    @else
                        Disk metrics unavailable
                    @endif
                </div>
            </div>
        </div>

        <div class="mc-health-note">
            Review warnings before retrying a failed job. “Restart queue worker” and “Rebuild config cache” are safe operational actions; retrying a job can send an email or repeat another external operation, so it always asks for confirmation.
        </div>

        <div class="mc-health-grid">
            @foreach($checks as $check)
                <x-filament::section>
                    <div class="mc-health-card">
                        <div class="mc-health-top">
                            <div>
                                <div class="mc-health-label">{{ $check['label'] }}</div>
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.16rem;">{{ $check['status'] }}</div>
                            </div>
                            <span class="mc-health-badge mc-health-{{ $check['level'] }}">
                                {{ $check['level'] === 'ok' ? 'OK' : ($check['level'] === 'warn' ? 'Review' : 'Issue') }}
                            </span>
                        </div>
                        <div class="mc-health-detail">{{ $check['detail'] }}</div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section
            heading="Failed job details"
            description="The latest eight failures are shown with a safe error summary. Select one from “Retry failed job” above only after the cause is resolved."
        >
            <div class="mc-health-failed">
                @forelse($failedJobs as $job)
                    <div class="mc-health-job">
                        <div class="mc-health-job-top">
                            <div>
                                <div class="mc-health-job-name">{{ $job['name'] }}</div>
                                <div class="mc-health-job-meta">{{ $job['queue'] }} · failed {{ $job['failed_at'] }}</div>
                            </div>
                            <span class="mc-health-badge mc-health-bad">Failed</span>
                        </div>
                        <div class="mc-health-job-error">{{ $job['error'] }}</div>
                        <div class="mc-health-job-id">{{ $job['uuid'] }}</div>
                    </div>
                @empty
                    <div class="mc-health-help">No failed jobs are recorded. When a background task fails, its job class, queue, timestamp and safe error summary will appear here.</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
