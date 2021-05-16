<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;

class Gamemode extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Alter a player's gamemode");
        $this->setUsage("/gamemode <mode> <player>");
		$this->setAliases(["gm"]);
        $this->setPermission("core.command.gamemode");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.gamemode")){
			$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
			return true;
		}
		switch(count($args)){
			case 1:
				if(!($sender instanceof Player)){
					$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
					return true;
				}
				break;
			case 0:
            	$sender->sendMessage(TextFormat::GOLD . "Usage: " . TextFormat::GREEN . "/gamemode <mode> <player>");
            	return true;
				break;
		}
		$target = $sender;
        if (isset($args[1])) {
            if (!$sender->hasPermission("core.command.gamemode.other")) {
                $sender->sendMessage(TextFormat::RED . "You don't have permission to set another player's gamemode!");
                return false;
            } else {
				$target = Main::getInstance()->getPlayer($args[1]);
			    if ($target == null) {
                    $sender->sendMessage(TextFormat::RED . "That player cannot be found.");
                    return false;
				}
            }
        }
		// Gamemode Survival
		if (mb_strtolower($args[0]) == "0" || mb_strtolower($args[0]) == "suv" || mb_strtolower($args[0]) == "survival") {
			$target->setGamemode(0);
			if ($target === $sender) {
				Command::broadcastCommandMessage($sender, "Set your own game mode to Survival");
			} else {
				$target->sendMessage("You game mode change to Survival Mode");
				Command::broadcastCommandMessage($sender, "Set " . $target->getName() . "'s game mode to Survival");
			}
        }
		// Gamemode Creative
		if (mb_strtolower($args[0]) == "1" || mb_strtolower($args[0]) == "cre" || mb_strtolower($args[0]) == "creative") {
			$target->setGamemode(1);
			if ($target === $sender) {
				Command::broadcastCommandMessage($sender, "Set your own game mode to Creative");
			} else {
				$target->sendMessage("Your game mode changed to Creative");
				Command::broadcastCommandMessage($sender, "Set " . $target->getName() . "'s game mode to Creative");
			}
        }
		// Gamemode Adventure
		if (mb_strtolower($args[0]) == "2" || mb_strtolower($args[0]) == "adv" || mb_strtolower($args[0]) == "adventure") {
			$target->setGamemode(2);
			if ($target === $sender) {
				Command::broadcastCommandMessage($sender, "Set your own game mode to Adventure");
			} else {
				$target->sendMessage("Your game mode changed to Adventure");
				Command::broadcastCommandMessage($sender, "Set " . $target->getName() . "'s game mode to Adventure");
			}
        }
		// Gamemode Spectator
		if (mb_strtolower($args[0]) == "3" || mb_strtolower($args[0]) == "spe" || mb_strtolower($args[0]) == "spectator") {
			$target->setGamemode(3);
			if ($target === $sender) {
				Command::broadcastCommandMessage($sender, "Set your own game mode to Spectator");
			} else {
				$target->sendMessage("Your game mode changed to Spectator");
				Command::broadcastCommandMessage($sender, "Set " . $target->getName() . "'s game mode to Spectator");
			}
        }
		return true;
    }
}