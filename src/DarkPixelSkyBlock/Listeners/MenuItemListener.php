<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Listeners;

use DarkPixelSkyBlock\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\Player;
use Throwable;

/**
 * MenuItemListener
 *
 * Handles all player interactions with the SkyBlock menu item:
 *
 *   - Right-click (air or block) → open SkyBlock menu with cooldown guard.
 *   - Drop key → cancel immediately and notify.
 *
 * COOLDOWN
 * ─────────
 * Both PlayerItemUseEvent and PlayerInteractEvent can fire for the same
 * right-click. The cooldown map (player name → last-open microtime) acts
 * as a deduplication gate: the first event opens the menu and stamps the
 * time; the second event (same tick) sees the cooldown and exits silently.
 */
final class MenuItemListener implements Listener {

    /**
     * Cooldown map: playerName → microtime(true) of last menu open.
     *
     * @var array<string, float>
     */
    private array $cooldowns = [];

    public function __construct(private readonly Main $plugin) {}

    // ─────────────────────────────────────────────────────────────────────────
    // RIGHT-CLICK IN AIR
    // ─────────────────────────────────────────────────────────────────────────

    public function onItemUse(PlayerItemUseEvent $event): void {
        if ($event->isCancelled()) return;

        $player = $event->getPlayer();
        $item   = $event->getItem();

        if (!$this->plugin->getItemManager()->isMenuItem($item)) return;

        $event->cancel();
        $this->tryOpenMenu($player);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RIGHT-CLICK ON BLOCK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handles right-clicking blocks while holding the menu item.
     * We only handle RIGHT_CLICK_BLOCK here — air is handled by onItemUse.
     * This prevents the double-fire issue when both events trigger.
     */
    public function onInteract(PlayerInteractEvent $event): void {
        if ($event->isCancelled()) return;

        // Only act on block clicks to avoid double-firing with PlayerItemUseEvent
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;

        $player = $event->getPlayer();
        $item   = $event->getItem();

        if (!$this->plugin->getItemManager()->isMenuItem($item)) return;

        $event->cancel();
        $this->tryOpenMenu($player);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DROP PREVENTION
    // ─────────────────────────────────────────────────────────────────────────

    public function onDropItem(PlayerDropItemEvent $event): void {
        if ($event->isCancelled()) return;

        $player = $event->getPlayer();
        $item   = $event->getItem();

        if (!$this->plugin->getItemManager()->isMenuItem($item)) return;
        if ($player->hasPermission("darkpixelskyblock.bypass.restrictions")) return;

        $event->cancel();
        $player->sendMessage(
            $this->plugin->getConfigManager()->getMessage("menu_item.cannot_drop")
        );
        $this->plugin->getSoundManager()->playSound($player, "error");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COOLDOWN-GATED OPEN
    // ─────────────────────────────────────────────────────────────────────────

    private function tryOpenMenu(Player $player): void {
        $name    = $player->getName();
        $now     = microtime(true);
        $cdSecs  = $this->plugin->getConfigManager()->getMenuOpenCooldown();

        if ($cdSecs > 0.0) {
            $last = $this->cooldowns[$name] ?? 0.0;
            if (($now - $last) < $cdSecs) {
                // Still within cooldown — silently ignore (prevents packet spam)
                $this->plugin->getConfigManager()->debugLog(
                    "Menu open blocked by cooldown for {$name}"
                );
                return;
            }
        }

        // Stamp cooldown BEFORE opening so double-fire events are swallowed
        $this->cooldowns[$name] = $now;

        try {
            $this->plugin->getMenuManager()->openMainMenu($player);
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "MenuItemListener: failed to open menu for {$name}: " . $e->getMessage()
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────────

    /** Clear the cooldown entry when a player quits to prevent memory leak. */
    public function clearCooldown(string $playerName): void {
        unset($this->cooldowns[$playerName]);
    }
}
