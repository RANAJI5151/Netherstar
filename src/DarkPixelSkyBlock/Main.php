<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock;

use DarkPixelSkyBlock\Commands\GiveMenuCommand;
use DarkPixelSkyBlock\Commands\SBMenuCommand;
use DarkPixelSkyBlock\Listeners\InventoryListener;
use DarkPixelSkyBlock\Listeners\MenuItemListener;
use DarkPixelSkyBlock\Listeners\PlayerListener;
use DarkPixelSkyBlock\Managers\ConfigManager;
use DarkPixelSkyBlock\Managers\DataManager;
use DarkPixelSkyBlock\Managers\EconomyManager;
use DarkPixelSkyBlock\Managers\ItemManager;
use DarkPixelSkyBlock\Managers\ProfileManager;
use DarkPixelSkyBlock\Managers\SoundManager;
use DarkPixelSkyBlock\Menus\MenuManager;
use DarkPixelSkyBlock\Tasks\AutoSaveTask;
use DarkPixelSkyBlock\Tasks\MenuRestoreTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Throwable;

/**
 * DarkPixelSkyBlock — Main Plugin Entry Point
 *
 * Orchestrates all managers, registers listeners/commands, and
 * schedules recurring tasks. All critical startup steps are wrapped
 * in try-catch so a single misconfiguration cannot crash the server.
 */
final class Main extends PluginBase {

    use SingletonTrait;

    private ConfigManager $configManager;
    private ItemManager $itemManager;
    private SoundManager $soundManager;
    private EconomyManager $economyManager;
    private DataManager $dataManager;
    private ProfileManager $profileManager;
    private MenuManager $menuManager;

    // ─────────────────────────────────────────────────────────────────────────
    // PLUGIN LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────────

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        // ── 1. Register InvMenu virion handler ───────────────────────────────
        if (!InvMenuHandler::isRegistered()) {
            try {
                InvMenuHandler::register($this);
                $this->getLogger()->debug("InvMenuHandler registered by DarkPixelSkyBlock.");
            } catch (\Throwable $e) {
                $this->getLogger()->critical("Failed to register InvMenuHandler: " . $e->getMessage());
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        // ── 2. Save default resource files ───────────────────────────────────
        $this->saveDefaultResources();

        // ── 3. Boot all managers in dependency order ──────────────────────────
        try {
            $this->configManager  = new ConfigManager($this);
            $this->dataManager    = new DataManager($this);
            $this->economyManager = new EconomyManager($this);
            $this->itemManager    = new ItemManager($this);
            $this->soundManager   = new SoundManager($this);
            $this->profileManager = new ProfileManager($this);
            $this->menuManager    = new MenuManager($this);
        } catch (Throwable $e) {
            $this->getLogger()->critical("Manager initialisation failed: " . $e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // ── 4. Register event listeners ───────────────────────────────────────
        try {
            $this->registerListeners();
        } catch (Throwable $e) {
            $this->getLogger()->error("Failed to register listeners: " . $e->getMessage());
        }

        // ── 5. Register commands ──────────────────────────────────────────────
        try {
            $this->registerCommands();
        } catch (Throwable $e) {
            $this->getLogger()->error("Failed to register commands: " . $e->getMessage());
        }

        // ── 6. Schedule recurring tasks ───────────────────────────────────────
        try {
            $this->scheduleTasks();
        } catch (Throwable $e) {
            $this->getLogger()->error("Failed to schedule tasks: " . $e->getMessage());
        }

        $this->getLogger()->info("§aDarkPixelSkyBlock v" . $this->getDescription()->getVersion() . " enabled!");
    }

    protected function onDisable(): void {
        // Persist all player data before shutdown
        if (isset($this->profileManager)) {
            try {
                $this->profileManager->saveAll();
            } catch (Throwable $e) {
                $this->getLogger()->error("Error saving profiles on disable: " . $e->getMessage());
            }
        }

        $this->getLogger()->info("§cDarkPixelSkyBlock disabled. All data saved.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INITIALISATION HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** Copy all default resource files from the phar to the data folder. */
    private function saveDefaultResources(): void {
        foreach (["config.yml", "menus.yml", "messages.yml", "items.yml"] as $file) {
            // saveResource will not overwrite an existing file by default
            $this->saveResource($file);
        }
    }

    /** Register all event listeners with the plugin manager. */
    private function registerListeners(): void {
        $pm = $this->getServer()->getPluginManager();
        $pm->registerEvents(new PlayerListener($this), $this);
        $pm->registerEvents(new InventoryListener($this), $this);
        $pm->registerEvents(new MenuItemListener($this), $this);
    }

    /** Register all plugin commands. */
    private function registerCommands(): void {
        $this->getServer()->getCommandMap()->registerAll("darkpixelskyblock", [
            new SBMenuCommand($this),
            new GiveMenuCommand($this),
        ]);
    }

    /** Schedule all repeating tasks. */
    private function scheduleTasks(): void {
        $scheduler = $this->getScheduler();

        // Hotbar restore — configurable interval
        $restoreInterval = max(10, (int) ($this->configManager->getMenuItemConfig()["restore_interval"] ?? 40));
        $scheduler->scheduleRepeatingTask(new MenuRestoreTask($this), $restoreInterval);

        // Auto-save player data
        $saveInterval = max(200, (int) ($this->configManager->getDatabaseConfig()["auto_save_interval"] ?? 6000));
        $scheduler->scheduleRepeatingTask(new AutoSaveTask($this), $saveInterval);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────────────────

    public function getConfigManager(): ConfigManager {
        return $this->configManager;
    }

    public function getItemManager(): ItemManager {
        return $this->itemManager;
    }

    public function getSoundManager(): SoundManager {
        return $this->soundManager;
    }

    public function getEconomyManager(): EconomyManager {
        return $this->economyManager;
    }

    public function getDataManager(): DataManager {
        return $this->dataManager;
    }

    public function getProfileManager(): ProfileManager {
        return $this->profileManager;
    }

    public function getMenuManager(): MenuManager {
        return $this->menuManager;
    }
}
