<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Services\UssdEngineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UssdCallbackController extends Controller
{
    public function handle(Request $request, UssdEngineService $engine): Response
    {
        $start = microtime(true);
        $reply = $engine->handleCallback($request->all(), true);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        try {
            WebhookLog::create([
                'provider' => 'ussd_gateway',
                'endpoint' => '/'.$request->path(),
                'status_code' => 200,
                'latency_ms' => $latencyMs,
                'outcome' => str_starts_with($reply, 'END Error:') ? 'rejected' : 'ok',
                'payload' => mb_substr(json_encode([
                    'sessionId' => $request->input('sessionId'),
                    'serviceCode' => $request->input('serviceCode'),
                    'networkCode' => $request->input('networkCode'),
                ]) ?: '', 0, 450),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never interrupt USSD replies over telemetry writes.
        }

        return response($reply, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function events(Request $request, UssdEngineService $engine): Response
    {
        $start = microtime(true);

        Log::info('USSD event callback received', [
            'phoneNumber' => $request->input('phoneNumber'),
            'status' => $request->input('status'),
            'sessionId' => $request->input('sessionId'),
            'durationInMillis' => $request->input('durationInMillis'),
            'cost' => $request->input('cost'),
        ]);

        $engine->reconcileEventCost($request->all());
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        try {
            WebhookLog::create([
                'provider' => 'ussd_gateway',
                'endpoint' => '/'.$request->path(),
                'status_code' => 200,
                'latency_ms' => $latencyMs,
                'outcome' => (string) ($request->input('status') ?: 'reconciled'),
                'payload' => mb_substr(json_encode([
                    'sessionId' => $request->input('sessionId'),
                    'serviceCode' => $request->input('serviceCode'),
                    'phoneNumber' => $request->input('phoneNumber'),
                    'networkCode' => $request->input('networkCode'),
                    'durationInMillis' => $request->input('durationInMillis'),
                    'hopsCount' => $request->input('hopsCount'),
                    'cost' => $request->input('cost'),
                    'status' => $request->input('status'),
                ]) ?: '', 0, 450),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }

        return response('OK', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
