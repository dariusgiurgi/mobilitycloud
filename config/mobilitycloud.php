<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MobilityCloud public contact channels
    |--------------------------------------------------------------------------
    |
    | These addresses are displayed in customer-facing pages and used as the
    | default reply-to targets for operational emails. Keep transactional
    | sending addresses separate from human inboxes.
    |
    */

    'emails' => [
        'contact' => env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@mobilitycloud.eu'),
        'support' => env('MOBILITYCLOUD_SUPPORT_EMAIL', 'support@mobilitycloud.eu'),
        'billing' => env('MOBILITYCLOUD_BILLING_EMAIL', 'billing@mobilitycloud.eu'),
        'security' => env('MOBILITYCLOUD_SECURITY_EMAIL', 'security@mobilitycloud.eu'),
        'notifications' => env('MOBILITYCLOUD_NOTIFICATION_EMAIL', 'notifications@mobilitycloud.eu'),
    ],

];
