<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use DarkPixelSkyBlock\Providers\Database\JsonProvider;
use DarkPixelSkyBlock\Providers\Database\SQLiteProvider;
use DarkPixelSkyBlock\Providers\Database\YamlProvider;

/**
 * DataManager
 *
 * Facade that routes persistent-storage operations to the configured
 * database provider (JSON / YAML / SQLite).
 */
final class DataManager {

    /** @var JsonProvider|YamlProvider|SQLiteProvider */
    private object $provider;

    public function __construct(private readonly Main $plugin) {
        $this->loadProvider();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROVIDER LOADING
    // ─────────────────────────────────────────────────────────────────────────

    private function loadProvider(): void {
        $cfg      = $this->plugin->getConfigManager()->getDatabaseConfig();
        $provName = strtolower((string) ($cfg["provider"] ?? "json"));

        $this->provider = match ($provName) {
            "yaml"   => new YamlProvider($this->plugin),
            "sqlite" => new SQLiteProvider($this->plugin),
            default  => new JsonProvider($this->plugin),
        };

        $this->plugin->getLogger()->info(
            "Database provider loaded: §e" . $this->provider->getName()
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load all persisted data for a player.
     * Returns an empty array if no data exists yet.
     *
     * @return array<string, mixed>
     */
    public function loadPlayer(string $playerName): array {
        return $this->provider->load($playerName);
    }

    /**
     * Persist all data for a player.
     *
     * @param array<string, mixed> $data
     */
    public function savePlayer(string $playerName, array $data): void {
        $this->provider->save($playerName, $data);
    }

    /**
     * Delete all persisted data for a player (e.g. profile reset).
     */
    public function deletePlayer(string $playerName): void {
        $this->provider->delete($playerName);
    }

    /**
     * Check whether a save record exists for the given player.
     */
    public function playerExists(string $playerName): bool {
        return $this->provider->exists($playerName);
    }

    public function getProviderName(): string {
        return $this->provider->getName();
    }
}
