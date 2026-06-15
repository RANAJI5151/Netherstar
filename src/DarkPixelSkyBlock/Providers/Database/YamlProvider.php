<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Providers\Database;

use DarkPixelSkyBlock\Main;
use pocketmine\utils\Config;

/**
 * YamlProvider
 *
 * Stores player profiles as YAML files via PocketMine's Config class under
 * plugin_data/DarkPixelSkyBlock/data/yaml/<PlayerName>.yml
 */
final class YamlProvider {

    private string $dataPath;

    public function __construct(private readonly Main $plugin) {
        $this->dataPath = $plugin->getDataFolder() . "data" . DIRECTORY_SEPARATOR . "yaml" . DIRECTORY_SEPARATOR;
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    public function getName(): string { return "YAML"; }

    /** @return array<string, mixed> */
    public function load(string $playerName): array {
        $file = $this->getFilePath($playerName);
        if (!file_exists($file)) return [];
        $cfg = new Config($file, Config::YAML);
        return $cfg->getAll();
    }

    /** @param array<string, mixed> $data */
    public function save(string $playerName, array $data): void {
        $file = $this->getFilePath($playerName);
        $cfg  = new Config($file, Config::YAML);
        $cfg->setAll($data);
        $cfg->save();
    }

    public function delete(string $playerName): void {
        $file = $this->getFilePath($playerName);
        if (file_exists($file)) unlink($file);
    }

    public function exists(string $playerName): bool {
        return file_exists($this->getFilePath($playerName));
    }

    private function getFilePath(string $playerName): string {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $playerName);
        return $this->dataPath . $safe . ".yml";
    }
}
