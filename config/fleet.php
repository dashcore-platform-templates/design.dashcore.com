<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The install
    |--------------------------------------------------------------------------
    |
    | This app runs in Dashcore's own fleet and in every client's. Anything that
    | names the company or points at its hosts reads it from here, so a client
    | install never shows Dashcore. The defaults are Dashcore's own values.
    |
    | company.name   — the install's company name, in user-facing text.
    | company.domain — the company's own domain (email addresses, links for people).
    | domain         — where the fleet's apps answer (<subdomain>.<domain>).
    |
    */

    'company' => [
        'name' => env('FLEET_COMPANY_NAME', 'Dashcore'),
        'domain' => env('FLEET_COMPANY_DOMAIN', 'dashcore.com'),
    ],

    'domain' => env('FLEET_DOMAIN', 'dashcore.com'),

];
