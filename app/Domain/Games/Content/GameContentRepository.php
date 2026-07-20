<?php

namespace App\Domain\Games\Content;

/**
 * Loads bundled game content from PHP data files in resources/game-content.
 *
 * Content lives as plain PHP `return [...]` files so contributors can edit them
 * without touching code or the database. Each file is compiled into OPcache once
 * and its returned value is memoized here, so every lookup after the first is a
 * pure in-memory read — no file I/O, no JSON decode, no query. This keeps the
 * fully-offline runtime instant even as content grows.
 */
final class GameContentRepository
{
    /**
     * Process-lifetime cache of decoded content sets, keyed by name.
     *
     * @var array<string, array<mixed>>
     */
    private static array $cache = [];

    /**
     * Load a content set by name (the file's basename in resources/game-content,
     * e.g. 'word-match'). Returns an empty array if the file is missing.
     *
     * @return array<mixed>
     */
    public function load(string $name): array
    {
        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }

        $path = resource_path('game-content/'.$name.'.php');

        $data = is_file($path) ? require $path : [];

        return self::$cache[$name] = is_array($data) ? $data : [];
    }

    /**
     * Drop the in-memory cache (used in tests that swap content files).
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
