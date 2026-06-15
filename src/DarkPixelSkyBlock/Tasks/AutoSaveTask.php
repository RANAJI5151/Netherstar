<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Tasks;

use DarkPixelSkyBlock\Main;
use pocketmine\scheduler\Task;

/**
 * AutoSaveTask
 *
 * Repeating task that periodically saves all online player profiles to
 * the configured database provider. Interval is set in config.yml →
 * database.auto_save_interval (in ticks; default 6000 = 5 minutes).
 *
 * This is a safety net — profiles are also saved on PlayerQuitEvent and
 * on plugin disable.
 */
final class AutoSaveTask extends Task {

    public function __construct(private readonly Main $plugin) {}

    public function onRun(): void {
        $pm     = $this->plugin->getProfileManager();
        $server = $this->plugin->getServer();
        $count  = 0;

        foreach ($server->getOnlinePlayers() as $player) {
            if (!$player->isOnline()) continue;
            $pm->saveProfile($player);
            $count++;
        }

        if ($this->plugin->getConfigManager()->isDebugMode() && $count > 0) {
            $this->plugin->getLogger()->debug(
                "AutoSaveTask: saved {$count} player profile(s)."
            );
        }
    }
}
