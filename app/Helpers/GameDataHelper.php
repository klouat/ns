<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized JSON game data loader with caching.
 * Prevents repeated file_get_contents + json_decode on every request,
 * saving ~30-50MB RAM per request on large files like library.json.
 */
class GameDataHelper
{
    private static int $cache_ttl = 3600; // 1 hour

    public static function get_library(): array
    {
        return Cache::remember('gamedata:library', self::$cache_ttl, function () {
            return self::load_json('library.json');
        });
    }

    public static function get_skills(): array
    {
        return Cache::remember('gamedata:skills', self::$cache_ttl, function () {
            return self::load_json('skills.json');
        });
    }

    public static function get_items(): array
    {
        return Cache::remember('gamedata:items', self::$cache_ttl, function () {
            return self::load_json('items.json');
        });
    }

    public static function get_gamedata(): array
    {
        return Cache::remember('gamedata:gamedata', self::$cache_ttl, function () {
            return self::load_json('gamedata.json');
        });
    }

    public static function get_missions(): array
    {
        return Cache::remember('gamedata:missions', self::$cache_ttl, function () {
            return self::load_json('mission.json');
        });
    }

    /**
     * Find a specific section by ID inside gamedata.json.
     * e.g. get_gamedata_section('scratch') returns the 'data' of that section.
     */
    public static function get_gamedata_section(string $section_id): ?array
    {
        $gamedata = self::get_gamedata();

        foreach ($gamedata as $section) {
            if (isset($section['id']) && $section['id'] === $section_id) {
                return $section['data'] ?? null;
            }
        }

        return null;
    }

    /**
     * Find a single item by key-value from a cached dataset.
     * Avoids loading entire array into caller scope when only one item is needed.
     */
    public static function find_in_library(string $item_id): ?array
    {
        $items = self::get_library();

        foreach ($items as $item) {
            if (($item['id'] ?? null) === $item_id) {
                return $item;
            }
        }

        return null;
    }

    public static function find_skill(string $skill_id): ?array
    {
        $skills = self::get_skills();

        foreach ($skills as $skill) {
            if (($skill['skill_id'] ?? null) === $skill_id) {
                return $skill;
            }
        }

        return null;
    }

    public static function find_item(string $item_id): ?array
    {
        $items = self::get_items();

        foreach ($items as $item) {
            if (($item['item_id'] ?? null) === $item_id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Flush all cached game data.
     * Call this after updating any JSON file.
     */
    public static function flush(): void
    {
        $keys = [
            'gamedata:library',
            'gamedata:skills',
            'gamedata:items',
            'gamedata:gamedata',
            'gamedata:missions',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    private static function load_json(string $filename): array
    {
        $path = storage_path('app/' . $filename);

        if (!file_exists($path)) {
            Log::error("GameDataHelper: File not found", ['file' => $filename]);
            return [];
        }

        $content = file_get_contents($path);
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("GameDataHelper: JSON parse error", [
                'file'  => $filename,
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        return $data;
    }
}
