<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;

use kenygamer\Core\LangManager;

class Say extends PluginCommand{

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Broadcasts the given message as the sender");
        $this->setUsage("/say <action>");
        $this->setPermission("core.command.say");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.say")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-say-usage", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			$sender->getServer()->broadcastMessage(TextFormat::LIGHT_PURPLE . "[Server] " . TextFormat::RESET . TextFormat::LIGHT_PURPLE . implode(" ", $args));
			return false;
		}
		$sender->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[" . TextFormat::RESET . $sender->getDisplayName() . TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "] " . TextFormat::RESET . TextFormat::LIGHT_PURPLE . implode(" ", $args));
		return true;
	}
}