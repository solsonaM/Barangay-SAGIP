<?php

// NOTE: This is an ADDITIONAL config file meant to be merged into your
// existing config/services.php (a fresh Laravel install already ships one
// for mail/aws/etc.). Add the 'ml_service' key below into that array.

return [

    'ml_service' => [
        'base_url' => env('ML_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => env('ML_SERVICE_TIMEOUT', 5),
    ],

];
