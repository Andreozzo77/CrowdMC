<?php

/**
 * @name Transfer
 * @main kenygamer\Transfer\Main
 * @api 3.9.7
 * @version 1.0.0-stable
 * @author kenygamer
 * @description Transfers you to the given server on join
 */

declare(strict_types=1);

namespace kenygamer\Transfer;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class Main extends PluginBase implements Listener{
	public const TRANSFER_IP = "mcpe.life";
	public const TRANSFER_PORT = 19132;
	
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->transfer(self::TRANSFER_IP, self::TRANSFER_PORT);
	}
	
}