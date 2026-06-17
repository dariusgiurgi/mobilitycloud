<x-filament-panels::page>

    @php
        $projects = $this->getProjects();
        $statusColors = [
            'draft'     => ['label' => 'Draft',     'bg' => 'rgba(100,116,139,.15)', 'text' => 'rgb(100,116,139)'],
            'submitted' => ['label' => 'Submitted', 'bg' => 'rgba(245,158,11,.15)',  'text' => 'rgb(217,119,6)'],
            'approved'  => ['label' => 'Approved',  'bg' => 'rgba(59,130,246,.15)',  'text' => 'rgb(37,99,235)'],
            'activated' => ['label' => 'Activated', 'bg' => 'rgba(34,197,94,.15)',   'text' => 'rgb(22,163,74)'],
            'completed' => ['label' => 'Completed', 'bg' => 'rgba(124,58,237,.15)',  'text' => 'rgb(124,58,237)'],
            'archived'  => ['label' => 'Archived',  'bg' => 'rgba(100,116,139,.15)', 'text' => 'rgb(100,116,139)'],
        ];
    @endphp

    @if($projects->isEmpty())
        <div class="fi-section rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">No projects yet. Create your first one.</p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
            @foreach($projects as $project)
                @php
                    $s = $statusColors[$project->status] ?? $statusColors['draft'];
                    $spent = $project->spent;
                    $budget = (float) $project->total_budget;
                    $progress = $project->progress;
                @endphp

                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="position:relative;overflow:hidden;transition:box-shadow .2s;"
                     onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
                     onmouseout="this.style.boxShadow=''">

                    {{-- Settings gear (sus-dreapta) --}}
                    <a href="{{ $this->getSettingsUrl($project) }}"
                       title="Project settings"
                       style="position:absolute;top:12px;right:12px;z-index:2;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;color:#9ca3af;"
                       class="hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </a>

                    {{-- Card body (click → pagina proiectului) --}}
                    <a href="{{ $this->getProjectUrl($project) }}"
                       style="display:block;padding:1.25rem;text-decoration:none;">

                        {{-- Status badge --}}
                        <span style="display:inline-block;font-size:11px;font-weight:600;padding:2px 10px;border-radius:999px;background:{{ $s['bg'] }};color:{{ $s['text'] }};margin-bottom:.75rem;">
                            {{ $s['label'] }}
                        </span>

                        {{-- Name --}}
                        <p class="text-gray-950 dark:text-white" style="font-size:15px;font-weight:600;line-height:1.4;margin:0 0 .25rem;padding-right:28px;">
                            {{ $project->name }}
                        </p>
                        @if($project->acronym)
                            <p class="text-gray-400" style="font-size:12px;font-family:monospace;margin:0 0 .75rem;">{{ $project->acronym }}</p>
                        @endif

                        {{-- Budget --}}
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin:.75rem 0 .4rem;">
                            <span class="text-gray-500 dark:text-gray-400">Budget</span>
                            <span class="text-gray-950 dark:text-white" style="font-weight:600;">€ {{ number_format($budget, 2) }}</span>
                        </div>

                        {{-- Progress bar --}}
                        <div style="height:6px;border-radius:999px;background:rgba(100,116,139,.15);overflow:hidden;">
                            <div style="height:6px;border-radius:999px;width:{{ $progress }}%;background:{{ $progress >= 100 ? '#ef4444' : '#6366f1' }};transition:width .3s;"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-top:.4rem;">
                            <span class="text-gray-400">Spent € {{ number_format($spent, 2) }}</span>
                            <span class="text-gray-400">{{ $progress }}%</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
