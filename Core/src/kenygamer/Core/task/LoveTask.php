<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\particle\HeartParticle;
use pocketmine\math\Vector3;
use kenygamer\Core\Main;

class LoveTask extends Task{
	
	/**
	 * Love system. Gives effects and increases the player size.
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach($plugin->getServer()->getOnlinePlayers() as $player){
			$target = $plugin->love->getNested($player->getName() . ".loving", "");
			if($target !== ""){
				if(strpos($player->getDisplayName(), "[<3]") === false){
					$player->setDisplayName(TextFormat::colorize("&5[<3] &f" . $player->getDisplayName()));
				}
				$tr = $player->getServer()->getPlayerExact($target);
				if($tr instanceof Player){
					if(($distance = $tr->distance($player)) <= 16){
						$player->setScale(1.2);
						$dist = 5 - @($distance / 5); //between 1-5: the nearest the couple is, the higher the number
						foreach([$tr, $player] as $p){
							$effects = [
							    Effect::REGENERATION, Effect::STRENGTH,
							    Effect::ABSORPTION, Effect::SPEED
							];
							foreach($effects as $effect){
							    $p->addEffect(new EffectInstance(Effect::getEffect($effect), 40, abs((int) round($dist)), false));
							}
							for($i = 0; $i < 1; ++$i){ //< $dist;
								$vector = new Vector3(
								    $p->x + ((float) ("0." . \kenygamer\Core\Main::mt_rand(0, 2) . \kenygamer\Core\Main::mt_rand(0, 9))),
								    $p->y + $p->height + ((float) ("0." . \kenygamer\Core\Main::mt_rand(0, 2) . \kenygamer\Core\Main::mt_rand(0, 9))),
								    $p->z + ((float) ("0." . \kenygamer\Core\Main::mt_rand(0, 2) . \kenygamer\Core\Main::mt_rand(0, 9)))
								);
								$particle = new HeartParticle($vector, (int) round(1 + (0.3 - @($distance / 0.3))));
								$p->getLevel()->addParticle($particle);
							}
						}
					}else{
					    $player->setScale(1);
					}
				}else{
					$player->setScale(1);
				}
			}		
		}
	}
	
}