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
 * SkillsMenu
 *
 * Displays all skills with their current level and XP progress.
 * Skill definitions and slot positions are pulled from menus.yml.
 */
final class SkillsMenu {

    /** Maps skill name → display item factory */
    private const SKILL_ITEMS = [
        "farming"    => "WHEAT",
        "mining"     => "IRON_PICKAXE",
        "combat"     => "IRON_SWORD",
        "foraging"   => "OAK_SAPLING",
        "fishing"    => "FISHING_ROD",
        "enchanting" => "ENCHANTED_BOOK",
        "alchemy"    => "BREWING_STAND",
        "taming"     => "BONE",
        "carpentry"  => "CRAFTING_TABLE",
    ];

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $this->plugin->getLogger()->info("Opening menu: SkillsMenu");
        $cfg   = $this->plugin->getConfigManager()->getSubmenuConfig("skills_menu");
        $title = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lYour Skills"));
        $back  = (int) ($cfg["back_slot"] ?? 49);
        $im    = $this->plugin->getItemManager();
        $pm    = $this->plugin->getProfileManager();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);
        $inv  = $menu->getInventory();

        // Fill background
        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) $inv->setItem($i, $filler);

        // Populate skill buttons starting at slot 10, wrapping every 7 columns
        $skillSlots = [10, 12, 14, 16, 19, 21, 23, 25, 28];
        $i = 0;
        foreach (self::SKILL_ITEMS as $skill => $itemConst) {
            $skillLevel = $pm->getSkillLevel($player, $skill);
            $skillXp    = $pm->getSkillXp($player, $skill);

            // Build item via VanillaItems constant name
            $baseItem = $this->resolveItem($itemConst);

            $skillName = ucfirst($skill);
            $baseItem->setCustomName("§a§l" . $skillName);
            $baseItem->setLore([
                "",
                "§7Level: §b" . $skillLevel,
                "§7XP: §f"    . number_format($skillXp, 1),
                "",
                "§7Gain XP through {$skillName}",
                "§7activities on your island.",
                "",
                "§eClick for details!",
            ]);

            $slot = $skillSlots[$i] ?? ($i + 10);
            $inv->setItem($slot, $baseItem);
            $i++;
        }

        // Back button
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

    private function resolveItem(string $constName): \pocketmine\item\Item {
        return match ($constName) {
            "WHEAT"          => VanillaItems::WHEAT()->setCount(1),
            "IRON_PICKAXE"   => VanillaItems::IRON_PICKAXE()->setCount(1),
            "IRON_SWORD"     => VanillaItems::IRON_SWORD()->setCount(1),
            "OAK_SAPLING"    => VanillaItems::OAK_SAPLING()->setCount(1),
            "FISHING_ROD"    => VanillaItems::FISHING_ROD()->setCount(1),
            "ENCHANTED_BOOK" => VanillaItems::ENCHANTED_BOOK()->setCount(1),
            "BREWING_STAND"  => VanillaItems::BREWING_STAND()->setCount(1),
            "BONE"           => VanillaItems::BONE()->setCount(1),
            "CRAFTING_TABLE" => VanillaItems::CRAFTING_TABLE()->setCount(1),
            default          => VanillaItems::BARRIER()->setCount(1),
        };
    }
}
