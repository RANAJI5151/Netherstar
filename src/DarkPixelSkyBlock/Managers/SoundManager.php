<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

/**
 * SoundManager
 *
 * Plays named sounds defined in config.yml → sounds section.
 * Uses the low-level PlaySoundPacket so we can pass arbitrary
 * Bedrock sound identifiers without constructing Sound objects.
 */
final class SoundManager {

    /** @var array<string, array{id: string, volume: float, pitch: float}> */
    private array $sounds = [];

    public function __construct(private readonly Main $plugin) {
        $this->loadSounds();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOADING
    // ─────────────────────────────────────────────────────────────────────────

    private function loadSounds(): void {
        $raw = $this->plugin->getConfigManager()->getSoundsConfig();
        foreach ($raw as $key => $data) {
            if (!is_array($data)) continue;
            $this->sounds[(string) $key] = [
                "id"     => (string) ($data["id"]     ?? "random.click"),
                "volume" => (float)  ($data["volume"] ?? 1.0),
                "pitch"  => (float)  ($data["pitch"]  ?? 1.0),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PLAYBACK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Play a configured sound by its config key (e.g. "menu_open").
     * Silently does nothing if the key is not configured.
     */
    public function playSound(Player $player, string $soundKey): void {
        if (!isset($this->sounds[$soundKey])) {
            $this->plugin->getConfigManager()->debugLog(
                "SoundManager: unknown sound key '{$soundKey}'"
            );
            return;
        }

        $sound  = $this->sounds[$soundKey];
        $pos    = $player->getPosition();

        $pk         = new PlaySoundPacket();
        $pk->soundName = $sound["id"];
        $pk->x         = $pos->getX();
        $pk->y         = $pos->getY();
        $pk->z         = $pos->getZ();
        $pk->volume    = $sound["volume"];
        $pk->pitch     = $sound["pitch"];

        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
     * Play a raw sound by Bedrock sound identifier.
     */
    public function playSoundRaw(
        Player $player,
        string $soundId,
        float $volume = 1.0,
        float $pitch  = 1.0
    ): void {
        $pos = $player->getPosition();

        $pk            = new PlaySoundPacket();
        $pk->soundName = $soundId;
        $pk->x         = $pos->getX();
        $pk->y         = $pos->getY();
        $pk->z         = $pos->getZ();
        $pk->volume    = $volume;
        $pk->pitch     = $pitch;

        $player->getNetworkSession()->sendDataPacket($pk);
    }
}
