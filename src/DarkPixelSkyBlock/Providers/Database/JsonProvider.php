<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Providers\Database;

use DarkPixelSkyBlock\Main;

/**
 * JsonProvider
 *
 * Stores player profiles as individual JSON files under
 * plugin_data/DarkPixelSkyBlock/data/json/<PlayerName>.json
 */
final class JsonProvider {

    private string $dataPath;

    public function __construct(private readonly Main $plugin) {
        $this->dataPath = $plugin->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR;
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    public function getName(): string { return "JSON"; }

    /** @return array<string, mixed> */
    public function load(string $playerName): array {
        $file = $this->getFilePath($playerName);
        if (!file_exists($file)) return [];

        $raw = file_get_contents($file);
        if ($raw === false) return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $data */
    public function save(string $playerName, array $data): void {
        $file = $this->getFilePath($playerName);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function delete(string $playerName): void {
        $file = $this->getFilePath($playerName);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function exists(string $playerName): bool {
        return file_exists($this->getFilePath($playerName));
    }

    private function getFilePath(string $playerName): string {
        // Sanitise the filename to prevent directory traversal
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $playerName);
        return $this->dataPath . $safe . ".json";
    }
}
