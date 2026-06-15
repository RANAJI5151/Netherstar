<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Menus;

use DarkPixelSkyBlock\Main;
use DarkPixelSkyBlock\Utils\MenuUtils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

/**
 * CollectionsMenu
 *
 * Shows a player's resource collection progress grouped by category.
 * Designed for easy future expansion — add new categories/items without
 * touching this class's core layout logic.
 */
final class CollectionsMenu {

    /**
     * Collection categories shown as category headers.
     * Each category maps slot → [displayName, item, category].
     */
    private const CATEGORIES = [
        10 => ["§6§lFarming",   "WHEAT",        "farming"],
        12 => ["§7§lMining",    "COBBLESTONE",  "mining"],
        14 => ["§c§lCombat",    "ROTTEN_FLESH", "combat"],
        16 => ["§2§lForaging",  "OAK_LOG",      "foraging"],
        19 => ["§9§lFishing",   "COD",          "fishing"],
        21 => ["§5§lEnchanting","LAPIS_LAZULI",  "enchanting"],
        23 => ["§d§lFarming II","CARROT",        "farming_2"],
        25 => ["§e§lCrafting",  "CRAFTING_TABLE","crafting"],
    ];

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("collections_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lCollections"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        foreach (self::CATEGORIES as $slot => [$name, $itemKey, $category]) {
            $item = $this->resolveItem($itemKey);
            $item->setCustomName($name);
            $item->setLore([
                "",
                "§7View your {$category}",
                "§7collection progress.",
                "",
                "§eClick to view!",
            ]);
            $inv->setItem($slot, $item);
        }

        // Info item
        $info = VanillaItems::BOOK()->setCount(1);
        $info->setCustomName("§e§lCollections");
        $info->setLore([
            "",
            "§7Collect resources to",
            "§7unlock recipes, perks,",
            "§7and permanent upgrades!",
        ]);
        $inv->setItem(4, $info);

        $inv->setItem($back, $im->createBackButton());

        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($back): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();
                $this->plugin->getSoundManager()->playSound($player, "menu_click");
                if ($slot === $back) {
                    $this->plugin->getMenuManager()->openMainMenu($player);
                }
            }
        ));

        $menu->send($player);
    }

    private function resolveItem(string $key): \pocketmine\item\Item {
        return match ($key) {
            "WHEAT"          => VanillaItems::WHEAT()->setCount(1),
            "COBBLESTONE"    => VanillaItems::COBBLESTONE()->setCount(1),
            "ROTTEN_FLESH"   => VanillaItems::ROTTEN_FLESH()->setCount(1),
            "OAK_LOG"        => VanillaItems::OAK_LOG()->setCount(1),
            "COD"            => VanillaItems::COD()->setCount(1),
            "LAPIS_LAZULI"   => VanillaItems::LAPIS_LAZULI()->setCount(1),
            "CARROT"         => VanillaItems::CARROT()->setCount(1),
            "CRAFTING_TABLE" => VanillaItems::CRAFTING_TABLE()->setCount(1),
            default          => VanillaItems::BARRIER()->setCount(1),
        };
    }
}
