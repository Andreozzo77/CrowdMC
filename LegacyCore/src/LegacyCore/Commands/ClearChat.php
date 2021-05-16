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
use pocketmine\plugin\Plugin;

use kenygamer\Core\LangManager;

class ClearChat extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Clear everyone's chat");
        $this->setUsage("/clearchat");
        $this->setAliases(["ch"]);
        $this->setPermission("core.command.clearchat");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.clearchat")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		foreach($this->plugin->getServer()->getOnlinePlayers() as $target) {
            if ($target !== $sender){
            	for($i = 0; $i < 30; $i++){
            		$target->sendMessage(" ");
            	}
            }
        }
	    return true;
	}
}