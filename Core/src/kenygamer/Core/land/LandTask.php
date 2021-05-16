<?php

declare(strict_types=1);

namespace kenygamer\Core\land;

use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class LandTask extends Task{
	private const A_WEEK = 60 * 60 * 24 * 7;
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach($plugin->landManager->getAll() as $land){
			if($land->owner !== "" && time() - $land->lastPayment >= self::A_WEEK && time() - $land->lastPayment - self::A_WEEK >= Main::getInstance()->getConfig()->getNested("land.due-deadline")){
				LangManager::broadcast("lands-expired", $land->id, $land->owner);
				$land->owner = "";
			}
			if($land->sign instanceof Vector3){
				if($plugin->getServer()->isLevelLoaded($land->world)){
					$sign = $plugin->getServer()->getLevelByName($land->world)->getTile($land->sign);
					if($sign instanceof Sign && !$sign->isClosed()){
						$sign->setLine(0, TextFormat::GRAY . "Land #" . $land->id);
						if($land->isOwned()){
							$sign->setLine(1, TextFormat::GOLD . "Owned");
							$sign->setLine(2, TextFormat::AQUA . $land->owner);
							$sign->setLine(3, TextFormat::AQUA . "Helpers: " . count($land->helpers));
						}else{
							$sign->setLine(1, TextFormat::RED . $land->getSize() . "m^2");
							$sign->setLine(2, TextFormat::YELLOW . "\$" . $land->price);
							$sign->setLine(3, TextFormat::AQUA . "Rent a week");
						}
					}
				}
			}
			foreach($plugin->getServer()->getOnlinePlayers() as $player){
				if($land->contains($player)){
					if(in_array(mb_strtolower($player->getName()), $land->denied)){
						$popup = LangManager::translate("land-popup-denied", $player);
						$newPos = $player->asPosition();
						$back = $player->getDirectionVector()->multiply(-1);
						while($land->contains(Position::fromObject($back, $player->getLevel()), true)){
							$newPos->x += $back->x;
							$newPos->z += $back->z;
						}
						$player->teleport($player->getLevel()->getSafeSpawn($newPos));
					}else{
						if($land->isOwned()){
							$popup = LangManager::translate("land-popup-owned", $player, $land->id, $land->owner);
						}else{
							$popup = LangManager::translate("land-popup-unowned", $player, $land->id, number_format($land->price));
						}
					}
					$player->sendPopup($popup);
				}
			}
		}
	}
	
}