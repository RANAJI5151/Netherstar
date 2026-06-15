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
 * QuestMenu
 *
 * Displays active and completed quests for the player.
 * Supports future expansion via the profile's quests array.
 */
final class QuestMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("quest_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lQuest Log"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();
        $pm    = $this->plugin->getProfileManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::WRITABLE_BOOK()->setCount(1);
        $header->setCustomName("§e§lQuest Log");
        $header->setLore([
            "",
            "§7Complete quests to earn",
            "§7rewards and SkyBlock XP.",
        ]);
        $inv->setItem(4, $header);

        // Active quests placeholder
        $activeLabel = VanillaItems::LIME_DYE()->setCount(1);
        $activeLabel->setCustomName("§a§lActive Quests");
        $activeLabel->setLore(["§7No active quests."]);
        $inv->setItem(19, $activeLabel);

        // Completed quests placeholder
        $doneLabel = VanillaItems::GRAY_DYE()->setCount(1);
        $doneLabel->setCustomName("§7§lCompleted Quests");
        $doneLabel->setLore(["§7No completed quests yet."]);
        $inv->setItem(25, $doneLabel);

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
