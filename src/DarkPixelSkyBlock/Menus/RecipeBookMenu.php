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
 * RecipeBookMenu
 *
 * Displays SkyBlock-specific craftable recipes.
 * Designed for future expansion — recipes can be loaded from config or
 * a database without modifying this class's structure.
 */
final class RecipeBookMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: RecipeBookMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("recipe_book_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lRecipe Book"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::BOOK()->setCount(1);
        $header->setCustomName("§e§lRecipe Book");
        $header->setLore([
            "",
            "§7Browse all unlocked",
            "§7SkyBlock recipes.",
            "",
            "§7Unlock new recipes by",
            "§7levelling up Collections.",
        ]);
        $inv->setItem(4, $header);

        // Placeholder recipe slots — expandable
        $slots = [10, 11, 12, 13, 14, 15, 16,
                  19, 20, 21, 22, 23, 24, 25,
                  28, 29, 30, 31, 32, 33, 34];

        foreach ($slots as $i => $slot) {
            $item = $im->createComingSoonItem();
            $inv->setItem($slot, $item);
        }

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
}
