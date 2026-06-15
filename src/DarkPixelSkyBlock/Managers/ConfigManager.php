<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

/**
 * ConfigManager
 *
 * Centralises access to all four configuration files (config, menus,
 * messages, items). Typed section helpers prevent raw Config access
 * from leaking into the rest of the codebase.
 */
final class ConfigManager {

    private Config $config;
    private Config $menusConfig;
    private Config $messagesConfig;
    private Config $itemsConfig;

    public function __construct(private readonly Main $plugin) {
        $dataFolder = $plugin->getDataFolder();

        $this->config         = new Config($dataFolder . "config.yml",   Config::YAML);
        $this->menusConfig    = new Config($dataFolder . "menus.yml",    Config::YAML);
        $this->messagesConfig = new Config($dataFolder . "messages.yml", Config::YAML);
        $this->itemsConfig    = new Config($dataFolder . "items.yml",    Config::YAML);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RAW CONFIG ACCESSORS
    // ─────────────────────────────────────────────────────────────────────────

    public function getConfig(): Config         { return $this->config; }
    public function getMenusConfig(): Config    { return $this->menusConfig; }
    public function getMessagesConfig(): Config { return $this->messagesConfig; }
    public function getItemsConfig(): Config    { return $this->itemsConfig; }

    // ─────────────────────────────────────────────────────────────────────────
    // config.yml SECTION HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function getGeneralConfig(): array {
        return (array) ($this->config->get("general", []));
    }

    /** @return array<string, mixed> */
    public function getDebugConfig(): array {
        return (array) ($this->config->get("debug", []));
    }

    /** @return array<string, mixed> */
    public function getMenuItemConfig(): array {
        return (array) ($this->config->get("menu_item", []));
    }

    /** @return array<string, mixed> */
    public function getCooldownsConfig(): array {
        return (array) ($this->config->get("cooldowns", []));
    }

    /** @return array<string, mixed> */
    public function getSoundsConfig(): array {
        return (array) ($this->config->get("sounds", []));
    }

    /** @return array<string, mixed> */
    public function getPerformanceConfig(): array {
        return (array) ($this->config->get("performance", []));
    }

    /** @return array<string, mixed> */
    public function getEconomyConfig(): array {
        return (array) ($this->config->get("economy", []));
    }

    /** @return array<string, mixed> */
    public function getDatabaseConfig(): array {
        return (array) ($this->config->get("database", []));
    }

    /** @return array<string, mixed> */
    public function getFeaturesConfig(): array {
        return (array) ($this->config->get("features", []));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONVENIENCE TYPED GETTERS
    // ─────────────────────────────────────────────────────────────────────────

    /** True when debug.enabled is set in config. */
    public function isDebugMode(): bool {
        return (bool) ($this->getDebugConfig()["enabled"] ?? false);
    }

    /** True when menu_item.silent_restore is set — suppress restore chat messages. */
    public function isSilentRestore(): bool {
        return (bool) ($this->getMenuItemConfig()["silent_restore"] ?? true);
    }

    /** Locked hotbar slot for the SkyBlock menu item (0-indexed). */
    public function getLockedSlot(): int {
        $slot = (int) ($this->getMenuItemConfig()["locked_slot"] ?? 8);
        return max(0, min(8, $slot));
    }

    /** Menu-open cooldown in seconds (0 = no cooldown). */
    public function getMenuOpenCooldown(): float {
        return max(0.0, (float) ($this->getCooldownsConfig()["menu_open"] ?? 1.0));
    }

    /** True when performance.cache_items is set. */
    public function isCacheItems(): bool {
        return (bool) ($this->getPerformanceConfig()["cache_items"] ?? true);
    }

    /** True when sounds.enabled is true. */
    public function areSoundsEnabled(): bool {
        return (bool) ($this->getSoundsConfig()["enabled"] ?? true);
    }

    public function isFeatureEnabled(string $feature): bool {
        return (bool) ($this->getFeaturesConfig()[$feature] ?? true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MENU-SPECIFIC HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function getMainMenuConfig(): array {
        return (array) ($this->menusConfig->get("main_menu", []));
    }

    /** @return array<string, mixed> */
    public function getSubmenuConfig(string $key): array {
        return (array) ($this->menusConfig->get($key, []));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MESSAGE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    public function getPrefix(): string {
        $raw = (string) ($this->messagesConfig->get("prefix", "&8[&bSkyBlock&8]&r "));
        return TextFormat::colorize($raw);
    }

    /**
     * Retrieve a message by dot-notation path and replace placeholders.
     *
     * @param  array<string, string> $placeholders
     */
    public function getMessage(string $path, array $placeholders = []): string {
        $parts   = explode(".", $path);
        $section = $this->messagesConfig->getAll();

        foreach ($parts as $part) {
            if (!is_array($section) || !array_key_exists($part, $section)) {
                return "§cMissing message: {$path}";
            }
            $section = $section[$part];
        }

        $message = is_string($section) ? $section : "§cInvalid message: {$path}";

        foreach ($placeholders as $key => $value) {
            $message = str_replace("{" . $key . "}", $value, $message);
        }

        return $this->getPrefix() . TextFormat::colorize($message);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ITEM HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function getItemConfig(string $key): array {
        return (array) ($this->itemsConfig->get($key, []));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGGING
    // ─────────────────────────────────────────────────────────────────────────

    /** Log a message only when debug mode is active. */
    public function debugLog(string $message): void {
        if ($this->isDebugMode()) {
            $this->plugin->getLogger()->debug("[DBG] " . $message);
        }
    }
}
