<x-filament-widgets::widget>
    <x-filament::section heading="Platform command center" description="Recent activity, operational shortcuts and account access attention points.">
        <style>
            .mc-platform-grid{display:grid;grid-template-columns:1.1fr 1fr 1fr;gap:1rem}.mc-platform-card{border:1px solid rgba(100,116,139,.16);border-radius:.8rem;padding:1rem;background:rgba(255,255,255,.02)}.mc-platform-row{display:flex;justify-content:space-between;gap:.8rem;padding:.65rem 0;border-top:1px solid rgba(100,116,139,.12)}.mc-platform-row:first-child{border-top:0}.mc-platform-title{font-size:.8rem;font-weight:700}.mc-platform-muted{color:#64748b;font-size:.7rem;line-height:1.45}.mc-platform-link-group{margin-top:.8rem;padding-top:.8rem;border-top:1px solid rgba(100,116,139,.12)}.mc-platform-link-group:first-of-type{border-top:0;padding-top:.35rem}.mc-platform-group-label{font-size:.64rem;font-weight:850;text-transform:uppercase;letter-spacing:.06em;color:#64748b}.mc-platform-link{display:block;text-decoration:none;padding:.65rem;border-radius:.65rem;border:1px solid rgba(99,102,241,.15);margin-top:.5rem}.mc-platform-link:hover{background:rgba(99,102,241,.07)}.mc-attention-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem;margin-bottom:1rem}.mc-attention{display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;text-decoration:none;border:1px solid rgba(245,158,11,.2);background:rgba(245,158,11,.07);border-radius:.8rem;padding:.85rem}.mc-attention:hover{background:rgba(245,158,11,.11)}.mc-attention-danger{border-color:rgba(239,68,68,.22);background:rgba(239,68,68,.07)}.mc-attention-danger:hover{background:rgba(239,68,68,.11)}.mc-attention-gray{border-color:rgba(148,163,184,.22);background:rgba(148,163,184,.07)}.mc-attention-count{font-size:1.25rem;font-weight:850;line-height:1;color:#111827}.dark .mc-attention-count{color:#fff}.mc-empty-attention{border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.07);border-radius:.8rem;padding:.85rem;color:#047857;font-size:.78rem;line-height:1.5;margin-bottom:1rem}@media(max-width:1050px){.mc-platform-grid,.mc-attention-grid{grid-template-columns:1fr}}
        </style>
        <div>
            <p class="mc-platform-title text-gray-950 dark:text-white" style="margin-bottom:.55rem;">Needs attention</p>
            @if(count($attentionItems))
                <div class="mc-attention-grid">
                    @foreach($attentionItems as $item)
                        <a href="{{ $item['url'] }}" class="mc-attention mc-attention-{{ $item['level'] }}">
                            <div>
                                <div class="mc-platform-title text-gray-950 dark:text-white">{{ $item['label'] }}</div>
                                <div class="mc-platform-muted" style="margin-top:.15rem;">{{ $item['detail'] }}</div>
                            </div>
                            <div class="mc-attention-count">{{ $item['count'] }}</div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mc-empty-attention">Everything looks calm: no suspended accounts, urgent access issues, pending moderation reports or failed jobs detected.</div>
            @endif
        </div>
        <div class="mc-platform-grid">
            <div class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">Quick actions</p>
                @foreach($linkGroups as $group)
                    <div class="mc-platform-link-group">
                        <div class="mc-platform-group-label">{{ $group['label'] }}</div>
                        @foreach($group['links'] as $link)
                            <a href="{{ $link['url'] }}" class="mc-platform-link">
                                <span class="mc-platform-title text-gray-950 dark:text-white">{{ $link['label'] }}</span>
                                <span class="mc-platform-muted" style="display:block;margin-top:.12rem;">{{ $link['detail'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">New accounts</p>
                @forelse($recentUsers as $user)
                    <div class="mc-platform-row">
                        <div>
                            <div class="mc-platform-title text-gray-950 dark:text-white">{{ $user->name }}</div>
                            <div class="mc-platform-muted">{{ $user->email }}</div>
                        </div>
                        <div class="mc-platform-muted">{{ $user->created_at->format('d M') }}</div>
                    </div>
                @empty
                    <p class="mc-platform-muted" style="margin-top:.7rem;">No users yet.</p>
                @endforelse
            </div>
            <div class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">Access attention</p>
                @forelse($endingSoon as $account)
                    <div class="mc-platform-row">
                        <div>
                            <div class="mc-platform-title text-gray-950 dark:text-white">{{ $account->name }}</div>
                            <div class="mc-platform-muted">{{ str($account->plan)->replace('_', ' ')->title() }} · {{ str($account->subscription_status)->replace('_', ' ')->title() }}</div>
                        </div>
                        <div class="mc-platform-muted">{{ ($account->subscription_ends_at ?: $account->trial_ends_at)?->format('d M') ?? 'Now' }}</div>
                    </div>
                @empty
                    <p class="mc-platform-muted" style="margin-top:.7rem;">No access deadlines or blocked accounts need attention.</p>
                @endforelse
            </div>
            <div class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">Admin alerts</p>
                @foreach($alerts as $alert)
                    <div class="mc-platform-row">
                        <div>
                            <div class="mc-platform-title text-gray-950 dark:text-white">{{ $alert['label'] }}</div>
                            <div class="mc-platform-muted">{{ $alert['detail'] }}</div>
                        </div>
                        <div class="mc-platform-title text-gray-950 dark:text-white">{{ $alert['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($recentAudit->isNotEmpty())
            <div style="margin-top:1rem;" class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">Recent admin actions</p>
                @foreach($recentAudit as $log)
                    <div class="mc-platform-row">
                        <div>
                            <div class="mc-platform-title text-gray-950 dark:text-white">{{ $log->description }}</div>
                            <div class="mc-platform-muted">{{ $log->actor?->email ?? 'System' }} · {{ $log->action }}</div>
                        </div>
                        <div class="mc-platform-muted">{{ $log->created_at->diffForHumans() }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
