<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'ussd',
        'ussd_callback',
        'api/v1/ussd',
        'api/v1/ussd/*',
        'billing/webhook',
    ];
}
