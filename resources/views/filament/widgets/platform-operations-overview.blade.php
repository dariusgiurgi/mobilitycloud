<x-filament-widgets::widget>
    <x-filament::section heading="Platform command center" description="Recent activity, operational shortcuts and subscription attention points.">
        <style>
            .mc-platform-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}.mc-platform-card{border:1px solid rgba(100,116,139,.16);border-radius:.8rem;padding:1rem;background:rgba(255,255,255,.02)}.mc-platform-row{display:flex;justify-content:space-between;gap:.8rem;padding:.65rem 0;border-top:1px solid rgba(100,116,139,.12)}.mc-platform-row:first-child{border-top:0}.mc-platform-title{font-size:.8rem;font-weight:700}.mc-platform-muted{color:#64748b;font-size:.7rem;line-height:1.45}.mc-platform-link{display:block;text-decoration:none;padding:.65rem;border-radius:.65rem;border:1px solid rgba(99,102,241,.15);margin-top:.5rem}.mc-platform-link:hover{background:rgba(99,102,241,.07)}@media(max-width:1050px){.mc-platform-grid{grid-template-columns:1fr}}
        </style>
        <div class="mc-platform-grid">
            <div class="mc-platform-card">
                <p class="mc-platform-title text-gray-950 dark:text-white">Quick actions</p>
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" class="mc-platform-link">
                        <span class="mc-platform-title text-gray-950 dark:text-white">{{ $link['label'] }}</span>
                        <span class="mc-platform-muted" style="display:block;margin-top:.12rem;">{{ $link['detail'] }}</span>
                    </a>
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
                <p class="mc-platform-title text-gray-950 dark:text-white">Subscriptions ending soon</p>
                @forelse($endingSoon as $workspace)
                    <div class="mc-platform-row">
                        <div>
                            <div class="mc-platform-title text-gray-950 dark:text-white">{{ $workspace->name }}</div>
                            <div class="mc-platform-muted">{{ str($workspace->plan)->replace('_', ' ')->title() }} · {{ $workspace->subscriptionStatusLabel() }}</div>
                        </div>
                        <div class="mc-platform-muted">{{ $workspace->subscription_ends_at?->format('d M') }}</div>
                    </div>
                @empty
                    <p class="mc-platform-muted" style="margin-top:.7rem;">No subscription deadlines in the next 30 days.</p>
                @endforelse
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
