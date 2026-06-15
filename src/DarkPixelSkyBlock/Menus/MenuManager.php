<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Menus;

use DarkPixelSkyBlock\Main;
use pocketmine\player\Player;
use Throwable;

/**
 * MenuManager
 *
 * Central registry and factory for all SkyBlock menus.
 * Every open() call is wrapped in try-catch so a broken submenu never
 * crashes the server — errors are logged to console instead.
 *
 * The main-menu open cooldown is enforced by MenuItemListener (the call
 * site). Submenu navigation (back/forward) is instant — no cooldown.
 */
final class MenuManager {

    /**
     * Tracks the menu currently open per player (for back-navigation).
     * playerName → menu key (e.g. "main", "skills")
     *
     * @var array<string, string>
     */
    private array $openMenus = [];

    public function __construct(private readonly Main $plugin) {}

    // ─────────────────────────────────────────────────────────────────────────
    // OPEN HELPERS — each wrapped in try-catch
    // ─────────────────────────────────────────────────────────────────────────

    public function openMainMenu(Player $player): void {
        if (!$this->checkPermission($player, "darkpixelskyblock.command.sbmenu")) return;

        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "main";
        $this->plugin->getConfigManager()->debugLog("Opening MainMenu for " . $player->getName());

        try {
            (new MainMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "MainMenu", $e);
        }
    }

    public function openProfileMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "profile";
        try {
            (new ProfileMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "ProfileMenu", $e);
        }
    }

    public function openSkillsMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "skills";
        try {
            (new SkillsMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "SkillsMenu", $e);
        }
    }

    public function openCollectionsMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "collections";
        try {
            (new CollectionsMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "CollectionsMenu", $e);
        }
    }

    public function openRecipeBookMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "recipe_book";
        try {
            (new RecipeBookMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "RecipeBookMenu", $e);
        }
    }

    public function openQuestMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "quest";
        try {
            (new QuestMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "QuestMenu", $e);
        }
    }

    public function openStorageMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "storage";
        try {
            (new StorageMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "StorageMenu", $e);
        }
    }

    public function openWardrobeMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "wardrobe";
        try {
            (new WardrobeMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "WardrobeMenu", $e);
        }
    }

    public function openEquipmentMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "equipment";
        try {
            (new EquipmentMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "EquipmentMenu", $e);
        }
    }

    public function openFastTravelMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "fast_travel";
        try {
            (new FastTravelMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "FastTravelMenu", $e);
        }
    }

    public function openSettingsMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_open");
        $this->openMenus[$player->getName()] = "settings";
        try {
            (new SettingsMenu($this->plugin))->open($player);
        } catch (Throwable $e) {
            $this->handleMenuError($player, "SettingsMenu", $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATE TRACKING
    // ─────────────────────────────────────────────────────────────────────────

    public function getCurrentMenu(Player $player): string {
        return $this->openMenus[$player->getName()] ?? "none";
    }

    public function clearMenu(Player $player): void {
        unset($this->openMenus[$player->getName()]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTERNAL
    // ─────────────────────────────────────────────────────────────────────────

    private function checkPermission(Player $player, string $permission): bool {
        if (!$player->hasPermission($permission)) {
            $player->sendMessage(
                $this->plugin->getConfigManager()->getMessage("general.no_permission")
            );
            $this->plugin->getSoundManager()->playSound($player, "error");
            return false;
        }
        return true;
    }

    private function handleMenuError(Player $player, string $menuName, Throwable $e): void {
        $this->plugin->getLogger()->error(
            "Error opening {$menuName} for " . $player->getName() . ": " . $e->getMessage()
        );
        $this->plugin->getConfigManager()->debugLog($e->getTraceAsString());
        $player->sendMessage(
            $this->plugin->getConfigManager()->getMessage("errors.generic")
        );
    }
}
