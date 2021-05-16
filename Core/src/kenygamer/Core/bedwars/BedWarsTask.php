<?php

declare(strict_types=1);

namespace kenygamer\Core\bedwars;

use pocketmine\scheduler\Task;

use pocketmine\utils\Config;
use kenygamer\Core\Main;
use pocketmine\Server;

use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;

class BedWarsTask extends Task{
	
	public function onRun(int $currentTick) : void{
		if($manager = Main2::getBedWarsManager()){
			foreach($manager->getQueue() as $mode => $players){
				$arenas = $manager->getAvailableArenas($mode);
				$leastPlayers = PHP_INT_MAX;
				
				/** @var Player[] $havePlayers */
				$havePlayers = [];
				foreach($players as $p){
					$player = Server::getInstance()->getPlayerExact($p);
					if($player !== null){
						$havePlayers[] = $player;
					}else{
						unset($players[array_search($p, $players)]);
					}
				}
				
				foreach($arenas as $arena){
					if(count($arena->getSpawns()) < $leastPlayers){
						$leastPlayers = count($arena->getSpawns());
					}
					if($leastPlayers <= count($havePlayers)){
						foreach($havePlayers as $player){
							$manager->dequeuePlayer($player);
							$arena->addPlayer($player);
						}
						break 2;
					}
				}
				if($leastPlayers < PHP_INT_MAX){
					foreach($havePlayers as $player){
						$player->sendPopup(LangManager::translate("bedwars-tip-queue", count($havePlayers), $leastPlayers));
					}
				}else{
					foreach($havePlayers as $player){
						$player->sendPopup(LangManager::translate("bedwars-tip-unavailable"));
					}
				}
			}
			
			foreach($manager->getArenas() as $arena){
				$arena->tickGame();
			}
		}
	}
	
}