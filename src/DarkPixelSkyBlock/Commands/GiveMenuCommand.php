<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Commands;

use DarkPixelSkyBlock\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * GiveMenuCommand
 *
 * /givemenuitem [player] — gives the SkyBlock menu item to the target.
 * With no argument: gives to self (player only).
 * With argument: gives to named player (requires op permission).
 */
final class GiveMenuCommand extends Command {

    public function __construct(private readonly Main $plugin) {
        parent::__construct(
            "givemenuitem",
            "Give the SkyBlock Menu item to a player",
            "/givemenuitem [player]",
            ["givesb"]
        );
        $this->setPermission("darkpixelskyblock.command.givemenuitem");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return true;
        }

        $cfg = $this->plugin->getConfigManager();

        // If no argument, give to self (must be a player)
        if (empty($args)) {
            if (!$sender instanceof Player) {
                $sender->sendMessage($cfg->getMessage("general.player_only"));
                return true;
            }
            $this->plugin->getItemManager()->giveMenuItem($sender);
            $sender->sendMessage($cfg->getMessage("menu_item.given_self"));
            return true;
        }

        // Give to named player
        $targetName = $args[0];
        $target     = $this->plugin->getServer()->getPlayerByPrefix($targetName);

        if ($target === null || !$target->isOnline()) {
            $sender->sendMessage($cfg->getMessage("general.player_not_found", [
                "player" => $targetName,
            ]));
            return true;
        }

        $this->plugin->getItemManager()->giveMenuItem($target);
        $target->sendMessage($cfg->getMessage("menu_item.given_self"));
        $sender->sendMessage($cfg->getMessage("menu_item.given", [
            "player" => $target->getName(),
        ]));

        return true;
    }
}
