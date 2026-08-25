<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MobilityCloud public contact channels
    |--------------------------------------------------------------------------
    |
    | These addresses are displayed in customer-facing pages and used as the
    | default reply-to targets for operational emails.
    |
    */

    'emails' => [
        'contact' => env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu'),
        'support' => env('MOBILITYCLOUD_SUPPORT_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu')),
        'billing' => env('MOBILITYCLOUD_BILLING_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu')),
        'owner' => env('MOBILITYCLOUD_OWNER_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational launch settings
    |--------------------------------------------------------------------------
    */

    'backups' => [
        'path' => env('MOBILITYCLOUD_BACKUP_PATH', '/var/backups/mobilitycloud'),
        'status_path' => env('MOBILITYCLOUD_BACKUP_STATUS_PATH', storage_path('app/private/backup-health.json')),
        'retention_days' => (int) env('MOBILITYCLOUD_BACKUP_RETENTION_DAYS', 14),
        'max_age_hours' => (int) env('MOBILITYCLOUD_BACKUP_MAX_AGE_HOURS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public analytics
    |--------------------------------------------------------------------------
    |
    | Google Analytics is loaded only on public pages and only after the user
    | grants Analytics consent through the cookie banner. Keep this empty until
    | the production GA4 Measurement ID is available.
    |
    */

    'analytics' => [
        'google_measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public registration protection
    |--------------------------------------------------------------------------
    |
    | Public registration is deliberately fail-closed in production until a
    | Turnstile site key and secret are configured. This prevents the signup
    | form from being used to generate spam accounts or verification emails.
    |
    */

    'registration' => [
        'minimum_completion_seconds' => (int) env('MOBILITYCLOUD_REGISTRATION_MIN_SECONDS', 4),
        'max_per_ip_per_hour' => (int) env('MOBILITYCLOUD_REGISTRATION_MAX_PER_IP_HOUR', 3),
        'max_per_ip_per_day' => (int) env('MOBILITYCLOUD_REGISTRATION_MAX_PER_IP_DAY', 8),
        'unverified_retention_hours' => (int) env('MOBILITYCLOUD_UNVERIFIED_RETENTION_HOURS', 48),
        'turnstile' => [
            'required' => (bool) env('TURNSTILE_REQUIRED', env('APP_ENV') === 'production'),
            'site_key' => env('TURNSTILE_SITE_KEY'),
            'secret_key' => env('TURNSTILE_SECRET_KEY'),
            'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME', 'mobilitycloud.eu'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared project links
    |--------------------------------------------------------------------------
    |
    | Participant and anonymous feedback forms are intentionally frictionless:
    | there is no CAPTCHA or expiry timer. This high submission ceiling is only
    | a last-resort flood safety valve and is keyed by both link and requester.
    |
    */

    'public_links' => [
        'max_submissions_per_minute' => (int) env('MOBILITYCLOUD_PUBLIC_LINK_MAX_PER_MINUTE', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary final project archives
    |--------------------------------------------------------------------------
    |
    | Final ZIP files are generated in the background and kept only long enough
    | for a secure handover. Project records and source files are not affected.
    |
    */

    'final_archives' => [
        'disk' => env('MOBILITYCLOUD_FINAL_ARCHIVE_DISK', 'local'),
        'retention_hours' => (int) env('MOBILITYCLOUD_FINAL_ARCHIVE_RETENTION_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal company details
    |--------------------------------------------------------------------------
    |
    | These values intentionally support empty defaults because the legal pages
    | are prepared before final company details are inserted.
    |
    */

    'company' => [
        'name' => env('MOBILITYCLOUD_COMPANY_NAME', 'Xeotype'),
        'legal_name' => env('MOBILITYCLOUD_COMPANY_LEGAL_NAME', 'XEOTYPE SRL'),
        'registration_number' => env('MOBILITYCLOUD_COMPANY_REGISTRATION_NUMBER', 'J24/1044/2023'),
        'vat_number' => env('MOBILITYCLOUD_COMPANY_VAT_NUMBER', 'RO48497754'),
        'address' => env('MOBILITYCLOUD_COMPANY_ADDRESS', 'Municipiul Sighetu Marmației, Strada Dragoș Vodă, Nr. 185, Județ Maramureș, Romania'),
        'country' => env('MOBILITYCLOUD_COMPANY_COUNTRY', 'Romania'),
        'email' => env('MOBILITYCLOUD_COMPANY_EMAIL', 'contact@xeotype.com'),
    ],

];
