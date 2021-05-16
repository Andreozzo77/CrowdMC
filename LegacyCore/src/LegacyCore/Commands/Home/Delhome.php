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

class Delhome extends PluginCommand{

	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Delete your home");
        $this->setUsage("/delhome <name>");
		$this->setPermission("core.command.delhome");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.delhome")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-delhome-usage", $sender);
            return false;
        }
    	$name = preg_replace("/[^a-z0-9]/", "", (isset($args[0]) ? mb_strtolower($args[0]) : "home"));
    	foreach($this->plugin->homes->getAll()[$sender->getName()] as $key => $home) {
    		if ($home["name"] === $name) {
    			$homes = $this->plugin->homes->getAll()[$sender->getName()];
    			unset($homes[$key]);
    			$this->plugin->homes->set($sender->getName(), $homes);
    			LangManager::send("core-delhome", $sender);
    			return true;
			}
		}
    	LangManager::send("core-home-notfound", $sender, $name);
		return true;
	}
}