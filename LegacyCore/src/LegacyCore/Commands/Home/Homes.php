<?php

namespace LegacyCore\Commands\Home;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Homes extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("List your homes");
        $this->setUsage("/homes");
		$this->setPermission("core.command.homes");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.homes")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
    	$homes = $this->plugin->homes->getAll()[$sender->getName()];
    	if (empty($homes)) {
    		LangManager::send("core-homes-none", $sender);
    		return true;
    	}
    	$msg = LangManager::translate("core-homes", $sender);
		foreach($homes as $home) {
			$msg .= "\n" . LangManager::translate("core-homeentry", $sender, $home["name"], $home["x"], $home["y"], $home["z"], $home["world"]);
		}
		$sender->sendMessage($msg);
	    return true;
	}
}