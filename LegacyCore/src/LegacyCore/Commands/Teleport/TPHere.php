<?php

namespace LegacyCore\Commands\Teleport;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class TPHere extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport a player to you");
        $this->setUsage("/tphere <player>");
        $this->setPermission("core.command.teleport");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.teleport")) {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return false;
		}
		if (count($args) < 1) {
            $sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /tphere <player>");
            return false;
        }
		if (!empty($args[0])) {
            $target = $this->plugin->getServer()->getPlayer($args[0]);
			if ($target == null) {
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
				return false;
			}
			if ($target === $sender) {
				$sender->sendMessage(TextFormat::RED . "You Cannot Teleport Here yourself");
			    return false;
			}
            if ($target == true) {
			    $target->teleport($sender);
				Command::broadcastCommandMessage($sender, "Teleported " . $target->getName() . " to " . $sender->getName());
				return true;
            }
		}
	}
}