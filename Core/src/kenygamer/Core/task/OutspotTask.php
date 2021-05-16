<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use kenygamer\Core\task\OutlineAreaTask;
use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class OutspotTask extends Task{
	/** @var int[] */
	private $capturing = [];
	/** @var string[] */
	private $outspots = [];
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$level = Server::getInstance()->getLevelByName("warzone");
		$plugin = Main::getInstance();
		if(empty($this->outspots)){
			$spawn = $level->getSpawnLocation();
			for($x = -16; $x < 16; $x += 16){
				for($z = -16; $z < 16; $z += 16){
					$this->outspots[] = [$chunkX = (($spawn->getX() + $x) >> 4), $chunkZ = (($spawn->getZ() + $z) >> 4)];
					/*$bb = $plugin->createBB(new Vector3($chunkX << 4, 0, $chunkX << 4), new Vector3(($chunkX << 4) + 16, Level::Y_MAX, ($chunkZ << 4) + 16));
					$plugin->getScheduler()->scheduleRepeatingTask(new OutlineAreaTask($bb, $level, PHP_INT_MAX), 20);*/
				}
			}
		}else{
			$popups = [];
			$fp = $plugin->getPlugin("FactionsPro");
			foreach($this->outspots as $outspot){
				list($chunkX, $chunkZ) = $outspot;
				foreach($level->getPlayers() as $player){
					
					if(isset($this->capturing[$chunkX . ":" . $chunkZ])){
						list($faction, $points) = $this->capturing[$chunkX . ":" . $chunkZ];
						$popup[$player->getName()] = LangManager::translate("outspot-popup", $player, $faction, $points);
					}
					
					if(!$fp->isInFaction($player->getName())){
						continue;
					}
					$faction = $fp->getPlayerFaction($player->getName());
					if($player->getX() >> 4 === $chunkX && $player->getZ() >> 4 === $chunkZ){
						if(!isset($this->capturing[$chunkX . ":" . $chunkZ])){
							$this->capturing[$chunkX . ":" . $chunkZ] = [$faction, 0];
						}else{
							list($who, $time) = $this->capturing[$chunkX . ":" . $chunkZ];
							$whoObj = $plugin->getServer()->getPlayerExact($who);
							if($whoObj === null || $whoObj->getX() >> 4 !== $chunkX || $whoObj->getZ() >> 4 !== $chunkZ){
								$this->capturing[$chunkX . ":" . $chunkZ] = [$faction, 0];
							}
						}
					}
				}
			}
			
			foreach($this->capturing as $chunk => $data){
				$chunkX = (int) $chunk[0];
				$chunkZ = (int) $chunk[1];
				$faction = $data[0];
				/** @var string $players Online players from $faction standing in this outspot. */
				$players = [];
				foreach($plugin->getServer()->getOnlinePlayers() as $player){
					$playerFaction = $fp->getPlayerFaction($player->getName());
					if($playerFaction === $faction && $chunkX === $player->getX() >> 4 && $chunkZ === $player->getZ() >> 4){
						$players[] = $player;
					}
				}
				if(\count($players) < 0){
					$this->capturing[$chunk][1] -= $fp->getPlayersInFaction($faction);
					if($this->capturing[$chunk][1] < 0){
						unset($this->capturing[$chunk]);
					}
					continue;
				}
				$beforePoints = $this->capturing[$chunk][1];
				
				$otherFactions = [];
				foreach($level->getPlayers() as $player){
					if($fp->isInFaction($player->getName()) && ($otherFaction = $fp->getPlayerFaction($player)) !== $faction && !\in_array($faction, $otherFactions)){
						$otherFactions[] = $otherFaction;
					}
				}
				
				if(!empty($otherFactions)){
					
					$this->capturing[$chunk][1] += \count($players);
					$afterPoints = $this->capturing[$chunk][1];
					if($beforePoints < 15 && $afterPoints >= 15){
						LangManager::broadcast("outspot-control", $faction);
					}
					if($afterPoints % 30 === 0 && $afterPoints > 0){
						\shuffle($otherFactions);
						$otherFaction = array_shift($otherFactions);
						$power = $fp->getFactionPower($otherFaction, true) * 2 / 100;
						$fp->transferPower($otherFaction, $faction, $power);
						if($power > 1){
							foreach($level->getPlayers() as $player){
								$player->sendMessage("outspot-steal", $faction, \number_format($power), $otherFaction);
							}
						}
					}
				}else{
					foreach($players as $player){
						//Override
						$popup[$player->getName()] = LangManager::translate("outspot-popup-needfactions", $player, $faction);
					}
				}
			}
			
			foreach($popups as $player => $popup){
				$playerObj = Server::getInstance()->getPlayerExact($player);
				$playerObj->sendPopup($popup);
			}
			
		}
	}

}