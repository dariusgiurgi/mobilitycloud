@php
    $siteKey = (string) config('mobilitycloud.registration.turnstile.site_key');
    $required = (bool) config('mobilitycloud.registration.turnstile.required');
@endphp

@if (filled($siteKey))
    <div
        wire:ignore
        x-data="{
            widgetId: null,
            render() {
                if (! window.turnstile || this.widgetId !== null) return;
                this.widgetId = window.turnstile.render(this.$refs.widget, {
                    sitekey: @js($siteKey),
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    callback: (token) => this.$wire.set('data.turnstile_token', token),
                    'expired-callback': () => this.$wire.set('data.turnstile_token', ''),
                    'error-callback': () => this.$wire.set('data.turnstile_token', ''),
                });
            },
            load() {
                if (window.turnstile) { this.render(); return; }
                const existing = document.querySelector('script[data-mc-turnstile]');
                if (existing) { existing.addEventListener('load', () => this.render(), { once: true }); return; }
                const script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                script.async = true;
                script.defer = true;
                script.dataset.mcTurnstile = 'true';
                script.addEventListener('load', () => this.render(), { once: true });
                document.head.appendChild(script);
            },
        }"
        x-init="load()"
    >
        <div x-ref="widget"></div>
    </div>
@elseif ($required)
    <div class="fi-fo-field-wrp-helper-text text-sm text-danger-600 dark:text-danger-400">
        Registration is temporarily unavailable. Please try again shortly.
    </div>
@endif
