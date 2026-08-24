<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

class TurnstileVerificationService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function isConfigured(): bool
    {
        return filled(config('mobilitycloud.registration.turnstile.site_key'))
            && filled(config('mobilitycloud.registration.turnstile.secret_key'));
    }

    public function passes(string $token, ?string $remoteIp): bool
    {
        if (! config('mobilitycloud.registration.turnstile.required')) {
            return true;
        }

        if (! $this->isConfigured() || blank($token)) {
            return false;
        }

        try {
            $response = $this->http
                ->asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('mobilitycloud.registration.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            $hostname = (string) $response->json('hostname');
            $expectedHostname = (string) config('mobilitycloud.registration.turnstile.expected_hostname');

            return $response->successful()
                && $response->json('success') === true
                && ($expectedHostname === '' || hash_equals($expectedHostname, $hostname));
        } catch (Throwable) {
            return false;
        }
    }
}
