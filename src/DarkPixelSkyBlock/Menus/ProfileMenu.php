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
 * ProfileMenu
 *
 * Displays the player's SkyBlock profile summary — level, coins, bank,
 * statistics — with a back button to the main menu.
 */
final class ProfileMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: ProfileMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("profile_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lYour Profile"));
        $back  = (int) ($cfg["back_slot"] ?? 49);

        $pm      = $this->plugin->getProfileManager();
        $im      = $this->plugin->getItemManager();
        $profile = $pm->getProfile($player);

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        // Fill background
        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // ── Player Head (centre top) ──────────────────────────────────────────
        $head = $this->plugin->getItemManager()->createPlayerHeadItem();
        $head->setCustomName("§e§l" . $player->getName());
        $head->setLore([
            "",
            "§7SkyBlock Level: §b" . ($profile["level"] ?? 1),
            "§7Coins: §6"          . number_format((float) ($profile["coins"] ?? 0)),
            "§7Bank: §6"           . number_format((float) ($profile["bank"]  ?? 0)),
            "",
            "§7Last Seen: §f" . date("Y-m-d", (int) ($profile["last_seen"] ?? time())),
        ]);
        $inv->setItem(22, $head);

        // ── Statistics ───────────────────────────────────────────────────────
        $stats = (array) ($profile["statistics"] ?? []);
        $statItem = VanillaItems::PAPER()->setCount(1);
        $statItem->setCustomName("§e§lStatistics");
        $statItem->setLore([
            "",
            "§7Blocks Broken: §f"  . number_format((int) ($stats["blocks_broken"] ?? 0)),
            "§7Kills: §f"          . number_format((int) ($stats["kills"]         ?? 0)),
            "§7Deaths: §f"         . number_format((int) ($stats["deaths"]        ?? 0)),
            "§7Items Crafted: §f"  . number_format((int) ($stats["items_crafted"] ?? 0)),
        ]);
        $inv->setItem(31, $statItem);

        // ── Back button ──────────────────────────────────────────────────────
        $inv->setItem($back, $im->createBackButton());

        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($back): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();
                if ($slot === $back) {
                    $this->plugin->getSoundManager()->playSound($player, "menu_back");
                    $this->plugin->getMenuManager()->openMainMenu($player);
                } else {
                    $this->plugin->getSoundManager()->playSound($player, "menu_click");
                }
            }
        ));

        $menu->send($player);
    }
}
