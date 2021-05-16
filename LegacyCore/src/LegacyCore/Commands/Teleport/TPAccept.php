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
use kenygamer\Core\Main;

class TPAccept extends PluginCommand{

	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Accept a teleport request");
        $this->setUsage("/tpaccept");
		$this->setPermission("core.command.tpaccept");
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
    	$cmd = "tpaccept";
    	if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if ($cmd === "tpaccept") {
			foreach($this->plugin->tpa as $key => $request) {
				$source = $request["source"];
				$target = $request["target"];
				if ($target === $sender->getName()) {
					$send = $this->plugin->getServer()->getPlayerExact($source);
					if($send === null){
						LangManager::translate("player-notfound", $sender);
						return true;
					}
					$rece = $this->plugin->getServer()->getPlayerExact($target);
					if ($target === $sender->getName()) {
						if (time() > $request["expire"]) {
							unset($this->plugin->tpa[$key]);
							LangManager::send("timedout", $sender);
							return true;
						}
						if ($send instanceof Player && $rece instanceof Player){
							LangManager::send("core-tpaccept-requester", $send, $rece->getName());
							if ($request["tpahere"]) {
								if(!in_array($sender->getLevel()->getFolderName(), Main::TP_WORLDS)){
									LangManager::send("tpa-disallowed", $sender);
									LangManager::send("timedout", $send);
									unset($this->plugin->tpa[$key]);
									break;
								}
								$rece->teleport($send);
							} else {
								if(!in_array($send->getLevel()->getFolderName(), Main::TP_WORLDS)){
									LangManager::send("tpa-disallowed-target", $send);
									LangManager::send("timedout", $sender);
									unset($this->plugin->tpa[$key]);
									break;
								}
								$send->teleport($rece);
			    			}
			    			LangManager::send("teleporting", $sender);
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