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
 * EquipmentMenu
 *
 * Displays the player's SkyBlock equipment slots (necklace, cloak, belt,
 * gloves) alongside their currently worn armour. Future expansion: load
 * equipment items from the profile's equipment array.
 */
final class EquipmentMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: EquipmentMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("equipment_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lEquipment"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::IRON_CHESTPLATE()->setCount(1);
        $header->setCustomName("§9§lEquipment");
        $header->setLore(["", "§7Equip special SkyBlock gear", "§7in your equipment slots."]);
        $inv->setItem(4, $header);

        // Equipment slots
        $equipmentSlots = [
            20 => ["§9§lNecklace", "§7Your necklace slot."],
            21 => ["§9§lCloak",    "§7Your cloak slot."],
            22 => ["§9§lBelt",     "§7Your belt slot."],
            23 => ["§9§lGloves",   "§7Your gloves slot."],
        ];

        foreach ($equipmentSlots as $slot => [$name, $lore]) {
            $item = VanillaItems::BARRIER()->setCount(1);
            $item->setCustomName($name);
            $item->setLore(["", $lore, "", "§7Empty", "", "§eClick to equip!"]);
            $inv->setItem($slot, $item);
        }

        // Currently equipped armour (read-only display)
        $armour = $player->getArmorInventory();
        $displaySlots = [28 => $armour->getHelmet(), 29 => $armour->getChestplate(),
                         30 => $armour->getLeggings(), 31 => $armour->getBoots()];

        foreach ($displaySlots as $slot => $piece) {
            if (!$piece->isNull()) {
                $inv->setItem($slot, clone $piece);
            }
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
