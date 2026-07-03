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
        'support' => env('MOBILITYCLOUD_SUPPORT_EMAIL', 'support@mobilitycloud.eu'),
        'billing' => env('MOBILITYCLOUD_BILLING_EMAIL', 'billing@mobilitycloud.eu'),
        'owner' => env('MOBILITYCLOUD_OWNER_EMAIL', 'darius@mobilitycloud.eu'),
    ],

];
