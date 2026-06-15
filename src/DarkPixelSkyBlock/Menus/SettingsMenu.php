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
 * SettingsMenu
 *
 * Shows player-configurable SkyBlock settings. Each setting toggles
 * between on/off and is persisted via ProfileManager → settings array.
 * Future expansion: add new settings without touching the core menu logic.
 */
final class SettingsMenu {

    /** @var array<string, array{label: string, desc: string}> */
    private const SETTINGS = [
        "show_tutorial"   => ["label" => "Tutorial Hints",   "desc" => "Show helpful tutorial hints."],
        "sound_effects"   => ["label" => "Sound Effects",    "desc" => "Play menu sound effects."],
        "menu_animations" => ["label" => "Menu Animations",  "desc" => "Enable menu animations."],
    ];

    /** Slots assigned to each setting key (in order of SETTINGS) */
    private const SETTING_SLOTS = [20, 22, 24];

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: SettingsMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("settings_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lSettings"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();
        $pm    = $this->plugin->getProfileManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Header
        $header = VanillaItems::NAME_TAG()->setCount(1);
        $header->setCustomName("§7§lSettings");
        $header->setLore(["", "§7Configure your personal", "§7SkyBlock preferences."]);
        $inv->setItem(4, $header);

        // Setting toggles
        $slotIndex = 0;
        foreach (self::SETTINGS as $key => $info) {
            $slot    = self::SETTING_SLOTS[$slotIndex++] ?? ($slotIndex + 18);
            $enabled = (bool) $pm->getSetting($player, $key, true);

            $item = ($enabled ? VanillaItems::LIME_DYE() : VanillaItems::GRAY_DYE())->setCount(1);
            $item->setCustomName(($enabled ? "§a" : "§c") . "§l" . $info["label"]);
            $item->setLore([
                "",
                "§7" . $info["desc"],
                "",
                "§7Status: " . ($enabled ? "§aEnabled" : "§cDisabled"),
                "",
                "§eClick to toggle!",
            ]);
            $inv->setItem($slot, $item);
        }

        $inv->setItem($back, $im->createBackButton());

        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($back, $pm): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();

                $this->plugin->getSoundManager()->playSound($player, "menu_click");

                if ($slot === $back) {
                    $this->plugin->getMenuManager()->openMainMenu($player);
                    return;
                }

                // Toggle setting if the clicked slot matches a setting slot
                $slotIndex = 0;
                foreach (self::SETTINGS as $key => $info) {
                    $settingSlot = self::SETTING_SLOTS[$slotIndex++] ?? null;
                    if ($settingSlot === $slot) {
                        $current = (bool) $pm->getSetting($player, $key, true);
                        $pm->setSetting($player, $key, !$current);
                        // Re-open the menu to reflect the change
                        $this->plugin->getMenuManager()->openSettingsMenu($player);
                        return;
                    }
                }
            }
        ));

        $menu->send($player);
    }
}
