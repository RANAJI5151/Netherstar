<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Commands;

use DarkPixelSkyBlock\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * SBMenuCommand
 *
 * /sbmenu — opens the SkyBlock main menu for the executing player.
 */
final class SBMenuCommand extends Command {

    public function __construct(private readonly Main $plugin) {
        parent::__construct(
            "sbmenu",
            "Open the SkyBlock Menu",
            "/sbmenu",
            ["skyblock", "sb"]
        );
        $this->setPermission("darkpixelskyblock.command.sbmenu");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(
                $this->plugin->getConfigManager()->getMessage("general.player_only")
            );
            return true;
        }

        if (!$this->testPermission($sender)) {
            return true;
        }

        $this->plugin->getMenuManager()->openMainMenu($sender);
        return true;
    }
}
