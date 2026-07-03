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
        'contact' => env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@xeotype.com'),
        'support' => env('MOBILITYCLOUD_SUPPORT_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@xeotype.com')),
        'billing' => env('MOBILITYCLOUD_BILLING_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@xeotype.com')),
        'owner' => env('MOBILITYCLOUD_OWNER_EMAIL', env('MOBILITYCLOUD_CONTACT_EMAIL', 'contact@xeotype.com')),
    ],

];
