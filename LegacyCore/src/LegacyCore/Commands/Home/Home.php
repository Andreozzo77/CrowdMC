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

class Home extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to your home");
        $this->setUsage("/home <name>");
		$this->setPermission("core.command.home");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.home")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-home-usage", $sender);
            return false;
        }
    	$name = preg_replace("/[^a-z0-9]/", "", (isset($args[0]) ? mb_strtolower($args[0]) : "home"));
    	foreach($this->plugin->homes->getAll()[$sender->getName()] as $home) {
    		if ($home["name"] === $name) {
    			$sender->teleport(new Position($home["x"], $home["y"], $home["z"], $this->plugin->getServer()->getLevelByName($home["world"])));
    			LangManager::send("teleporting", $sender);
    			return true;
    		}
    	}
    	LangManager::send("core-home-notfound", $sender, $name);
		return true;
	}
}