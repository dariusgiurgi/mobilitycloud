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
        'retention_days' => (int) env('MOBILITYCLOUD_BACKUP_RETENTION_DAYS', 14),
        'max_age_hours' => (int) env('MOBILITYCLOUD_BACKUP_MAX_AGE_HOURS', 30),
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
        'name' => env('MOBILITYCLOUD_COMPANY_NAME'),
        'legal_name' => env('MOBILITYCLOUD_COMPANY_LEGAL_NAME'),
        'registration_number' => env('MOBILITYCLOUD_COMPANY_REGISTRATION_NUMBER'),
        'vat_number' => env('MOBILITYCLOUD_COMPANY_VAT_NUMBER'),
        'address' => env('MOBILITYCLOUD_COMPANY_ADDRESS'),
        'country' => env('MOBILITYCLOUD_COMPANY_COUNTRY', 'Romania'),
        'email' => env('MOBILITYCLOUD_COMPANY_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu')),
    ],

];
