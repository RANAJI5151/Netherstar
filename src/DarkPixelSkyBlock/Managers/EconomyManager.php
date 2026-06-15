<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use DarkPixelSkyBlock\Providers\Economy\EconomyAPIProvider;
use DarkPixelSkyBlock\Providers\Economy\InternalEconomyProvider;
use pocketmine\player\Player;

/**
 * EconomyManager
 *
 * Facade that delegates economy operations to the configured provider.
 * Providers are swappable via config.yml → economy.provider.
 */
final class EconomyManager {

    /** @var EconomyAPIProvider|InternalEconomyProvider */
    private object $provider;

    public function __construct(private readonly Main $plugin) {
        $this->loadProvider();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROVIDER LOADING
    // ─────────────────────────────────────────────────────────────────────────

    private function loadProvider(): void {
        $cfg      = $this->plugin->getConfigManager()->getEconomyConfig();
        $provName = strtolower((string) ($cfg["provider"] ?? "internal"));

        $this->provider = match ($provName) {
            "economyapi" => new EconomyAPIProvider($this->plugin),
            default      => new InternalEconomyProvider($this->plugin),
        };

        $this->plugin->getLogger()->info(
            "Economy provider loaded: §e" . $this->provider->getName()
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    public function getBalance(Player|string $player): float {
        $name = $player instanceof Player ? $player->getName() : $player;
        return $this->provider->getBalance($name);
    }

    public function setBalance(Player|string $player, float $amount): bool {
        $name = $player instanceof Player ? $player->getName() : $player;
        return $this->provider->setBalance($name, $amount);
    }

    public function addBalance(Player|string $player, float $amount): bool {
        $name    = $player instanceof Player ? $player->getName() : $player;
        $current = $this->getBalance($name);
        return $this->provider->setBalance($name, $current + $amount);
    }

    public function subtractBalance(Player|string $player, float $amount): bool {
        $name    = $player instanceof Player ? $player->getName() : $player;
        $current = $this->getBalance($name);
        if ($current < $amount) {
            return false;
        }
        return $this->provider->setBalance($name, $current - $amount);
    }

    public function hasBalance(Player|string $player, float $amount): bool {
        return $this->getBalance($player) >= $amount;
    }

    public function getProviderName(): string {
        return $this->provider->getName();
    }

    public function isAvailable(): bool {
        return $this->provider->isAvailable();
    }
}
