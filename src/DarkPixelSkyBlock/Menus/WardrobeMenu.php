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
 * WardrobeMenu
 *
 * Allows players to preview and equip different armour appearances.
 * Future expansion: load cosmetic skins from profile data.
 */
final class WardrobeMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("wardrobe_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lWardrobe"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Current armour display
        $armorSlots = [
            20 => [VanillaItems::LEATHER_HELMET(),     "§5§lHelmet",      "Equipped Helmet"],
            21 => [VanillaItems::LEATHER_CHESTPLATE(), "§5§lChestplate",  "Equipped Chestplate"],
            22 => [VanillaItems::LEATHER_LEGGINGS(),   "§5§lLeggings",    "Equipped Leggings"],
            23 => [VanillaItems::LEATHER_BOOTS(),      "§5§lBoots",       "Equipped Boots"],
        ];

        foreach ($armorSlots as $slot => [$baseItem, $name, $desc]) {
            $item = $baseItem->setCount(1);
            $item->setCustomName($name);
            $item->setLore(["", "§7{$desc}", "", "§eClick to change!"]);
            $inv->setItem($slot, $item);
        }

        // Header
        $header = VanillaItems::LEATHER_CHESTPLATE()->setCount(1);
        $header->setCustomName("§5§lWardrobe");
        $header->setLore(["", "§7Customise your armour appearance.", "§7More cosmetics coming soon!"]);
        $inv->setItem(4, $header);

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
