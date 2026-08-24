<?php

namespace App\Filament\Auth\Pages;

use App\Services\TurnstileVerificationService;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicRegister extends Register
{
    private const SESSION_STARTED_AT = 'mobilitycloud.public_registration_started_at';

    public function mount(): void
    {
        session()->put(self::SESSION_STARTED_AT, now()->timestamp);

        parent::mount();
    }

    public function register(): ?RegistrationResponse
    {
        // Validate the normal registration fields before spending a Turnstile token
        // or a rate-limit slot. The parent method validates again before creating.
        $this->form->getState();

        $this->ensureHoneypotIsEmpty();
        $this->ensureMinimumCompletionTime();
        $this->ensureTurnstilePasses();
        $this->ensureIpCanRegister();

        return parent::register();
    }

    protected function getHoneypotFormComponent(): Component
    {
        return TextInput::make('website')
            ->label('Website')
            ->maxLength(255)
            ->dehydrated(false)
            ->extraAttributes([
                'autocomplete' => 'off',
                'tabindex' => '-1',
            ])
            ->extraFieldWrapperAttributes([
                'aria-hidden' => 'true',
                'style' => 'position:absolute !important;left:-10000px !important;width:1px !important;height:1px !important;overflow:hidden !important;',
            ]);
    }

    protected function getTurnstileTokenFormComponent(): Component
    {
        return Hidden::make('turnstile_token')
            ->dehydrated(false);
    }

    protected function getTurnstileFormComponent(): Component
    {
        return ViewField::make('turnstile_widget')
            ->view('filament.forms.components.turnstile')
            ->dehydrated(false);
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getHoneypotFormComponent(),
                $this->getTurnstileTokenFormComponent(),
                $this->getTurnstileFormComponent(),
            ]);
    }

    private function ensureHoneypotIsEmpty(): void
    {
        if (blank($this->data['website'] ?? null)) {
            return;
        }

        $this->reject('data.website', 'We could not complete this registration.');
    }

    private function ensureMinimumCompletionTime(): void
    {
        $startedAt = (int) session()->get(self::SESSION_STARTED_AT, 0);
        $minimumSeconds = max(1, (int) config('mobilitycloud.registration.minimum_completion_seconds', 4));

        if ($startedAt > 0 && now()->timestamp - $startedAt >= $minimumSeconds) {
            return;
        }

        $this->reject('data.email', 'Please take a moment to complete the form before submitting it.');
    }

    private function ensureTurnstilePasses(): void
    {
        if (app(TurnstileVerificationService::class)->passes(
            token: (string) ($this->data['turnstile_token'] ?? ''),
            remoteIp: request()->ip(),
        )) {
            return;
        }

        $this->reject('data.turnstile_token', 'Please complete the security check and try again.');
    }

    private function ensureIpCanRegister(): void
    {
        $ip = request()->ip() ?: 'unknown';
        $limits = [
            ['key' => 'hour', 'attempts' => (int) config('mobilitycloud.registration.max_per_ip_per_hour', 3), 'decay' => 3600],
            ['key' => 'day', 'attempts' => (int) config('mobilitycloud.registration.max_per_ip_per_day', 8), 'decay' => 86400],
        ];

        foreach ($limits as $limit) {
            $key = 'mobilitycloud:register:'.$limit['key'].':'.hash('sha256', $ip);

            if (RateLimiter::tooManyAttempts($key, max(1, $limit['attempts']))) {
                Notification::make()
                    ->title('Registration temporarily unavailable')
                    ->body('Please try again later or contact support if you need help.')
                    ->danger()
                    ->send();

                $this->reject('data.email', 'Too many registration attempts. Please try again later.');
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::hit(
                'mobilitycloud:register:'.$limit['key'].':'.hash('sha256', $ip),
                $limit['decay'],
            );
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
