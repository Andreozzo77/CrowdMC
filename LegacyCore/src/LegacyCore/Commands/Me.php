<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin;

use kenygamer\Core\LangManager;

class Me extends PluginCommand{

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Perform the specified action in chat");
        $this->setUsage("/me <action>");
        $this->setPermission("core.command.me");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.me")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-me-usage", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			$sender->getServer()->broadcastMessage("* Server" . TextFormat::RESET . " > " . implode(" ", $args));
			return false;
		}
		$sender->getServer()->broadcastMessage("* " . TextFormat::clean($sender->getDisplayName()) . TextFormat::RESET . " > " . implode(" ", $args));
	    return true;
	}
}