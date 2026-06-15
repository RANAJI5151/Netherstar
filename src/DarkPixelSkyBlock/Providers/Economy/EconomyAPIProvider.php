<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Providers\Economy;

use DarkPixelSkyBlock\Main;
use onebone\economyapi\EconomyAPI;

/**
 * EconomyAPIProvider
 *
 * Bridges DarkPixelSkyBlock's economy system to the popular EconomyAPI
 * (onebone/economyapi) plugin. Loaded only when provider = "economyapi"
 * is set in config.yml and the EconomyAPI plugin is actually present.
 */
final class EconomyAPIProvider {

    private ?EconomyAPI $api = null;

    public function __construct(private readonly Main $plugin) {
        $pm = $plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($pm instanceof EconomyAPI) {
            $this->api = $pm;
        } else {
            $plugin->getLogger()->warning(
                "EconomyAPI not found — falling back to internal economy."
            );
        }
    }

    public function getName(): string {
        return "EconomyAPI";
    }

    public function isAvailable(): bool {
        return $this->api !== null;
    }

    public function getBalance(string $playerName): float {
        if ($this->api === null) return 0.0;
        $bal = $this->api->myMoney($playerName);
        return $bal === false ? 0.0 : (float) $bal;
    }

    public function setBalance(string $playerName, float $amount): bool {
        if ($this->api === null) return false;
        $this->api->setMoney($playerName, $amount);
        return true;
    }
}
