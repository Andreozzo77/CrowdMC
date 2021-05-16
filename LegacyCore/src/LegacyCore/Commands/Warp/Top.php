<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class Top extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to the highest block above you");
        $this->setUsage("/top");
        $this->setPermission("core.command.top");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.top")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if(!in_array($sender->getLevel()->getFolderName(), Main::TP_WORLDS)){
        	LangManager::send("tpa-disallowed", $sender); 
        	return false;
        }
        LangManager::send("teleporting", $sender);
        $sender->teleport(new Vector3($sender->getX(), $sender->getLevel()->getHighestBlockAt($sender->getFloorX(), $sender->getFloorZ()) + 1, $sender->getZ()));
		return true;
	}
}