<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use kenygamer\Core\entity\FishingHook;
use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\util\FishingLootTable;
use kenygamer\Core\Main;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Tool;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;


class FishingRod extends Tool{
	
	public function __construct($meta = 0){
		parent::__construct(Item::FISHING_ROD, $meta, "Fishing Rod");
	}
	
	/**
	 * @return int
	 */
	public function getMaxStackSize() : int{
		return 1;
	}
	
	/**
	 * @return int
	 */
	public function getMaxDurability(): int{
		return 355;
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
		if(isset(MiscListener2::$fishing[$player->getName()])){
			$playerFishingLevel = Main::getInstance()->getFishingLevel($player);
			if(!MiscListener2::$fishing[$player->getName()][0]){
				//Cannot fish at night when under level 3
				$time = $player->getLevel()->getTimeOfDay();
				if ((($time < Level::TIME_SUNSET || $time > Level::TIME_SUNRISE) && $playerFishingLevel <= 3) || $playerFishingLevel > 3){
					var_dump("close to making proj");
					$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);

					/** @var FishingHook $projectile */
					$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);
					if($projectile !== null){
						//Level impact ThrowForce
						$throwForce = $this->getThrowForce();
						
						$throwForcePercent = $playerFishingLevel* 6 - 36;
						$throwForceToAdd = ($throwForce / 100) * $throwForcePercent;
						$throwForce = $throwForce + $throwForceToAdd;
						$projectile->setMotion($projectile->getMotion()->multiply($throwForce));
						var_dump("project not nulllllll");
					
						//Change the location where sending hook projectile
	
						$degreeToRand = 35 / $playerFishingLevel;
						$randomRotation = floor(rand() / getrandmax() * ($degreeToRand * 2 - $degreeToRand));
						$randomRotationRadian = $randomRotation * (M_PI /180);
						$hookMotion = $projectile->getMotion();
						$theta = deg2rad($randomRotation);
						$cos = cos($theta);
						$sin = sin($theta);
						$px = $hookMotion->x * $cos - $hookMotion->z * $sin; 
						$pz = $hookMotion->x * $sin + $hookMotion->z * $cos;
						$projectile->setMotion(new Vector3($px, $hookMotion->y, $pz));
	
						$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
						if($projectileEv->isCancelled()){
							$projectile->flagForDespawn();
						}else{
							$projectile->spawnToAll();
							var_dump("spawned shit");
							$player->getLevel()->addSound(new LaunchSound($player), $player->getViewers());
						}
	
						$rand = \kenygamer\Core\Main::mt_rand(30, 100); //Rainy/Rainy Thunder weather: 15-50
						if($this->hasEnchantments()){
							foreach($this->getEnchantments() as $enchantment){
								switch($enchantment->getId()){
									case Enchantment::LURE:
										$divisor = $enchantment->getLevel() * 0.50;
										$rand = intval(round($rand / $divisor)) + 3;
										break;
								}
							}
						}
						$projectile->baseTimer = $rand * 20;
	
						MiscListener2::$fishing[$player->getName()] = [true, $projectile];
					}
					
				}else{
					$player->sendMessage("fishing-lvltoolow");
				}
				
			}else{
				$projectile = MiscListener2::$fishing[$player->getName()][1];
				if($projectile instanceof FishingHook){
					MiscListener2::unsetFishing($player);

					if($player->getLevel()->getBlock($projectile->asVector3())->getId() == Block::WATER || $player->getLevel()->getBlock($projectile)->getId() == Block::WATER){
						$damage = 5;
					}else{
						$damage = \kenygamer\Core\Main::mt_rand(10, 15);
					}

					$this->applyDamage($damage);

					if($projectile->coughtTimer > 0){
						$lvl = 0;
						if($this->hasEnchantments()){
							if($this->hasEnchantment(Enchantment::LUCK_OF_THE_SEA)){
								$lvl = $this->getEnchantment(Enchantment::LUCK_OF_THE_SEA)->getLevel();
							}
						}
						//Level of player impact chance to catch something
						//Level of Enchant LUCK_OF_THE_SEA impact chance too
						 if(\kenygamer\Core\Main::mt_rand($playerFishingLevel, intval(round(11+ $lvl + sqrt($playerFishingLevel +2) * 2))) <= round(2 + $lvl + sqrt($playerFishingLevel) * 4.4)){
							$item = FishingLootTable::getRandom($lvl);
							if($item->getId() === 349 OR $item->getId() === 460){
								$size = round(5 * $playerFishingLevel * (($lvl +2 ) / 3) * (((-1 / 15) *$projectile->lightLevelAtHook) + 2));
								$item->setCustomBlockData(new CompoundTag("", [
									new StringTag("FishSize", strval($size))
								]))->setLore(["Fish size: " . $size . " cm"]);
							}
							$player->getInventory()->addItem($item);
							Main::getInstance()->addFishingExp(\kenygamer\Core\Main::mt_rand(3, 6), $player);
							$player->addXp(\kenygamer\Core\Main::mt_rand(2, 4), false);
						}else{
							Main::getInstance()->addFishingExp(\kenygamer\Core\Main::mt_rand(1, 3), $player);
							$player->addXp(\kenygamer\Core\Main::mt_rand(1, 2),false);
							$player->knockBack($projectile, 0, $player->x - $projectile->x, $player->z - $projectile->z, (0.3/$playerFishingLevel));
							$player->sendMessage("fishing-goneaway");
						}
						

					}
				}
			}
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getProjectileEntityType() : string{
		return "FishingHook";
	}

	/**
	 * @return float
	 */
	public	function getThrowForce() : float{
		return 1.6;
	}
	
}
