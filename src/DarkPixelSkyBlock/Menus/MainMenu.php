<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Menus;

use DarkPixelSkyBlock\Main;
use DarkPixelSkyBlock\Utils\MenuUtils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use Throwable;

/**
 * MainMenu
 *
 * The primary SkyBlock double-chest GUI (54 slots) that replicates the
 * Hypixel SkyBlock Menu layout.
 *
 * PLAYER HEAD NOTE
 * ────────────────
 * VanillaItems::PLAYER_HEAD() is fully supported in PocketMine-MP 5.x and
 * renders as the default Steve head in Bedrock GUIs. Showing a specific
 * player's actual skin requires setting compound skin NBT data — that is
 * handled by ItemManager::createPlayerHeadItem() with a Nether Star fallback
 * so the GUI never breaks even if the head item is unavailable on a build.
 *
 * All button positions are configurable in menus.yml → main_menu.slots.
 */
final class MainMenu {

    public function __construct(private readonly Main $plugin) {}

    public function open(Player $player): void {
        $cfg      = $this->plugin->getConfigManager()->getMainMenuConfig();
        $title    = MenuUtils::colorize((string) ($cfg["title"] ?? "§8§lSkyBlock Menu"));
        $slots    = (array) ($cfg["slots"]           ?? []);
        $enabled  = (array) ($cfg["enabled_buttons"] ?? []);
        $features = $this->plugin->getConfigManager()->getFeaturesConfig();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName($title);

        $inv = $menu->getInventory();
        $im  = $this->plugin->getItemManager();

        // ── Fill all 54 slots with gray glass pane background ────────────────
        $filler = $im->createFillerItem();
        for ($i = 0; $i < 54; $i++) {
            $inv->setItem($i, $filler);
        }

        // ── Profile ──────────────────────────────────────────────────────────
        // Uses createPlayerHeadItem() which has a Nether Star fallback if
        // PLAYER_HEAD is unavailable on this build.
        if (($enabled["profile"] ?? true) && ($features["profile"] ?? true)) {
            $profile = $this->plugin->getProfileManager()->getProfile($player);
            $level   = (int) ($profile["level"] ?? 1);
            $coins   = number_format((float) ($profile["coins"] ?? 0));
            $slot    = (int) ($slots["profile"] ?? 4);

            $head = $im->createPlayerHeadItem();
            $btn  = $im->createButton($head, "profile_button", [
                "player" => $player->getName(),
                "level"  => (string) $level,
                "coins"  => $coins,
            ]);
            $inv->setItem($slot, $btn);
        }

        // ── SkyBlock Level ────────────────────────────────────────────────────
        if (($enabled["skyblock_level"] ?? true) && ($features["skyblock_level"] ?? true)) {
            $level = (int) ($this->plugin->getProfileManager()->getProfile($player)["level"] ?? 1);
            $slot  = (int) ($slots["skyblock_level"] ?? 13);
            $btn   = $im->createButton(
                VanillaItems::EXPERIENCE_BOTTLE()->setCount(1),
                "skyblock_level_button",
                ["level" => (string) $level]
            );
            $inv->setItem($slot, $btn);
        }

        // ── Skills ────────────────────────────────────────────────────────────
        if (($enabled["skills"] ?? true) && ($features["skills"] ?? true)) {
            $slot = (int) ($slots["skills"] ?? 19);
            $btn  = $im->createButton(VanillaItems::DIAMOND_SWORD()->setCount(1), "skills_button");
            $inv->setItem($slot, $btn);
        }

        // ── Collections ───────────────────────────────────────────────────────
        if (($enabled["collections"] ?? true) && ($features["collections"] ?? true)) {
            $slot = (int) ($slots["collections"] ?? 20);
            $btn  = $im->createButton(VanillaItems::BOOK()->setCount(1), "collections_button");
            $inv->setItem($slot, $btn);
        }

        // ── Recipe Book ───────────────────────────────────────────────────────
        if (($enabled["recipe_book"] ?? true) && ($features["recipe_book"] ?? true)) {
            $slot = (int) ($slots["recipe_book"] ?? 21);
            $btn  = $im->createButton(VanillaItems::BOOK()->setCount(1), "recipe_book_button");
            $inv->setItem($slot, $btn);
        }

        // ── Trades ────────────────────────────────────────────────────────────
        if (($enabled["trades"] ?? true) && ($features["trades"] ?? true)) {
            $slot = (int) ($slots["trades"] ?? 22);
            $btn  = $im->createButton(VanillaItems::EMERALD()->setCount(1), "trades_button");
            $inv->setItem($slot, $btn);
        }

        // ── Quest Log ─────────────────────────────────────────────────────────
        if (($enabled["quest_log"] ?? true) && ($features["quest_log"] ?? true)) {
            $slot = (int) ($slots["quest_log"] ?? 23);
            $btn  = $im->createButton(VanillaItems::WRITABLE_BOOK()->setCount(1), "quest_log_button");
            $inv->setItem($slot, $btn);
        }

        // ── Calendar & Events ─────────────────────────────────────────────────
        if (($enabled["calendar"] ?? true) && ($features["calendar"] ?? true)) {
            $slot = (int) ($slots["calendar"] ?? 24);
            $btn  = $im->createButton(VanillaItems::CLOCK()->setCount(1), "calendar_button");
            $inv->setItem($slot, $btn);
        }

        // ── Storage ───────────────────────────────────────────────────────────
        if (($enabled["storage"] ?? true) && ($features["storage"] ?? true)) {
            $slot = (int) ($slots["storage"] ?? 25);
            $btn  = $im->createButton(VanillaItems::CHEST()->setCount(1), "storage_button");
            $inv->setItem($slot, $btn);
        }

        // ── Personal Bank ─────────────────────────────────────────────────────
        // Uses gold ingot instead of player head — clearer bank semantics and
        // avoids player-head skin complexity.
        if (($enabled["bank"] ?? true) && ($features["bank"] ?? true)) {
            $bank = number_format($this->plugin->getProfileManager()->getBankBalance($player));
            $slot = (int) ($slots["bank"] ?? 31);
            $btn  = $im->createButton(
                VanillaItems::GOLD_INGOT()->setCount(1),
                "bank_button",
                ["bank" => $bank]
            );
            $inv->setItem($slot, $btn);
        }

        // ── Pets ──────────────────────────────────────────────────────────────
        if (($enabled["pets"] ?? true) && ($features["pets"] ?? true)) {
            $slot = (int) ($slots["pets"] ?? 28);
            $btn  = $im->createButton(VanillaItems::BONE()->setCount(1), "pets_button");
            $inv->setItem($slot, $btn);
        }

        // ── Crafting ──────────────────────────────────────────────────────────
        if (($enabled["crafting"] ?? true) && ($features["crafting"] ?? true)) {
            $slot = (int) ($slots["crafting"] ?? 29);
            $btn  = $im->createButton(VanillaItems::CRAFTING_TABLE()->setCount(1), "crafting_button");
            $inv->setItem($slot, $btn);
        }

        // ── Wardrobe ──────────────────────────────────────────────────────────
        if (($enabled["wardrobe"] ?? true) && ($features["wardrobe"] ?? true)) {
            $slot = (int) ($slots["wardrobe"] ?? 30);
            $btn  = $im->createButton(VanillaItems::LEATHER_CHESTPLATE()->setCount(1), "wardrobe_button");
            $inv->setItem($slot, $btn);
        }

        // ── Equipment ─────────────────────────────────────────────────────────
        if (($enabled["equipment"] ?? true) && ($features["equipment"] ?? true)) {
            $slot = (int) ($slots["equipment"] ?? 32);
            $btn  = $im->createButton(VanillaItems::IRON_CHESTPLATE()->setCount(1), "equipment_button");
            $inv->setItem($slot, $btn);
        }

        // ── Fast Travel ───────────────────────────────────────────────────────
        if (($enabled["fast_travel"] ?? true) && ($features["fast_travel"] ?? true)) {
            $slot = (int) ($slots["fast_travel"] ?? 33);
            $btn  = $im->createButton(VanillaItems::COMPASS()->setCount(1), "fast_travel_button");
            $inv->setItem($slot, $btn);
        }

        // ── Settings ──────────────────────────────────────────────────────────
        if (($enabled["settings"] ?? true) && ($features["settings"] ?? true)) {
            $slot = (int) ($slots["settings"] ?? 49);
            $btn  = $im->createButton(VanillaItems::NAME_TAG()->setCount(1), "settings_button");
            $inv->setItem($slot, $btn);
        }

        // ── Close ─────────────────────────────────────────────────────────────
        if ($enabled["close"] ?? true) {
            $slot = (int) ($slots["close"] ?? 53);
            $btn  = $im->createButton(VanillaItems::BARRIER()->setCount(1), "close_button");
            $inv->setItem($slot, $btn);
        }

        // ── Booster Cookie ────────────────────────────────────────────────────
        if (($enabled["booster_cookie"] ?? true) && ($features["booster_cookie"] ?? true)) {
            $slot = (int) ($slots["booster_cookie"] ?? 10);
            $btn  = $im->createButton(VanillaItems::COOKIE()->setCount(1), "booster_cookie_button");
            $inv->setItem($slot, $btn);
        }

        // ── Information ───────────────────────────────────────────────────────
        if ($enabled["information"] ?? true) {
            $slot = (int) ($slots["information"] ?? 40);
            $btn  = $im->createButton(VanillaItems::TORCH()->setCount(1), "information_button");
            $inv->setItem($slot, $btn);
        }

        // ─────────────────────────────────────────────────────────────────────
        // CLICK HANDLER
        // ─────────────────────────────────────────────────────────────────────
        $menu->setListener(InvMenu::readonly(
            function (DeterministicInvMenuTransaction $tx) use ($slots, $enabled, $features): void {
                $player = $tx->getPlayer();
                $slot   = $tx->getAction()->getSlot();

                $this->plugin->getSoundManager()->playSound($player, "menu_click");

                try {
                    $this->handleClick($player, $slot, $slots, $enabled, $features);
                } catch (Throwable $e) {
                    $this->plugin->getLogger()->error(
                        "MainMenu click error for " . $player->getName() . ": " . $e->getMessage()
                    );
                }
            }
        ));

        $menu->send($player);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLICK DISPATCH
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param array<string, int>  $slots
     * @param array<string, bool> $enabled
     * @param array<string, bool> $features
     */
    private function handleClick(
        Player $player,
        int    $slot,
        array  $slots,
        array  $enabled,
        array  $features
    ): void {
        $mm = $this->plugin->getMenuManager();

        match (true) {
            $slot === ($slots["profile"]       ?? 4)  => $mm->openProfileMenu($player),
            $slot === ($slots["skyblock_level"] ?? 13) => $this->showComingSoon($player),
            $slot === ($slots["skills"]         ?? 19) => $mm->openSkillsMenu($player),
            $slot === ($slots["collections"]    ?? 20) => $mm->openCollectionsMenu($player),
            $slot === ($slots["recipe_book"]    ?? 21) => $mm->openRecipeBookMenu($player),
            $slot === ($slots["trades"]         ?? 22) => $this->showComingSoon($player),
            $slot === ($slots["quest_log"]      ?? 23) => $mm->openQuestMenu($player),
            $slot === ($slots["calendar"]       ?? 24) => $this->showComingSoon($player),
            $slot === ($slots["storage"]        ?? 25) => $mm->openStorageMenu($player),
            $slot === ($slots["bank"]           ?? 31) => $this->showComingSoon($player),
            $slot === ($slots["pets"]           ?? 28) => $this->showComingSoon($player),
            $slot === ($slots["crafting"]       ?? 29) => $this->showComingSoon($player),
            $slot === ($slots["wardrobe"]       ?? 30) => $mm->openWardrobeMenu($player),
            $slot === ($slots["equipment"]      ?? 32) => $mm->openEquipmentMenu($player),
            $slot === ($slots["fast_travel"]    ?? 33) => $mm->openFastTravelMenu($player),
            $slot === ($slots["settings"]       ?? 49) => $mm->openSettingsMenu($player),
            $slot === ($slots["close"]          ?? 53) => $this->closeMenu($player),
            $slot === ($slots["booster_cookie"] ?? 10) => $this->showComingSoon($player),
            $slot === ($slots["information"]    ?? 40) => $this->showInformation($player),
            default => null
        };
    }

    private function showComingSoon(Player $player): void {
        $player->sendMessage(
            $this->plugin->getConfigManager()->getMessage("menus.coming_soon")
        );
        $this->plugin->getSoundManager()->playSound($player, "error");
    }

    private function closeMenu(Player $player): void {
        $this->plugin->getSoundManager()->playSound($player, "menu_close");
        $player->removeCurrentWindow();
    }

    private function showInformation(Player $player): void {
        $ver = "2.0.0";
        $player->sendMessage(
            $this->plugin->getConfigManager()->getPrefix() .
            "§bDarkPixelSkyBlock §7v{$ver} — Hypixel SkyBlock Menu for PocketMine-MP"
        );
    }
}
