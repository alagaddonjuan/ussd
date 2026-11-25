<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user();
        $apiKeys = ApiKey::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->get();

        return view('client.api-keys', compact('client', 'apiKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key_name' => ['required', 'string', 'max:255'],
            'whitelisted_ips' => ['nullable', 'string', 'max:1000'],
        ]);

        $plainKey = 'live_'.bin2hex(random_bytes(16));

        ApiKey::create([
            'client_id' => $request->user()->id,
            'key_name' => $data['key_name'],
            'api_key' => $plainKey,
            'whitelisted_ips' => $data['whitelisted_ips'] ?? null,
            'created_at' => now(),
        ]);

        return back()->with('status', "New API key created: {$plainKey}");
    }

    public function update(Request $request, ApiKey $apiKey): RedirectResponse
    {
        if ((int) $apiKey->client_id !== (int) $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'whitelisted_ips' => ['nullable', 'string', 'max:1000'],
        ]);

        $apiKey->update([
            'whitelisted_ips' => $data['whitelisted_ips'] ?? null,
        ]);

        return back()->with('status', "Updated IP whitelist for {$apiKey->key_name}.");
    }

    public function destroy(Request $request, ApiKey $apiKey): RedirectResponse
    {
        if ((int) $apiKey->client_id !== (int) $request->user()->id) {
            abort(403);
        }

        $apiKey->delete();

        return back()->with('status', 'API key revoked.');
    }
}
