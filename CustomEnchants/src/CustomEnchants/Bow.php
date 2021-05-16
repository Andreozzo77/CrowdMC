<?php

declare(strict_types=1);

namespace CustomEnchants;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\Arrow;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Bow as BowItem;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\Player;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;

class Bow extends BowItem{
	
	public function onReleaseUsing(Player $player) : bool{
		if($player->isSurvival() and !$player->getInventory()->contains(ItemFactory::get(Item::ARROW, 0, 1))){
			$player->getInventory()->sendContents($player);
			return false;
		}

		$nbt = Entity::createBaseNBT(
			$player->add(0, $player->getEyeHeight(), 0),
			@$player->getDirectionVector(),
			($player->yaw > 180 ? 360 : 0) - $player->yaw,
			-$player->pitch
		);
		$nbt->setShort("Fire", $player->isOnFire() ? 45 * 60 : 0);

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$baseForce = min((($p ** 2) + $p * 2) / 3, 1);

        $enchantment = $player->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::COWLAUNCHER);
        $entity = Entity::createEntity($enchantment !== null ? "CowArrow" : "Arrow", $player->getLevel(), $nbt, $player, $baseForce >= 1);
        	
		if($entity instanceof Projectile){
			if($enchantment !== null){
				$entity->explosionSize = $enchantment->getLevel();
			}
			
			$infinity = $this->hasEnchantment(Enchantment::INFINITY);
			if($entity instanceof Arrow){
				if($infinity){
					$entity->setPickupMode(Arrow::PICKUP_CREATIVE);
				}
				if(($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH)) > 0){
					$entity->setPunchKnockback($punchLevel);
				}
			}
			if(($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER)) > 0){
				$entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
			}
			if($this->hasEnchantment(Enchantment::FLAME)){
				$entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
			}
			$ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 3);

			if($baseForce < 0.1 or $diff < 5){
				$ev->setCancelled();
			}

			$ev->call();

			$entity = $ev->getProjectile(); //This might have been changed by plugins

			if($ev->isCancelled()){
				if(!$entity->isClosed()){
					$entity->close();
					//$entity->setPunchKnockback(0);
					//$entity->setOnFire(0);
				}
				$player->getInventory()->sendContents($player);
			}else{
				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
				if($player->isSurvival()){
					if(!$infinity){ //TODO: tipped arrows are still consumed when Infinity is applied
						$player->getInventory()->removeItem(ItemFactory::get(Item::ARROW, 0, 1));
					}
					$this->applyDamage(1);
				}

				if($entity instanceof Projectile){
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					if($projectileEv->isCancelled()){
						if(!$ev->getProjectile()->isClosed()){
							$ev->getProjectile()->flagForDespawn();
						}
					}else{
						$ev->getProjectile()->spawnToAll();
						$player->getLevel()->broadcastLevelSoundEvent($player, $enchantment !== null ? LevelSoundEventPacket::SOUND_HIT : LevelSoundEventPacket::SOUND_BOW);
					}
				}else{
					$entity->spawnToAll();
				}
			}
		}else{
			$entity->spawnToAll();
		}

		return true;
	}
	
}