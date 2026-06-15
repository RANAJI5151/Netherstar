<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Utils;

use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;

/**
 * ItemUtils
 *
 * Stateless helpers for working with PocketMine-MP Item objects.
 */
final class ItemUtils {

    private function __construct() {}

    /**
     * Check whether an item carries a specific NBT string tag key/value pair.
     */
    public static function hasNbtTag(Item $item, string $key, string $value): bool {
        if ($item->isNull()) return false;
        $tag = $item->getNamedTag()->getTag($key);
        return $tag instanceof StringTag && $tag->getValue() === $value;
    }

    /**
     * Set a string NBT tag on an item and return the mutated item.
     */
    public static function setNbtTag(Item $item, string $key, string $value): Item {
        $tag = $item->getNamedTag();
        $tag->setString($key, $value);
        $item->setNamedTag($tag);
        return $item;
    }

    /**
     * Returns true if the item is a named item (has a custom display name set).
     */
    public static function hasCustomName(Item $item): bool {
        return $item->hasCustomName();
    }

    /**
     * Compare two items by type and NBT, ignoring count and damage.
     */
    public static function isSameType(Item $a, Item $b): bool {
        return $a->getTypeId() === $b->getTypeId();
    }

    /**
     * Clone an item and override its count.
     */
    public static function withCount(Item $item, int $count): Item {
        $clone = clone $item;
        $clone->setCount($count);
        return $clone;
    }
}
