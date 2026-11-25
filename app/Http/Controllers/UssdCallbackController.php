<?php

namespace App\Http\Controllers;

use App\Services\UssdEngineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UssdCallbackController extends Controller
{
    public function handle(Request $request, UssdEngineService $engine): Response
    {
        $reply = $engine->handleCallback($request->all(), true);

        return response($reply, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function events(Request $request): Response
    {
        Log::info('USSD event callback received', [
            'phoneNumber' => $request->input('phoneNumber'),
            'status' => $request->input('status'),
            'sessionId' => $request->input('sessionId'),
            'durationInMillis' => $request->input('durationInMillis'),
        ]);

        return response('OK', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
