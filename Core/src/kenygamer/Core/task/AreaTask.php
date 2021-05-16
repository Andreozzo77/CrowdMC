<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\entity\Effect;
use pocketmine\level\Level;
use pocketmine\entity\EffectInstance;
use pocketmine\Server;
use LegacyCore\Events\Area;
use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;
use kenygamer\Core\bedwars\BedWarsManager;

final class AreaTask extends Task{
	
	/**
	 * @todo
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$area = Area::getInstance();
		if($area === null){
			return;
		}
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$enableJumpBoost = false; 
			$enableNightVision = false;
			$inShop = false;
			foreach($area->cmd->areas as $anArea){
				if($anArea->contains($player->asVector3(), $player->getLevel()->getFolderName())){
					switch($anArea->getName()){
						case "wildportal":
						    if(!$player->isUnderwater()) break;
						    LangManager::send("teleporting", $player);
						    $player->chat("/wild");
						    break;
						case "homesteadportal":
						    if(!$player->isUnderwater()) break;
						    if($plugin->isVip($player)){
						    	LangManager::send("teleporting", $player);
						    	$player->chat("/warp hotel");
						    }else{
						    	LangManager::send("only-vip", $player);
						    }
						    break;
						case "pvpportal":
						    if(!$player->isUnderwater()) break;
						    LangManager::send("teleporting", $player);
						    $player->chat("/warp warzone");
						    break;
						case "spleefportal":
						    if(!$player->isUnderwater()) break;
						    $plugin->joinQueue($player, Main::DUEL_TYPE_SPLEEF);
						    $plugin->quitQueue($player, Main::DUEL_TYPE_SPLEEF);
						    break;
						case "mazeportal":
						    if(!$player->isUnderwater()) break;
						    LangManager::send("teleporting", $player);
						    $player->chat("/warp maze");
						    break;
						case "minigamesportal":
						    if(!$player->isUnderwater()) break;
						    LangManager::send("teleporting", $player);
						    $player->chat("/warp minigames");
						    break;
						case "bedwarsportal":
						    if(!$player->isUnderwater()) break;
						    $manager = Main2::getBedWarsManager();
						    if($manager !== null){
						    	$manager->dequeuePlayer($player);
						    	$modes = [Main2::BEDWARS_MODE_NORMAL/*, Main2::BEDWARS_MODE_CUSTOM*/];
						    	$manager->enqueuePlayer($player, $mode = $modes[array_rand($modes)]);
						    	$player->sendMessage("bedwars-enqueued", BedWarsManager::getGameModeString($mode));
						    	$player->teleport($player->getLevel()->getSpawnLocation());
						    }
						    break;
						case "shop":
							$inShop = true;
							break;
						case "shopmiddle": //NOPE
						case "hubspawn": //NOPE
						case "jump":
						    $enableJumpBoost = true;
						    break;
						case "hub2": //NOPE
						    $enableNightVision = true;
						    break;
						//Disable fly in all mines and warzone
						case "pvpmineside1":
						case "pvpmineside2":
						case "pvpmineside3":
						case "pvpmineside4":
						case "pvpmineupside":
						case "pvpminedownside":
						case "pvpminearea":
						case "normalmineside1":
						case "normalmineside2":
						case "normalmineside3":
						case "normalmineside4":
						case "normalmineupside":
						case "normalminedownside":
						case "normalminearea":
						case "warzone":
						    if($player->getGamemode() % 1 !== 0){
						    	$player->setFlying(false);
						    	$player->setAllowFlight(false);
						    }
						    break;
					}
				}
			}
			$enableNightVision = $player->getLevel()->getFolderName() === "hub" && !$inShop;
			
			//Gives players in shop middle jump boost
			$hasEffect = $player->hasEffect(Effect::JUMP_BOOST);
			if($enableJumpBoost){
				if(!$hasEffect || Main::EFFECT_SAFE_MAX_DURATION - $player->getEffect(Effect::JUMP_BOOST)->getDuration() < Main::EFFECT_SAFE_MAX_DURATION / 2){
					$player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), Main::EFFECT_SAFE_MAX_DURATION, 10, false));
				}
			}elseif($hasEffect && $player->getEffect(Effect::JUMP_BOOST)->getDuration() > 5000 * 20){
				$player->removeEffect(Effect::JUMP_BOOST);
			}
			//Gives players in prison/hub/minigames night vision
			if(!$enableNightVision){
				$enableNightVision = in_array($player->getLevel()->getFolderName(), ["prison", "minigames"]);
			}
			$hasEffect = $player->hasEffect(Effect::NIGHT_VISION);
			if($enableNightVision){ /*&& ($time = $player->getLevel()->getTime()) >= Level::TIME_NIGHT && $time < Level::TIME_SUNRISE){*/
				if(!$hasEffect || Main::EFFECT_SAFE_MAX_DURATION - $player->getEffect(Effect::NIGHT_VISION)->getDuration() < Main::EFFECT_SAFE_MAX_DURATION / 2){
					$player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), Main::EFFECT_SAFE_MAX_DURATION, 0, false));
				}
			}elseif($hasEffect && $player->getEffect(Effect::NIGHT_VISION)->getDuration() > 5000 * 20){
				$player->removeEffect(Effect::NIGHT_VISION);
			}
		}
	}
	
}