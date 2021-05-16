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

use kenygamer\Core\LangManager;

class Snoop extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Snoop Commands");
        $this->setUsage("/snoop");
        $this->setPermission("core.command.snoop");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.snoop")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (!isset($this->plugin->snoopers[$sender->getName()])) {
		    LangManager::send("core-snoop-on", $sender);
		    $this->plugin->snoopers[$sender->getName()] = true;
			return true;
		} else {
			LangManager::send("core-snoop-on", $sender);
		    unset($this->plugin->snoopers[$sender->getName()]);
		    return true;
		}
	}
}