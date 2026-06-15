<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Managers;

use DarkPixelSkyBlock\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Throwable;

/**
 * ItemManager
 *
 * Creates, identifies, and manages all special items (hotbar menu item,
 * GUI fillers, buttons). The menu item stack is cached after first build
 * and cloned on every use — avoids repeated config/NBT construction.
 */
final class ItemManager {

    private string $nbtKey;
    private string $nbtValue;
    private int    $lockedSlot;

    /**
     * Cached menu item stack — clone this instead of rebuilding every time.
     * Invalidated by calling invalidateCache().
     */
    private ?Item $cachedMenuStack = null;

    /** Cached filler item. */
    private ?Item $cachedFiller = null;

    public function __construct(private readonly Main $plugin) {
        $cfg              = $plugin->getConfigManager()->getMenuItemConfig();
        $this->nbtKey     = (string) ($cfg["nbt_tag_key"]   ?? "DarkPixelSkyBlock");
        $this->nbtValue   = (string) ($cfg["nbt_tag_value"] ?? "SkyBlockMenu");
        $this->lockedSlot = $plugin->getConfigManager()->getLockedSlot();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MENU ITEM FACTORY
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return a fresh copy of the SkyBlock menu Nether Star.
     * Uses internal cache when performance.cache_items is enabled.
     */
    public function createMenuItemStack(): Item {
        if ($this->plugin->getConfigManager()->isCacheItems()) {
            if ($this->cachedMenuStack === null) {
                $this->cachedMenuStack = $this->buildMenuItemStack();
            }
            return clone $this->cachedMenuStack;
        }
        return $this->buildMenuItemStack();
    }

    private function buildMenuItemStack(): Item {
        $cfg  = $this->plugin->getConfigManager()->getItemConfig("skyblock_menu_item");
        $name = TextFormat::colorize((string) ($cfg["name"] ?? "§bSkyBlock Menu"));
        $lore = array_map(
            static fn(string $l) => TextFormat::colorize($l),
            (array) ($cfg["lore"] ?? [])
        );

        $item = VanillaItems::NETHER_STAR()->setCount(1);
        $item->setCustomName($name);
        $item->setLore($lore);

        // Embed the identifying NBT string tag
        $tag = $item->getNamedTag();
        $tag->setString($this->nbtKey, $this->nbtValue);
        $item->setNamedTag($tag);

        return $item;
    }

    /** Force a rebuild of the cached menu item (e.g. after config reload). */
    public function invalidateCache(): void {
        $this->cachedMenuStack = null;
        $this->cachedFiller    = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ITEM IDENTIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns true if the given Item is the SkyBlock menu item.
     * Uses NBT tag check — immune to display name changes.
     */
    public function isMenuItem(Item $item): bool {
        if ($item->isNull()) return false;
        try {
            $tag = $item->getNamedTag()->getTag($this->nbtKey);
            return $tag instanceof StringTag && $tag->getValue() === $this->nbtValue;
        } catch (Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOTBAR MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Give the menu item to a player.
     * - Removes all existing copies first (no duplicates).
     * - Places the item in the locked hotbar slot.
     */
    public function giveMenuItem(Player $player): void {
        try {
            $inv = $player->getInventory();
            $this->removeAllMenuItems($player);
            $inv->setItem($this->lockedSlot, $this->createMenuItemStack());

            $this->plugin->getConfigManager()->debugLog(
                "Menu item given to " . $player->getName() . " at slot {$this->lockedSlot}"
            );
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "Failed to give menu item to " . $player->getName() . ": " . $e->getMessage()
            );
        }
    }

    /**
     * Silently ensure the menu item is in the locked slot.
     * Only acts when restoration is actually needed (cheap O(1) check first).
     * Does NOT send restore messages unless silent_restore is false.
     */
    public function ensureMenuItem(Player $player): void {
        try {
            $inv  = $player->getInventory();
            $item = $inv->getItem($this->lockedSlot);

            // Fast path: item is already correct
            if ($this->isMenuItem($item)) return;

            // Remove any stray duplicates, then restore to the locked slot
            $this->removeAllMenuItems($player);
            $inv->setItem($this->lockedSlot, $this->createMenuItemStack());

            $this->plugin->getConfigManager()->debugLog(
                "Restored menu item for " . $player->getName()
            );

            // Message and sound — only when not in silent mode
            if (!$this->plugin->getConfigManager()->isSilentRestore()) {
                $player->sendMessage(
                    $this->plugin->getConfigManager()->getMessage("menu_item.restored")
                );
                $this->plugin->getSoundManager()->playSound($player, "item_restore");
            }
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "ensureMenuItem failed for " . $player->getName() . ": " . $e->getMessage()
            );
        }
    }

    /**
     * Remove every copy of the menu item from the player's inventory.
     * Scans only the player's own inventory — not armour or off-hand.
     */
    public function removeAllMenuItems(Player $player): void {
        $inv = $player->getInventory();
        foreach ($inv->getContents() as $slot => $item) {
            if ($this->isMenuItem($item)) {
                $inv->clear($slot);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GUI ITEM FACTORIES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a gray stained glass pane filler (cached).
     */
    public function createFillerItem(): Item {
        if ($this->cachedFiller !== null) {
            return clone $this->cachedFiller;
        }
        $cfg  = $this->plugin->getConfigManager()->getItemConfig("filler");
        $name = TextFormat::colorize((string) ($cfg["name"] ?? "§r"));
        $item = VanillaBlocks::GRAY_STAINED_GLASS_PANE()->asItem()->setCount(1);
        $item->setCustomName($name);
        $item->setLore([]);
        $this->cachedFiller = $item;
        return clone $item;
    }

    /**
     * Create a named GUI button from items.yml.
     *
     * @param  Item                  $base         Base item type (will be mutated)
     * @param  string                $configKey    Key in items.yml
     * @param  array<string, string> $placeholders Placeholder replacements
     */
    public function createButton(Item $base, string $configKey, array $placeholders = []): Item {
        try {
            $cfg  = $this->plugin->getConfigManager()->getItemConfig($configKey);
            $name = (string) ($cfg["name"] ?? "§r" . $configKey);
            $lore = (array)  ($cfg["lore"] ?? []);

            foreach ($placeholders as $key => $value) {
                $name = str_replace("{" . $key . "}", $value, $name);
                $lore = array_map(
                    static fn(string $l) => str_replace("{" . $key . "}", $value, $l),
                    $lore
                );
            }

            $base->setCustomName(TextFormat::colorize($name));
            $base->setLore(array_map([TextFormat::class, "colorize"], $lore));
        } catch (Throwable) {
            $base->setCustomName("§c" . $configKey);
        }
        return $base;
    }

    /**
     * Safe player-representative item for use in GUIs.
     *
     * PocketMine-MP 5.x VanillaItems::PLAYER_HEAD() is fully supported as a
     * GUI item; it renders as the default Steve head on Bedrock. Setting a
     * specific player's skin requires extra NBT skin data — use this method
     * when a head-like icon is needed without skin customisation.
     */
    public function createPlayerHeadItem(): Item {
        try {
            return VanillaItems::PLAYER_HEAD()->setCount(1);
        } catch (Throwable) {
            // Fallback: nether star is always available
            return VanillaItems::NETHER_STAR()->setCount(1);
        }
    }

    /** Create a "Coming Soon" placeholder button. */
    public function createComingSoonItem(): Item {
        return $this->createButton(VanillaItems::BARRIER()->setCount(1), "coming_soon");
    }

    /** Create the standard "Go Back" button. */
    public function createBackButton(): Item {
        return $this->createButton(VanillaItems::ARROW()->setCount(1), "back_button");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    public function getHotbarSlot(): int  { return $this->lockedSlot; }
    public function getNbtKey(): string   { return $this->nbtKey; }
    public function getNbtValue(): string { return $this->nbtValue; }
}
