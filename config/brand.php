<?php

/*
|--------------------------------------------------------------------------
| Brand
|--------------------------------------------------------------------------
|
| The same file `design:apply` writes into a kit, declared by hand here
| because this app is the tool rather than a kit: it has no design.json to
| apply and never will. The components in the package read these, so without
| them x-ds::page-header would render this app under a kit's defaults.
|
| The tool's own second face is mono, set in resources/css/app.css, which is
| why `second_kind` says so: a mono second face is for labels and eyebrows,
| never for a title, and 03 is what decides that rather than this file.
|
*/

return [
    'name' => 'Dashcore Design',
    'motif' => 'accent-bar',
    'second_kind' => 'mono',
    'condensed' => false,
];
