<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Listeners;

use DarkPixelSkyBlock\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\scheduler\ClosureTask;
use Throwable;

/**
 * PlayerListener
 *
 * Handles player join / quit / respawn to manage profile loading,
 * data saving, menu item restoration, and runtime state cleanup.
 */
final class PlayerListener implements Listener {

    public function __construct(private readonly Main $plugin) {}

    // ─────────────────────────────────────────────────────────────────────────
    // JOIN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * On join:
     *   1. Load profile from storage.
     *   2. Give the SkyBlock menu item (delayed 10 ticks so inventory is ready).
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        try {
            $this->plugin->getProfileManager()->loadProfile($player);
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "Failed to load profile for " . $player->getName() . ": " . $e->getMessage()
            );
        }

        // Small delay ensures the inventory is fully initialised before we write to it
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($player): void {
                if ($player->isOnline()) {
                    try {
                        $this->plugin->getItemManager()->giveMenuItem($player);
                    } catch (Throwable $e) {
                        $this->plugin->getLogger()->error(
                            "giveMenuItem on join failed for " . $player->getName() . ": " . $e->getMessage()
                        );
                    }
                }
            }),
            10 // 0.5 seconds
        );

        $this->plugin->getConfigManager()->debugLog("Player joined: " . $player->getName());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QUIT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * On quit:
     *   1. Save and unload the player's profile.
     *   2. Clear the open-menu state tracker (prevents stale entries).
     *
     * Note: MenuItemListener's per-player cooldown (1 second) is intentionally
     * left to expire naturally on quit — it's a float value and the memory
     * footprint is negligible. It will be overwritten on the player's next join.
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();

        try {
            $this->plugin->getProfileManager()->saveProfile($player, unload: true);
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "Failed to save profile for " . $player->getName() . ": " . $e->getMessage()
            );
        }

        // Clean up in-memory menu state
        $this->plugin->getMenuManager()->clearMenu($player);

        $this->plugin->getConfigManager()->debugLog("Player quit, data saved: " . $player->getName());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DEATH
    // ─────────────────────────────────────────────────────────────────────────

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $drops = $event->getDrops();
        foreach ($drops as $index => $item) {
            if ($this->plugin->getItemManager()->isMenuItem($item)) {
                unset($drops[$index]);
            }
        }
        $event->setDrops(array_values($drops));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RESPAWN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Restore the menu item after death/respawn.
     * Items drop on death in vanilla — the periodic restore task will also
     * catch this, but the explicit 5-tick delay here is faster.
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();

        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($player): void {
                if ($player->isOnline()) {
                    try {
                        $this->plugin->getItemManager()->giveMenuItem($player);
                    } catch (Throwable $e) {
                        $this->plugin->getLogger()->error(
                            "giveMenuItem on respawn failed for " . $player->getName() . ": " . $e->getMessage()
                        );
                    }
                }
            }),
            5
        );
    }
}
