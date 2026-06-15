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
 * StorageMenu
 *
 * Shows the player's personal storage chests.
 * Each storage slot can be expanded to open a separate sub-inventory.
 * Future expansion: load/save individual storage pages from DataManager.
 */
final class StorageMenu {

    private const MAX_STORAGE_PAGES = 8;

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: StorageMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("storage_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lStorage"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::CHEST()->setCount(1);
        $header->setCustomName("§6§lStorage");
        $header->setLore([
            "",
            "§7Store your items across",
            "§7multiple storage pages.",
        ]);
        $inv->setItem(4, $header);

        // Storage page buttons
        $storageSlots = [10, 11, 12, 13, 14, 15, 16, 17];
        for ($page = 1; $page <= self::MAX_STORAGE_PAGES; $page++) {
            $pageItem = VanillaItems::CHEST()->setCount($page);
            $pageItem->setCustomName("§6§lStorage " . $page);
            $pageItem->setLore([
                "",
                "§7Click to access",
                "§7Storage page {$page}.",
                "",
                "§eClick to open!",
            ]);
            $inv->setItem($storageSlots[$page - 1], $pageItem);
        }

        $inv->setItem($back, $im->createBackButton());

        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($back): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();
                $this->plugin->getSoundManager()->playSound($player, "menu_click");
                if ($slot === $back) {
                    $this->plugin->getMenuManager()->openMainMenu($player);
                } else {
                    $player->sendMessage(
                        $this->plugin->getConfigManager()->getMessage("menus.coming_soon")
                    );
                }
            }
        ));

        $menu->send($player);
    }
}
