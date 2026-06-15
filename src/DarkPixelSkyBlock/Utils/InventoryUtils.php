<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

/**
 * InventoryUtils
 *
 * Stateless helpers for working with PocketMine-MP inventories.
 */
final class InventoryUtils {

    private function __construct() {}

    /**
     * Count how many items matching the given type exist in an inventory.
     */
    public static function countItems(Inventory $inventory, Item $target): int {
        $count = 0;
        foreach ($inventory->getContents() as $item) {
            if ($item->getTypeId() === $target->getTypeId()) {
                $count += $item->getCount();
            }
        }
        return $count;
    }

    /**
     * Return true if the inventory has at least $amount of the given item type.
     */
    public static function hasItem(Inventory $inventory, Item $target, int $amount = 1): bool {
        return self::countItems($inventory, $target) >= $amount;
    }

    /**
     * Find the first slot index containing an item matching the given type.
     * Returns -1 if not found.
     */
    public static function findSlot(Inventory $inventory, Item $target): int {
        foreach ($inventory->getContents() as $slot => $item) {
            if ($item->getTypeId() === $target->getTypeId()) {
                return $slot;
            }
        }
        return -1;
    }

    /**
     * Remove up to $amount of an item type from the inventory.
     * Returns how many were actually removed.
     */
    public static function removeItems(Inventory $inventory, Item $target, int $amount): int {
        $removed = 0;
        foreach ($inventory->getContents() as $slot => $item) {
            if ($removed >= $amount) break;
            if ($item->getTypeId() !== $target->getTypeId()) continue;

            $toRemove = min($item->getCount(), $amount - $removed);
            $removed += $toRemove;

            $item->setCount($item->getCount() - $toRemove);
            if ($item->getCount() <= 0) {
                $inventory->clear($slot);
            } else {
                $inventory->setItem($slot, $item);
            }
        }
        return $removed;
    }

    /**
     * Fill every slot in an inventory with the given item.
     * Useful for GUI background panes.
     */
    public static function fillInventory(Inventory $inventory, Item $filler): void {
        $size = $inventory->getSize();
        for ($i = 0; $i < $size; $i++) {
            $inventory->setItem($i, clone $filler);
        }
    }

    /**
     * Clear all slots in an inventory.
     */
    public static function clearInventory(Inventory $inventory): void {
        $inventory->clearAll();
    }

    /**
     * Count the number of empty slots in an inventory.
     */
    public static function countEmptySlots(Inventory $inventory): int {
        $empty = 0;
        for ($i = 0; $i < $inventory->getSize(); $i++) {
            if ($inventory->getItem($i)->isNull()) {
                $empty++;
            }
        }
        return $empty;
    }

    /**
     * Check whether a player has room for at least one more item stack.
     */
    public static function hasRoomFor(Player $player, Item $item): bool {
        return $player->getInventory()->canAddItem($item);
    }
}
