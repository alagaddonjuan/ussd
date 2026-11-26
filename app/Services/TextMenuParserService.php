<?php

namespace App\Services;

use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TextMenuParserService
{
    /**
     * Parse an indented text outline into hierarchical menu item payloads.
     *
     * Supports formats like:
     *   Welcome to Q4 Services
     *     1. Play Games
     *       1. Keno [API: Please Select Balls]
     *       2. Support [END: Call +234 904 593 6806]
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $outline, string $defaultMenuTitle = 'Welcome', string $defaultLeafType = 'API'): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $outline) ?: [];
        $parsedNodes = [];

        foreach ($lines as $rawLine) {
            if (trim($rawLine) === '') {
                continue;
            }

            $expanded = str_replace("\t", '  ', $rawLine);
            preg_match('/^(\s*)(.*)$/', $expanded, $indentMatch);
            $indent = strlen($indentMatch[1] ?? '');
            $content = trim($indentMatch[2] ?? '');

            // Ignore comment lines
            if ($content === '' || str_starts_with($content, '#') || str_starts_with($content, '//')) {
                continue;
            }

            $parsedNodes[] = [
                'indent' => $indent,
                'raw' => $content,
            ];
        }

        if ($parsedNodes === []) {
            return [];
        }

        // Determine if the first non-indented line is an explicit ROOT title or already a numbered trigger.
        $first = $parsedNodes[0];
        $rootTitle = $defaultMenuTitle;
        $explicitRootType = 'CON';
        $explicitRootBody = null;

        $isFirstNumbered = (bool) preg_match('/^(?:\d+|[A-Za-z*#]{1,4})[.)\]:]\s+/', $first['raw'])
            && ! preg_match('/^\[?ROOT\]?[\s:]/i', $first['raw']);

        if ($first['indent'] === 0 && ! $isFirstNumbered) {
            $meta = $this->extractTypeAndBody(
                preg_replace('/^\[?ROOT\]?\s*:?\s*/i', '', $first['raw']) ?? $first['raw']
            );
            $rootTitle = $meta['label'] !== '' ? $meta['label'] : $defaultMenuTitle;
            $explicitRootType = $meta['type'] ?? 'CON';
            $explicitRootBody = $meta['custom_body'];
            array_shift($parsedNodes);
        }

        // Convert remaining lines to normalized flat records with temporary IDs & parent links via indent stack.
        $flat = [];
        $stack = []; // each item: ['indent' => int, 'temp_id' => int]
        $nextTempId = 1;

        foreach ($parsedNodes as $node) {
            while ($stack !== [] && end($stack)['indent'] >= $node['indent']) {
                array_pop($stack);
            }

            $parentTempId = $stack !== [] ? end($stack)['temp_id'] : 0; // 0 = ROOT

            $meta = $this->extractTypeAndBody($node['raw']);
            $trigger = $meta['trigger'];
            $label = $meta['label'];

            $tempId = $nextTempId++;
            $flat[$tempId] = [
                'temp_id' => $tempId,
                'parent_temp_id' => $parentTempId,
                'option_trigger' => $trigger,
                'label' => $label,
                'explicit_type' => $meta['type'],
                'custom_body' => $meta['custom_body'],
                'children' => [],
            ];

            if ($parentTempId !== 0 && isset($flat[$parentTempId])) {
                $flat[$parentTempId]['children'][] = $tempId;
            }

            $stack[] = ['indent' => $node['indent'], 'temp_id' => $tempId];
        }

        $rootChildren = array_values(array_filter(
            $flat,
            fn (array $item): bool => $item['parent_temp_id'] === 0
        ));

        $result = [];

        $rootBody = $explicitRootBody ?? $this->buildScreenBody($rootTitle, $rootChildren, false);
        $result[0] = [
            'temp_id' => 0,
            'parent_temp_id' => null,
            'option_trigger' => 'ROOT',
            'response_type' => $explicitRootType,
            'response_text' => $rootBody,
        ];

        foreach ($flat as $tempId => $item) {
            $childItems = array_map(fn (int $cid) => $flat[$cid], $item['children']);
            $hasChildren = $childItems !== [];

            $type = $item['explicit_type'] ?? ($hasChildren ? 'CON' : strtoupper($defaultLeafType));

            if ($hasChildren && $type === 'CON') {
                $heading = $item['custom_body'] ?? "You selected {$item['label']}";
                $body = $this->buildScreenBody($heading, $childItems, false);
            } elseif ($item['custom_body'] !== null) {
                $body = $type === 'API' && ! str_contains($item['custom_body'], '|')
                    ? "{$item['label']}|{$item['custom_body']}"
                    : $item['custom_body'];
            } else {
                $body = $type === 'API'
                    ? "{$item['label']}|You selected {$item['label']}"
                    : ($type === 'CON' ? "{$item['label']}\n1. Confirm\n0. Back" : $item['label']);
            }

            $result[$tempId] = [
                'temp_id' => $tempId,
                'parent_temp_id' => $item['parent_temp_id'],
                'option_trigger' => $item['option_trigger'],
                'response_type' => $type,
                'response_text' => $body,
            ];
        }

        return $result;
    }

    /**
     * Persist parsed outline items into a UssdMenu inside a database transaction.
     */
    public function importIntoMenu(
        UssdMenu $menu,
        string $outline,
        string $mode = 'replace',
        ?int $targetParentId = null,
        string $defaultLeafType = 'API'
    ): int {
        $nodes = $this->parse($outline, $menu->menu_name, $defaultLeafType);
        if ($nodes === []) {
            return 0;
        }

        return DB::transaction(function () use ($menu, $nodes, $mode, $targetParentId): int {
            if ($mode === 'replace') {
                UssdMenuItem::query()->where('menu_id', $menu->id)->delete();
                $targetParentId = null;
            }

            $idMap = [];
            $created = 0;

            foreach ($nodes as $tempId => $node) {
                if ($mode === 'append' && $tempId === 0) {
                    // When appending under an existing parent (or top level), top-level children attach to targetParentId.
                    continue;
                }

                $parentId = null;
                if ($node['parent_temp_id'] !== null) {
                    if ($node['parent_temp_id'] === 0 && $mode === 'append') {
                        $parentId = $targetParentId;
                    } else {
                        $parentId = $idMap[$node['parent_temp_id']] ?? $targetParentId;
                    }
                }

                $item = UssdMenuItem::create([
                    'menu_id' => $menu->id,
                    'parent_item_id' => $parentId,
                    'option_trigger' => $node['option_trigger'],
                    'response_type' => $node['response_type'],
                    'response_text' => $node['response_text'],
                ]);

                $idMap[$tempId] = $item->id;
                $created++;
            }

            return $created;
        });
    }

    /**
     * Serialize an existing menu tree into editable indented outline text.
     *
     * @param  Collection<int, UssdMenuItem>  $items
     */
    public function toText(Collection $items): string
    {
        if ($items->isEmpty()) {
            return "Welcome to Q4 Services\n  1. Option One [END: Thank you]";
        }

        $root = $items->first(fn (UssdMenuItem $i) => $i->parent_item_id === null && strtoupper($i->option_trigger) === 'ROOT');
        $lines = [];

        if ($root) {
            $firstLine = strtok(trim((string) $root->response_text), "\n") ?: 'Welcome';
            $lines[] = $firstLine;
            $lines = array_merge($lines, $this->serializeChildren($items, $root->id, 1));
        }

        // Also export any top-level items directly attached with parent_item_id = null (other than ROOT)
        $orphanRoots = $items->filter(fn (UssdMenuItem $i) => $i->parent_item_id === null && (! $root || $i->id !== $root->id));
        foreach ($orphanRoots as $topItem) {
            $lines[] = $this->formatLine($topItem, $root ? 1 : 0, $items);
            $lines = array_merge($lines, $this->serializeChildren($items, $topItem->id, $root ? 2 : 1));
        }

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, UssdMenuItem>  $items
     * @return array<int, string>
     */
    protected function serializeChildren(Collection $items, int $parentId, int $depth): array
    {
        $lines = [];
        $children = $items->filter(fn (UssdMenuItem $i) => (int) $i->parent_item_id === $parentId);

        foreach ($children as $child) {
            $lines[] = $this->formatLine($child, $depth, $items);
            $lines = array_merge($lines, $this->serializeChildren($items, $child->id, $depth + 1));
        }

        return $lines;
    }

    /**
     * @param  Collection<int, UssdMenuItem>  $items
     */
    protected function formatLine(UssdMenuItem $item, int $depth, Collection $items): string
    {
        $indent = str_repeat('  ', $depth);
        $hasChildren = $items->contains(fn (UssdMenuItem $i) => (int) $i->parent_item_id === $item->id);
        $raw = trim((string) $item->response_text);

        if (str_contains($raw, '|')) {
            [$label, $body] = explode('|', $raw, 2);
            $label = trim($label);
            $body = trim($body);
        } else {
            $firstLine = trim(strtok($raw, "\n") ?: $raw);
            $label = preg_replace('/^You selected\s+/i', '', $firstLine) ?: $firstLine;
            $body = $firstLine;
        }

        if ($hasChildren && $item->response_type === 'CON') {
            return "{$indent}{$item->option_trigger}. {$label}";
        }

        return "{$indent}{$item->option_trigger}. {$label} [{$item->response_type}: {$body}]";
    }

    /**
     * Extract trigger, label, response type, and custom body from a single outline line.
     *
     * @return array{trigger: string, label: string, type: ?string, custom_body: ?string}
     */
    protected function extractTypeAndBody(string $line): array
    {
        $trigger = '1';
        $rest = trim($line);

        if (preg_match('/^([A-Za-z0-9*#]{1,8})[.)\]:]\s+(.+)$/', $rest, $m)) {
            $trigger = trim($m[1]);
            $rest = trim($m[2]);
        }

        $type = null;
        $customBody = null;

        if (preg_match('/^(.*?)\s*\[(CON|END|API)(?:\s*:\s*([^\]]*))?\]\s*$/i', $rest, $tm)) {
            $rest = trim($tm[1]);
            $type = strtoupper($tm[2]);
            $customBody = isset($tm[3]) && trim($tm[3]) !== '' ? trim($tm[3]) : null;
        } elseif (preg_match('/^(.*?)\s*=>\s*(.+)$/', $rest, $am)) {
            $rest = trim($am[1]);
            $customBody = trim($am[2]);
        }

        return [
            'trigger' => $trigger,
            'label' => trim($rest),
            'type' => $type,
            'custom_body' => $customBody,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    protected function buildScreenBody(string $heading, array $children, bool $includeBack): string
    {
        $lines = [$heading];
        foreach ($children as $child) {
            $lines[] = "{$child['option_trigger']}. {$child['label']}";
        }
        if ($includeBack) {
            $lines[] = '0. Back';
        }

        return implode("\n", $lines);
    }
}
