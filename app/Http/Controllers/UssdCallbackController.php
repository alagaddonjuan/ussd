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
        $statusCode = 200;
        $reply = 'END Service temporarily unavailable.';
        $outcome = 'ok';

        try {
            $reply = $engine->handleCallback($request->all(), true);
            $outcome = $engine->getLastCallbackOutcome() ?: (str_starts_with($reply, 'END Error:') ? 'rejected' : 'ok');
        } catch (\Throwable $e) {
            $statusCode = 500;
            $outcome = 'error:exception';
            $reply = 'END Service temporarily unavailable. Please try again.';
            Log::error('USSD callback unhandled exception', [
                'sessionId' => $request->input('sessionId'),
                'serviceCode' => $request->input('serviceCode'),
                'error' => $e->getMessage(),
            ]);
        }

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        try {
            WebhookLog::create([
                'provider' => 'ussd_gateway',
                'endpoint' => '/'.$request->path(),
                'status_code' => $statusCode,
                'latency_ms' => $latencyMs,
                'outcome' => mb_substr($outcome, 0, 50),
                'payload' => $this->encodeWebhookPayload([
                    'sessionId' => $request->input('sessionId'),
                    'serviceCode' => $request->input('serviceCode'),
                    'phoneNumber' => $request->input('phoneNumber'),
                    'networkCode' => $request->input('networkCode'),
                    'text' => mb_substr((string) $request->input('text', ''), 0, 500),
                    'error' => $engine->getLastGatewayError(),
                ]),
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
            'errorMessage' => $request->input('errorMessage'),
        ]);

        $statusCode = 200;
        $outcome = 'reconciled';
        $reason = null;

        try {
            $result = $engine->reconcileEventCost($request->all());
            $outcome = $result['outcome'] ?? 'reconciled';
            $reason = $result['reason'] ?? null;
        } catch (\Throwable $e) {
            $statusCode = 500;
            $outcome = 'error:exception';
            $reason = $e->getMessage();
            Log::error('USSD event callback exception', [
                'sessionId' => $request->input('sessionId'),
                'serviceCode' => $request->input('serviceCode'),
                'error' => $e->getMessage(),
            ]);
        }

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        try {
            WebhookLog::create([
                'provider' => 'ussd_gateway',
                'endpoint' => '/'.$request->path(),
                'status_code' => $statusCode,
                'latency_ms' => $latencyMs,
                'outcome' => mb_substr((string) ($outcome ?: 'reconciled'), 0, 50),
                'payload' => $this->encodeWebhookPayload([
                    'sessionId' => $request->input('sessionId'),
                    'serviceCode' => $request->input('serviceCode'),
                    'phoneNumber' => $request->input('phoneNumber'),
                    'networkCode' => $request->input('networkCode'),
                    'durationInMillis' => $request->input('durationInMillis'),
                    'hopsCount' => $request->input('hopsCount'),
                    'cost' => $request->input('cost'),
                    'status' => $request->input('status'),
                    'input' => mb_substr((string) ($request->input('input') ?? $request->input('text') ?? ''), 0, 500),
                    'errorMessage' => $request->input('errorMessage') ?: $reason,
                    'lastAppResponse' => mb_substr((string) $request->input('lastAppResponse', ''), 0, 500),
                    'date' => $request->input('date'),
                ]),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }

        return response('OK', $statusCode)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Encode webhook payload as valid JSON without slicing mid-string.
     *
     * @param  array<string, mixed>  $data
     */
    protected function encodeWebhookPayload(array $data): string
    {
        $filtered = array_filter($data, fn ($v) => $v !== null && $v !== '');
        $json = json_encode($filtered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json !== false ? $json : '{}';
    }
}
