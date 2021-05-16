<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class SocialChat implements Listener{
	/** @var Core */
	private $plugin;

    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
	}
	
	/**
     * @param PlayerChatEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
	public function onChat(PlayerChatEvent $event) : void{
	    $player = $event->getPlayer();
		$msg = $event->getMessage();
		
		$chatcolor = $this->plugin->chatprefs->get($player->getName());
		if($chatcolor !== false){
			if($chatcolor === "Rainbow"){
				$event->setMessage(LangManager::patternize($msg, LangManager::PATTERN_RAINBOW));
			}else{
				$const = "\\pocketmine\\utils\\TextFormat::" . mb_strtoupper(str_replace(" ", "_", $chatcolor));
				if(defined($const)){
					$event->setMessage(constant($const) . $msg);
				}
			}
		}
	}
	
}