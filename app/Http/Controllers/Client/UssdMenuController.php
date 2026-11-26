<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Models\UssdMenuVersion;
use App\Services\TextMenuParserService;
use App\Services\UssdEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class UssdMenuController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user();

        $menus = UssdMenu::query()
            ->withCount('items')
            ->where('client_id', $client->id)
            ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
            ->orderByDesc('id')
            ->get();

        $ussdLogs = UssdLog::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('client.ussd-menus', compact('client', 'menus', 'ussdLogs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'menu_name' => ['required', 'string', 'max:255'],
        ]);

        $client = $request->user();

        $menu = DB::transaction(function () use ($client, $data): UssdMenu {
            $menu = UssdMenu::create([
                'client_id' => $client->id,
                'menu_name' => $data['menu_name'],
                'is_active' => false,
                'is_archived' => false,
            ]);

            UssdMenuItem::create([
                'menu_id' => $menu->id,
                'parent_item_id' => null,
                'option_trigger' => 'ROOT',
                'response_type' => 'CON',
                'response_text' => "Welcome to {$client->name}\n1. Option One",
            ]);

            return $menu;
        });

        return redirect()->route('ussd.menus.show', $menu)
            ->with('status', 'USSD menu created with a starter ROOT screen.');
    }

    public function show(Request $request, UssdMenu $menu, TextMenuParserService $parser): View
    {
        $this->authorizeMenu($request, $menu);

        $menu->load('client');
        $items = $menu->items()->orderBy('id')->get();
        $tree = $this->buildTree($items);
        $menuTextOutline = $parser->toText($items);
        $versions = $menu->versions()->limit(10)->get();

        return view('client.ussd-builder', [
            'client' => $request->user(),
            'menu' => $menu,
            'items' => $items,
            'tree' => $tree,
            'menuTextOutline' => $menuTextOutline,
            'versions' => $versions,
        ]);
    }

    public function importText(Request $request, UssdMenu $menu, TextMenuParserService $parser): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);

        $data = $request->validate([
            'menu_text' => ['required', 'string', 'max:50000'],
            'mode' => ['required', 'in:replace,append'],
            'parent_item_id' => ['nullable', 'integer', 'exists:ussd_menu_items,id'],
            'default_leaf_type' => ['required', 'in:API,END'],
        ]);

        $parentId = ! empty($data['parent_item_id']) ? (int) $data['parent_item_id'] : null;
        if ($data['mode'] === 'append' && $parentId !== null) {
            $parentBelongsToMenu = UssdMenuItem::query()
                ->where('id', $parentId)
                ->where('menu_id', $menu->id)
                ->exists();

            if (! $parentBelongsToMenu) {
                return back()->withErrors(['parent_item_id' => 'Selected parent step does not belong to this menu.']);
            }
        }

        if ($menu->items()->exists()) {
            $this->recordSnapshot($menu, $parser, 'Auto backup before text '.$data['mode'], $request->user()?->id);
        }

        $count = $parser->importIntoMenu(
            $menu,
            $data['menu_text'],
            $data['mode'],
            $parentId,
            $data['default_leaf_type']
        );

        if ($count === 0) {
            return back()->withErrors(['menu_text' => 'Could not parse any menu steps from the provided outline.']);
        }

        return back()->with('status', "Built {$count} menu step(s) from text outline (previous state saved in Version History).");
    }

    public function storeSnapshot(Request $request, UssdMenu $menu, TextMenuParserService $parser): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $version = $this->recordSnapshot(
            $menu,
            $parser,
            $data['label'] ?: 'Manual Snapshot',
            $request->user()?->id
        );

        return back()->with('status', "Saved menu snapshot v{$version->version_number}.");
    }

    public function restoreVersion(Request $request, UssdMenu $menu, UssdMenuVersion $version, TextMenuParserService $parser): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);
        if ((int) $version->menu_id !== (int) $menu->id) {
            abort(403, 'Snapshot does not belong to this menu.');
        }

        $snapshot = json_decode((string) $version->snapshot_json, true);
        if (! is_array($snapshot)) {
            return back()->withErrors(['version' => 'Saved snapshot data is corrupted or empty.']);
        }

        $this->recordSnapshot($menu, $parser, "Backup before restore to v{$version->version_number}", $request->user()?->id);

        DB::transaction(function () use ($menu, $snapshot): void {
            $menu->items()->delete();
            $idMap = [];
            foreach ($snapshot as $row) {
                $oldParent = $row['parent_item_id'] ?? null;
                $newItem = UssdMenuItem::create([
                    'menu_id' => $menu->id,
                    'parent_item_id' => $oldParent ? ($idMap[$oldParent] ?? null) : null,
                    'option_trigger' => (string) ($row['option_trigger'] ?? '1'),
                    'response_type' => (string) ($row['response_type'] ?? 'CON'),
                    'response_text' => (string) ($row['response_text'] ?? ''),
                ]);
                if (isset($row['id'])) {
                    $idMap[$row['id']] = $newItem->id;
                }
            }
        });

        return back()->with('status', "Restored menu to snapshot v{$version->version_number}.");
    }

    public function setActive(Request $request, UssdMenu $menu): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);

        DB::transaction(function () use ($menu): void {
            UssdMenu::query()
                ->where('client_id', $menu->client_id)
                ->update(['is_active' => false]);

            $menu->update(['is_active' => true, 'is_archived' => false]);
        });

        return back()->with('status', "Menu \"{$menu->menu_name}\" is now active.");
    }

    public function archive(Request $request, UssdMenu $menu): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);

        $menu->update([
            'is_active' => false,
            'is_archived' => true,
        ]);

        return redirect()->route('ussd.menus.index')
            ->with('status', "Menu \"{$menu->menu_name}\" archived.");
    }

    public function storeItem(Request $request, UssdMenu $menu): RedirectResponse
    {
        $this->authorizeMenu($request, $menu);

        $data = $request->validate([
            'parent_item_id' => ['nullable', 'integer', 'exists:ussd_menu_items,id'],
            'option_trigger' => ['required', 'string', 'max:255'],
            'response_type' => ['required', 'in:CON,END,API'],
            'response_text' => ['required', 'string'],
        ]);

        if (! empty($data['parent_item_id'])) {
            $parentBelongsToMenu = UssdMenuItem::query()
                ->where('id', $data['parent_item_id'])
                ->where('menu_id', $menu->id)
                ->exists();

            if (! $parentBelongsToMenu) {
                return back()->withErrors(['parent_item_id' => 'Selected parent step does not belong to this menu.']);
            }
        }

        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $data['parent_item_id'] ?: null,
            'option_trigger' => trim($data['option_trigger']),
            'response_type' => $data['response_type'],
            'response_text' => $data['response_text'],
        ]);

        return back()->with('status', 'Menu step added.');
    }

    public function updateItem(Request $request, UssdMenuItem $item): RedirectResponse
    {
        $item->load('menu');
        $this->authorizeMenu($request, $item->menu);

        $data = $request->validate([
            'parent_item_id' => ['nullable', 'integer', 'exists:ussd_menu_items,id'],
            'option_trigger' => ['required', 'string', 'max:255'],
            'response_type' => ['required', 'in:CON,END,API'],
            'response_text' => ['required', 'string'],
        ]);

        $parentId = ! empty($data['parent_item_id']) ? (int) $data['parent_item_id'] : null;
        if ($parentId === $item->id) {
            return back()->withErrors(['parent_item_id' => 'A step cannot be its own parent.']);
        }

        if ($parentId !== null) {
            $parentValid = UssdMenuItem::query()
                ->where('id', $parentId)
                ->where('menu_id', $item->menu_id)
                ->exists();

            if (! $parentValid) {
                return back()->withErrors(['parent_item_id' => 'Invalid parent step for this menu.']);
            }
        }

        $item->update([
            'parent_item_id' => $parentId,
            'option_trigger' => trim($data['option_trigger']),
            'response_type' => $data['response_type'],
            'response_text' => $data['response_text'],
        ]);

        return back()->with('status', 'Menu step updated.');
    }

    public function destroyItem(Request $request, UssdMenuItem $item): RedirectResponse
    {
        $item->load('menu');
        $this->authorizeMenu($request, $item->menu);

        $item->delete();

        return back()->with('status', 'Menu step removed.');
    }

    public function simulate(Request $request, UssdMenu $menu, UssdEngineService $engine): JsonResponse
    {
        $this->authorizeMenu($request, $menu);

        $menu->load('client');
        $rawText = (string) $request->input('text', '');
        $inputs = $engine->parseInputs($rawText);

        $reflection = new \ReflectionMethod($engine, 'resolveMenuResponse');
        $reflection->setAccessible(true);
        $reply = $reflection->invoke(
            $engine,
            $menu,
            $inputs,
            'SIM-'.time(),
            $menu->client?->ussd_code ?? '*000#',
            '+2348000000000',
            implode('*', $inputs)
        );

        return response()->json(['reply' => $reply]);
    }

    public function testApiNode(Request $request, UssdMenu $menu): JsonResponse
    {
        $this->authorizeMenu($request, $menu);
        $menu->load('client');

        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'text' => ['nullable', 'string', 'max:255'],
            'phoneNumber' => ['nullable', 'string', 'max:32'],
        ]);

        $scheme = strtolower((string) parse_url($data['url'], PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return response()->json(['ok' => false, 'message' => 'Only http:// and https:// endpoints are allowed.'], 422);
        }

        $start = microtime(true);
        try {
            $response = Http::asForm()->timeout(8)->post($data['url'], [
                'sessionId' => 'API-TEST-'.time(),
                'serviceCode' => $menu->client?->ussd_code ?? '*384*23#',
                'phoneNumber' => $data['phoneNumber'] ?: '+2348000000000',
                'text' => (string) ($data['text'] ?? ''),
            ]);
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            return response()->json([
                'ok' => $response->successful(),
                'status_code' => $response->status(),
                'latency_ms' => $latencyMs,
                'reply' => trim((string) $response->body()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'status_code' => 0,
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'reply' => 'Connection error: '.$e->getMessage(),
            ], 502);
        }
    }

    protected function authorizeMenu(Request $request, UssdMenu $menu): void
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) $menu->client_id !== (int) $user->id) {
            abort(403, 'You do not have permission to access this USSD menu.');
        }
    }

    /**
     * Save a recoverable JSON + text snapshot of the current menu items.
     */
    protected function recordSnapshot(UssdMenu $menu, TextMenuParserService $parser, string $label, ?int $actorId = null): UssdMenuVersion
    {
        $items = $menu->items()->orderBy('id')->get();
        $nextNumber = ((int) ($menu->versions()->max('version_number') ?? 0)) + 1;

        return UssdMenuVersion::create([
            'menu_id' => $menu->id,
            'version_number' => $nextNumber,
            'label' => $label,
            'outline_text' => $parser->toText($items),
            'snapshot_json' => json_encode($items->map(fn ($item) => [
                'id' => $item->id,
                'parent_item_id' => $item->parent_item_id,
                'option_trigger' => $item->option_trigger,
                'response_type' => $item->response_type,
                'response_text' => $item->response_text,
            ])->values()->all()),
            'created_by' => $actorId,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UssdMenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function buildTree($items, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($items as $item) {
            $itemParent = $item->parent_item_id ?: null;
            if ($itemParent === $parentId) {
                $branch[] = [
                    'item' => $item,
                    'children' => $this->buildTree($items, $item->id),
                ];
            }
        }

        return $branch;
    }
}
