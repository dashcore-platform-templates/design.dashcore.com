<?php

/*
|--------------------------------------------------------------------------
| Design kit
|--------------------------------------------------------------------------
|
| Where `php artisan design:apply <slug>` pulls a design from when it is
| given a slug rather than a URL or a local file. Point it at a local
| design.dashcore.com while developing the standard itself.
|
*/

return [
    'source' => env('DESIGN_SOURCE', 'https://design.dashcore.com'),
];
