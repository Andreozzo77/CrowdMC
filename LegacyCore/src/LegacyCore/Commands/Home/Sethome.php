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
use kenygamer\Core\Main;

class Sethome extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Set your home");
        $this->setUsage("/sethome <name>");
		$this->setPermission("core.command.sethome");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.sethome")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-sethome-usage", $sender);
            return false;
        }
        if(!in_array($sender->getLevel()->getFolderName(), Main::SETPOINT_WORLDS)){
        	LangManager::send("sethome-disallowed", $sender);
        	return false;
        }
		$name = preg_replace("/[^a-z0-9]/", "", (isset($args[0]) ? mb_strtolower($args[0]) : "home"));
    	if (empty($name)) {
    		LangManager::send("alphanumeric", $sender);
    		return true;
		}
		foreach($this->plugin->homes->getAll()[$sender->getName()] as $home) {
			if ($home["name"] === $name) {
				LangManager::send("core-sethome-exists", $sender);
				return true;
		    }
		}
		$homes = $this->plugin->homes->getAll()[$sender->getName()];
		$homes[] = [
	    "name" => $name,
	    "x" => $sender->getFloorX(),
	    "y" => $sender->getFloorY(),
		"z" => $sender->getFloorZ(),
		"world" => $sender->getLevel()->getFolderName()
		];
		$this->plugin->homes->set($sender->getName(), $homes);
		LangManager::send("core-sethome", $sender);
		return true;
	}
}