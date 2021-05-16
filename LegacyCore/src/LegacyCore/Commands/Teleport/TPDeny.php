<?php

namespace LegacyCore\Commands\Teleport;

use LegacyCore\Core;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class TPDeny extends PluginCommand{

	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Deny a teleport request");
        $this->setUsage("/tpdeny");
		$this->setPermission("core.command.tpdeny");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	$cmd = "tpdeny";
    	if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if ($cmd === "tpdeny") {
			foreach($this->plugin->tpa as $key => $request) {
				$source = $request["source"];
				$target = $request["target"];
				if ($target === $sender->getName()) {
					$send = $this->plugin->getServer()->getPlayerExact($source);
					$rece = $this->plugin->getServer()->getPlayerExact($target);
					if ($target === $sender->getName()) {
						if (time() > $request["expire"]) {
							unset($this->plugin->tpa[$key]);
							LangManager::send("timedout", $sender);
							return true;
						}
						if ($send instanceof Player && $rece instanceof Player) {
							LangManager::send("core-tpdeny", $sender);
							LangManager::send("core-tpdeny-requester", $send, $rece->getName());
			    			unset($this->plugin->tpa[$key]);
			    		} else {
			    			LangManager::translate("player-notfound", $sender);
			    		}
			    		return true;
			    	}
			    }
			}
			LangManager::send("core-tpa-none", $sender);
		}
		return true;
	}
	
}