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
 * FastTravelMenu
 *
 * Allows players to teleport between defined SkyBlock locations.
 * Locations are configurable through menus.yml → fast_travel_menu.locations.
 * Future expansion: add unlock conditions, per-player unlocked destinations.
 */
final class FastTravelMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $cfg       = $this->plugin->getConfigManager()->getSubmenuConfig("fast_travel_menu");
        $title     = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lFast Travel"));
        $back      = (int) ($cfg["back_slot"] ?? 49);
        $locations = (array) ($cfg["locations"] ?? []);
        $im        = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::COMPASS()->setCount(1);
        $header->setCustomName("§b§lFast Travel");
        $header->setLore(["", "§7Teleport to SkyBlock locations.", "§7Unlock more through progression."]);
        $inv->setItem(4, $header);

        // Location buttons starting at slot 10
        $locationSlots = [10, 11, 12, 13, 14, 19, 20, 21, 22, 23];
        foreach ($locations as $index => $locationName) {
            if (!isset($locationSlots[$index])) break;

            $slot = $locationSlots[$index];
            $item = VanillaItems::COMPASS()->setCount(1);
            $item->setCustomName("§b§l" . $locationName);
            $item->setLore([
                "",
                "§7Teleport to §b" . $locationName . "§7.",
                "",
                "§eClick to travel!",
            ]);
            $inv->setItem($slot, $item);
        }

        $inv->setItem($back, $im->createBackButton());

        // Keep a reference to location names for click handler
        $locationMap = [];
        foreach ($locations as $index => $locationName) {
            if (isset($locationSlots[$index])) {
                $locationMap[$locationSlots[$index]] = (string) $locationName;
            }
        }

        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($back, $locationMap): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();

                $this->plugin->getSoundManager()->playSound($player, "menu_click");

                if ($slot === $back) {
                    $this->plugin->getMenuManager()->openMainMenu($player);
                    return;
                }

                if (isset($locationMap[$slot])) {
                    $loc = $locationMap[$slot];
                    // Future: perform actual teleport
                    $player->removeCurrentWindow();
                    $player->sendMessage(
                        $this->plugin->getConfigManager()->getPrefix() .
                        "§7Traveling to §b{$loc}§7... §c(Coming Soon)"
                    );
                }
            }
        ));

        $menu->send($player);
    }
}
