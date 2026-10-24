@php
    $account = $getRecord();
    $initials = collect(preg_split('/\s+/u', trim($account->name)))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<div class="mc-account-person">
    <span class="mc-account-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
    <div class="mc-account-identity">
        <div class="mc-account-name">{{ $account->name }}</div>
        <div class="mc-account-email">{{ $account->email }}</div>
        @if ($account->billing_name)
            <div class="mc-account-org">{{ $account->billing_name }}</div>
        @endif
    </div>
</div>
