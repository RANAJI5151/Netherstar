<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Providers\Economy;

use DarkPixelSkyBlock\Main;

/**
 * InternalEconomyProvider
 *
 * Standalone economy provider that stores balances in the plugin's
 * own profile data (ProfileManager) so no external plugin is required.
 */
final class InternalEconomyProvider {

    public function __construct(private readonly Main $plugin) {}

    public function getName(): string {
        return "Internal";
    }

    public function isAvailable(): bool {
        return true;
    }

    public function getBalance(string $playerName): float {
        return $this->plugin->getProfileManager()->getCoins($playerName);
    }

    public function setBalance(string $playerName, float $amount): bool {
        $this->plugin->getProfileManager()->setCoins($playerName, $amount);
        return true;
    }
}
