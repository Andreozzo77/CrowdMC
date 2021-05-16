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
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Broadcast extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Broadcast a message");
        $this->setUsage("/broadcast <action>");
		$this->setAliases(["bcast"]);
        $this->setPermission("core.command.broadcast");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.broadcast")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-broadcast-usage", $sender);
            return false;
        }
		$sender->getServer()->broadcastMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "» " . TextFormat::RESET . implode(" ", $args));
		return true;
	}
}