<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use pocketmine\player\Player;

/**
 * ProfileManager
 *
 * Manages in-memory player profiles (skills, collections, coins, level, etc.)
 * and delegates persistence to DataManager.
 *
 * Designed for extensibility — new fields can be added to the profile array
 * without touching the core save/load mechanics.
 */
final class ProfileManager {

    /**
     * In-memory cache: playerName → profile data array
     *
     * @var array<string, array<string, mixed>>
     */
    private array $profiles = [];

    /** Default structure for a brand-new profile */
    private const DEFAULT_PROFILE = [
        "coins"        => 500,
        "bank"         => 0,
        "level"        => 1,
        "level_xp"     => 0,
        "skills"       => [
            "farming"    => ["level" => 0, "xp" => 0.0],
            "mining"     => ["level" => 0, "xp" => 0.0],
            "combat"     => ["level" => 0, "xp" => 0.0],
            "foraging"   => ["level" => 0, "xp" => 0.0],
            "fishing"    => ["level" => 0, "xp" => 0.0],
            "enchanting" => ["level" => 0, "xp" => 0.0],
            "alchemy"    => ["level" => 0, "xp" => 0.0],
            "taming"     => ["level" => 0, "xp" => 0.0],
            "carpentry"  => ["level" => 0, "xp" => 0.0],
        ],
        "collections"  => [],
        "statistics"   => [
            "blocks_broken" => 0,
            "kills"         => 0,
            "deaths"        => 0,
            "items_crafted" => 0,
        ],
        "settings"     => [
            "show_tutorial"   => true,
            "sound_effects"   => true,
            "menu_animations" => true,
        ],
        "pets"         => [],
        "wardrobe"     => [],
        "fast_travel"  => [],
        "quests"       => [],
        "created_at"   => 0,
        "last_seen"    => 0,
    ];

    public function __construct(private readonly Main $plugin) {}

    // ─────────────────────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load a player's profile from storage into memory.
     * Creates a default profile if none exists.
     */
    public function loadProfile(Player $player): void {
        $name = $player->getName();
        $data = $this->plugin->getDataManager()->loadPlayer($name);

        if (empty($data)) {
            // Brand-new player — build a default profile
            $data                = self::DEFAULT_PROFILE;
            $data["coins"]       = (float) ($this->plugin->getConfigManager()->getEconomyConfig()["starting_coins"] ?? 500);
            $data["bank"]        = (float) ($this->plugin->getConfigManager()->getEconomyConfig()["starting_bank_coins"] ?? 0);
            $data["created_at"]  = time();
        }

        $data["last_seen"] = time();
        $this->profiles[$name] = array_replace_recursive(self::DEFAULT_PROFILE, $data);

        $this->plugin->getConfigManager()->debugLog("Loaded profile for {$name}");
    }

    /**
     * Save a player's in-memory profile to storage and optionally unload it.
     */
    public function saveProfile(Player|string $player, bool $unload = false): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        if (!isset($this->profiles[$name])) return;

        $this->profiles[$name]["last_seen"] = time();
        $this->plugin->getDataManager()->savePlayer($name, $this->profiles[$name]);

        if ($unload) {
            unset($this->profiles[$name]);
        }

        $this->plugin->getConfigManager()->debugLog("Saved profile for {$name}");
    }

    /**
     * Save all currently loaded profiles (called on shutdown or by AutoSaveTask).
     */
    public function saveAll(): void {
        foreach ($this->profiles as $name => $data) {
            $this->plugin->getDataManager()->savePlayer($name, $data);
        }
        $this->plugin->getLogger()->debug("ProfileManager: all profiles saved.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROFILE ACCESS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns true if a profile is currently loaded for the given player.
     */
    public function hasProfile(Player|string $player): bool {
        $name = $player instanceof Player ? $player->getName() : $player;
        return isset($this->profiles[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfile(Player|string $player): array {
        $name = $player instanceof Player ? $player->getName() : $player;
        return $this->profiles[$name] ?? self::DEFAULT_PROFILE;
    }

    /** @param array<string, mixed> $data */
    public function setProfile(Player|string $player, array $data): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name] = $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIELD GETTERS / SETTERS
    // ─────────────────────────────────────────────────────────────────────────

    public function getCoins(Player|string $player): float {
        return (float) ($this->getProfile($player)["coins"] ?? 0);
    }

    public function setCoins(Player|string $player, float $amount): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name]["coins"] = max(0.0, $amount);
    }

    public function addCoins(Player|string $player, float $amount): void {
        $this->setCoins($player, $this->getCoins($player) + $amount);
    }

    public function getBankBalance(Player|string $player): float {
        return (float) ($this->getProfile($player)["bank"] ?? 0);
    }

    public function setBankBalance(Player|string $player, float $amount): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name]["bank"] = max(0.0, $amount);
    }

    public function getLevel(Player|string $player): int {
        return (int) ($this->getProfile($player)["level"] ?? 1);
    }

    public function setLevel(Player|string $player, int $level): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name]["level"] = max(1, $level);
    }

    /**
     * @return array<string, array{level: int, xp: float}>
     */
    public function getSkills(Player|string $player): array {
        return (array) ($this->getProfile($player)["skills"] ?? []);
    }

    public function getSkillLevel(Player|string $player, string $skill): int {
        $skills = $this->getSkills($player);
        return (int) ($skills[$skill]["level"] ?? 0);
    }

    public function getSkillXp(Player|string $player, string $skill): float {
        $skills = $this->getSkills($player);
        return (float) ($skills[$skill]["xp"] ?? 0.0);
    }

    public function addSkillXp(Player|string $player, string $skill, float $xp): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        if (!isset($this->profiles[$name]["skills"][$skill])) {
            $this->profiles[$name]["skills"][$skill] = ["level" => 0, "xp" => 0.0];
        }
        $this->profiles[$name]["skills"][$skill]["xp"] += $xp;
    }

    public function setSkillLevel(Player|string $player, string $skill, int $level): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name]["skills"][$skill]["level"] = $level;
    }

    /**
     * @return array<string, int>
     */
    public function getStatistics(Player|string $player): array {
        return (array) ($this->getProfile($player)["statistics"] ?? []);
    }

    public function incrementStat(Player|string $player, string $stat, int $by = 1): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $current = (int) ($this->profiles[$name]["statistics"][$stat] ?? 0);
        $this->profiles[$name]["statistics"][$stat] = $current + $by;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(Player|string $player): array {
        return (array) ($this->getProfile($player)["settings"] ?? []);
    }

    public function getSetting(Player|string $player, string $key, mixed $default = null): mixed {
        return $this->getSettings($player)[$key] ?? $default;
    }

    public function setSetting(Player|string $player, string $key, mixed $value): void {
        $name = $player instanceof Player ? $player->getName() : $player;
        $this->profiles[$name]["settings"][$key] = $value;
    }
}
