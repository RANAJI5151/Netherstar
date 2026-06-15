<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Listeners;

use DarkPixelSkyBlock\Main;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;
use Throwable;

/**
 * InventoryListener
 *
 * Surgically prevents the SkyBlock menu item from being moved, stored,
 * or duplicated while remaining completely non-interfering with:
 *   - InvMenu GUIs (other virions, submenus)
 *   - Wardrobe / Storage / Pets / Auction House / Bazaar inventories
 *   - Any custom container opened by another plugin
 *
 * STRATEGY
 * ─────────
 * We iterate over SlotChangeActions and look for exactly two conditions:
 *   1. The menu item is the SOURCE of an action in the PLAYER'S OWN inventory
 *      (i.e. it's being picked up / moved away from its locked slot).
 *   2. Something is being placed INTO the locked hotbar slot, displacing the
 *      menu item (target item is NOT the menu item, but the slot is locked).
 *
 * Any other action — clicks in chest inventories, InvMenu GUIs, other virion
 * inventories — passes through untouched.
 */
final class InventoryListener implements Listener {

    public function __construct(private readonly Main $plugin) {}

    public function onInventoryTransaction(InventoryTransactionEvent $event): void {
        // Skip cancelled transactions early
        if ($event->isCancelled()) return;

        try {
            $transaction = $event->getTransaction();
            $player      = $transaction->getSource();

            if (!$player instanceof Player) return;

            // Admins / bypass players are unrestricted
            if ($player->hasPermission("darkpixelskyblock.bypass.restrictions")) return;

            $im         = $this->plugin->getItemManager();
            $playerInv  = $player->getInventory();
            $lockedSlot = $im->getHotbarSlot();

            foreach ($transaction->getActions() as $action) {
                if (!$action instanceof SlotChangeAction) continue;

                $actionInv  = $action->getInventory();
                $sourceItem = $action->getSourceItem();
                $targetItem = $action->getTargetItem();
                $actionSlot = $action->getSlot();

                // ── Condition 1: Menu item being moved OUT of player inventory ──
                // Source is the menu item AND the action is in the player's own inventory.
                // This catches drags from the hotbar to another slot/container.
                if ($im->isMenuItem($sourceItem) && $actionInv === $playerInv) {
                    $this->cancelAndNotify($event, $player, "menu_item.cannot_move");
                    return;
                }

                // ── Condition 2: Something displacing the menu item's locked slot ──
                // A non-menu-item is being placed into the locked slot in the player's
                // inventory. This would overwrite/displace the menu item.
                if ($actionInv === $playerInv
                    && $actionSlot === $lockedSlot
                    && !$im->isMenuItem($targetItem)
                    && !$targetItem->isNull()
                ) {
                    $this->cancelAndNotify($event, $player, "menu_item.cannot_move");
                    return;
                }

                // ── Condition 3: Menu item arriving in a foreign inventory ──
                // This catches edge cases where the item somehow ends up being placed
                // into a chest/container by another mechanism.
                if ($im->isMenuItem($targetItem) && $actionInv !== $playerInv) {
                    $this->cancelAndNotify($event, $player, "menu_item.cannot_store");
                    return;
                }
            }
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "InventoryListener error: " . $e->getMessage()
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function cancelAndNotify(
        InventoryTransactionEvent $event,
        Player $player,
        string $messageKey
    ): void {
        $event->cancel();

        // Brief notification so the player understands what happened
        $player->sendMessage(
            $this->plugin->getConfigManager()->getMessage($messageKey)
        );

        $this->plugin->getSoundManager()->playSound($player, "error");

        $this->plugin->getConfigManager()->debugLog(
            "Blocked inventory transaction for " . $player->getName() . " ({$messageKey})"
        );

        // No delayed restore task needed — MenuRestoreTask handles periodic
        // restoration, and giveMenuItem() is called on every join/respawn.
    }
}
