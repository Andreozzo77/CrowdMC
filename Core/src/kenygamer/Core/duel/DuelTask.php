<?php

declare(strict_types=1);

namespace kenygamer\Core\duel;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

/**
 * Ticks arenas, moves players from the duel queue to a free arena
 *
 * @claas DuelTask
 */
final class DuelTask extends Task{
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$takenArenas = [];
		foreach($plugin->duelQueue as $duelType => $pls){
			$players = [];
			foreach($pls as $p){
				$pl = $plugin->getServer()->getPlayerExact($p);
				if(!$pl instanceof Player){
					unset($plugin->duelQueue[$duelType][array_search($p, $plugin->duelQueue[$duelType])]);
				}else{
					$players[] = $pl;
				}
			}
			
			$arena = $plugin->findFreeArena($duelType);
			if(!($arena instanceof DuelArena || ($arena !== null ? (in_array($arena->getName(), $takenArenas)) : false))){
				foreach($players as $player){
					$player->sendPopup(TextFormat::colorize("\n\n\n" . LangManager::translate("duel-queue-arena", $player)));
				}
				continue;
			}
			
			if(count($players) >= 2){
				$takenArenas[] = $arena->getName();
				
				$player1 = array_shift($plugin->duelQueue[$duelType]);
				$player1 = $plugin->getServer()->getPlayerExact($player1);
			    $player2 = array_shift($plugin->duelQueue[$duelType]);
			    $player2 = $plugin->getServer()->getPlayerExact($player2);
			    
			    LangManager::send("duel-started-player", $player1, $player2->getName());
			    LangManager::send("duel-started-player", $player2, $player1->getName());
			    $duelName = $plugin->getDuelName($duelType);
			    LangManager::broadcast("duel-started", $duelName, $player1->getName(), $player2->getName());
				
				switch($duelType){
					case Main::DUEL_TYPE_VANILLA:
					case Main::DUEL_TYPE_CUSTOM:
					case Main::DUEL_TYPE_SPLEEF:
					case Main::DUEL_TYPE_TNTRUN:
					    $arena->startDuel($plugin->duelKits[$duelType], $duelType, true, $player1, $player2);
					    break;
					default:
					    $arena->startDuel([], $duelType, false, $player1, $player2);
				}
			}elseif(count($players) === 1){
				$players[0]->sendPopup(LangManager::translate("duel-queue-opponent", $players[0]));
			}
		}
		
		foreach($plugin->duelArenas as $arena){
			$arena->tickArena();
		}
	}

}