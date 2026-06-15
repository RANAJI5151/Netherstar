<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Tasks;

use DarkPixelSkyBlock\Main;
use pocketmine\scheduler\Task;
use Throwable;

/**
 * MenuRestoreTask
 *
 * Repeating task (interval: menu_item.restore_interval ticks).
 * Scans all online players and silently restores the SkyBlock menu item
 * to anyone whose locked hotbar slot is missing or holds the wrong item.
 *
 * PERFORMANCE
 * ───────────
 * The hot path is a single $inv->getItem($slot) O(1) lookup per player.
 * isMenuItem() only needs to check the NBT of ONE item per tick per player.
 * The expensive removeAllMenuItems() scan only runs during the rare case
 * where a restoration is actually needed.
 */
final class MenuRestoreTask extends Task {

    public function __construct(private readonly Main $plugin) {}

    public function onRun(): void {
        $im         = $this->plugin->getItemManager();
        $lockedSlot = $im->getHotbarSlot();

        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            // Skip disconnecting or not-yet-spawned players
            if (!$player->isOnline() || !$player->isConnected() || !$player->isAlive()) {
                continue;
            }

            // Skip admins with the bypass permission
            if ($player->hasPermission("darkpixelskyblock.bypass.restrictions")) {
                continue;
            }

            try {
                // ── O(1) check — inspect only the locked slot ─────────────────
                $item = $player->getInventory()->getItem($lockedSlot);
                if (!$im->isMenuItem($item)) {
                    // Restoration required — ensureMenuItem handles dedup + silent flag
                    $im->ensureMenuItem($player);
                }
            } catch (Throwable $e) {
                $this->plugin->getLogger()->error(
                    "MenuRestoreTask error for " . $player->getName() . ": " . $e->getMessage()
                );
            }
        }
    }
}
