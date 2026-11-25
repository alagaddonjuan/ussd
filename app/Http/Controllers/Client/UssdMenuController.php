<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Services\UssdEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function show(Request $request, UssdMenu $menu): View
    {
        $this->authorizeMenu($request, $menu);

        $menu->load('client');
        $items = $menu->items()->orderBy('id')->get();
        $tree = $this->buildTree($items);

        return view('client.ussd-builder', [
            'client' => $request->user(),
            'menu' => $menu,
            'items' => $items,
            'tree' => $tree,
        ]);
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

    protected function authorizeMenu(Request $request, UssdMenu $menu): void
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) $menu->client_id !== (int) $user->id) {
            abort(403, 'You do not have permission to access this USSD menu.');
        }
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
