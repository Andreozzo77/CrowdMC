<?php

declare(strict_types=1);

namespace kenygamer\Core\entity;

use pocketmine\block\StillWater;
use pocketmine\block\Water;
use pocketmine\block\Liquid;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\Server;

use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener2;

class FishingHook extends Projectile{
	public const NETWORK_ID = self::FISHING_HOOK;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	public $baseTimer = 0;
	public $coughtTimer = 0;
	public $bubbleTimer = 0;
	public $bubbleTicks = 0;
	public $bitesTicks = 0;
	public $attractTimer = 0;
	public $attractTimerTicks = 0;
	public $lightLevelAtHook = 0;
	protected $gravity = 0.1;
	protected $drag = 0.05;
	protected $touchedWater = false;
	
	public function flagForDespawn() : void{
		if(($owner = $this->getOwningEntity()) !== null){
			MiscListener2::unsetFishing($owner);
		}
		parent::flagForDespawn();
	}
	
	/**
	 * @param int $currentTick
	 * @return bool
	 */
	public function onUpdate(int $currentTick): bool{
		if($this->isFlaggedForDespawn() || !$this->isAlive()){
			return false;
		}
		
		$owner = $this->getOwningEntity();
		
		//Remove if Owner is null
		if ($owner === null){
			if(!$this->isFlaggedForDespawn()){
				$this->flagForDespawn();
			}
		}
			
		//Remove if Owner too far
		if($owner instanceof Player){
			if($this->getPosition()->distance($owner->getPosition()) > (25 - (10 - Main::getInstance()->getFishingLevel($owner)) * 2)){
				//Distance is too low
			}
		}
		
		//calculate timer for attractTimer
		$this->lightLevelAtHook = $this->level->getBlockSkyLightAt((int) $this->x, (int) $this->y, (int) $this->z);
		$this->attractTimer = ($this->baseTimer * (((-1 / 15) * $this->lightLevelAtHook) + 2)) - $this->attractTimerTicks;

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
		
		if($this->isInsideOfSolid()){
			$random = new Random((int) (microtime(true) * 1000) + \kenygamer\Core\Main::mt_rand());
			$this->motion->x *= $random->nextFloat() * 0.2;
			$this->motion->y *= $random->nextFloat() * 0.2;
			$this->motion->z *= $random->nextFloat() * 0.2;
		}		
		
		if(!$this->isInsideOfSolid()){
			if($currentTick % 20 === 0){
				var_dump("baseTimer: ".$this->baseTimer." attractTimer: ".$this->attractTimer." attractTimerTicks: ".$this->attractTimerTicks." coughtTimer: ".$this->coughtTimer." bubbleTimer: ".$this->bubbleTimer);
			}
			$f6 = 0.92;

			if($this->onGround or $this->isCollidedHorizontally){
				$f6 = 0.5;
			}
			
			$d10 = 0;
			$bb = $this->getBoundingBox();
			for($j = 0; $j < 5; ++$j){
				$d1 = $bb->minY + ($bb->maxY - $bb->minY) * $j / 5;
				$d3 = $bb->minY + ($bb->maxY - $bb->minY) * ($j + 1) / 5;
				$bb2 = new AxisAlignedBB($bb->minX, $d1, $bb->minZ, $bb->maxX, $d3, $bb->maxZ);
				if($this->isLiquidInBoundingBox($bb2)){
					$d10 += 0.2;
				}
			}

			//if ($d10 > 0){	
			if(true){
				//Little annimation floating
				if ($currentTick % 60 === 0){
					$this->motion->y =-0.02;
				}
				//Wait, we are waiting the fish
				if($this->attractTimer <= 0){
					//Set bubble timer, fish is near
					if ($this->bubbleTimer === 0 && $this->coughtTimer <= 0){
						$this->bubbleTimer = \kenygamer\Core\Main::mt_rand(5, 10) * 20;
					}elseif($this->bubbleTimer > 0){
						$this->bubbleTimer--;
					}
					
					//If bubble timer finished, catch it
					if($this->bubbleTimer <= 0 && $this->coughtTimer <= 0){
						$this->coughtTimer = \kenygamer\Core\Main::mt_rand(3, 5) * 20;
						$this->fishBites();
						$this->bitesTicks = \kenygamer\Core\Main::mt_rand(1, 3) * 20;
					}else{
					//Else do animation every X ticks
						if ($this->bubbleTicks === 0){
							$this->attractFish();
							$this->bubbleTicks = 10;
						}else{
							$this->bubbleTicks--;
						}
						
					}
				}elseif($this->attractTimer > 0){
					$this->attractTimerTicks++;
				}
				
				if($this->coughtTimer > 0){
					$this->coughtTimer--;
					if ($this->bitesTicks === 0){
						$this->fishBites();
						$this->bitesTicks = \kenygamer\Core\Main::mt_rand(1, 3) * 20;
					}else{
						$this->bitesTicks--;
					}
	
					//Too late, fish has gone, reset timer
					if ($this->coughtTimer <= 0){
						$owner->sendMessage("fishing-goneaway");
						$this->baseTimer = \kenygamer\Core\Main::mt_rand(30, 100) * 20;
						$this->attractTimerTicks = 0;
					}
				}
				
			}
			$d11 = $d10 * 2.0 - 1.0;
			
			$this->motion->y += 0.04 * $d11;
			if($d10 > 0.0){
				$f6 = $f6 * 0.9;
				$this->motion->y *= 0.8;
			}
			
			$this->motion->x *= $f6;
			$this->motion->y *= $f6;
			$this->motion->z *= $f6;
		}
		$this->timings->stopTiming();

		return $hasUpdate;
	}

	public function attractFish() : void{
		$owner = $this->getOwningEntity();
		if($owner instanceof Player){
			$this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_BUBBLE);
		}
		$this->level->addParticle(new GenericParticle(new Vector3($this->x, $this->y - 0.1, $this->z), Particle::TYPE_BUBBLE));
	}

	public function fishBites() : void{
		$owner = $this->getOwningEntity();
		if($owner instanceof Player){
			$this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_HOOK);
		}
		$this->motion->y =-0.08;
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		$this->server->getPluginManager()->callEvent(new ProjectileHitEntityEvent($this, $hitResult, $entityHit));

		$damage = $this->getResultDamage();

		if($this->getOwningEntity() === null){
			$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}else{
			$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}

		$entityHit->attack($ev);
		if($this->getOwningEntity() !== null){
			$entityHit->setMotion($this->getOwningEntity()->getDirectionVector()->multiply(-0.3)->add(0, 0.3, 0));
		}

		$this->isCollided = true;
		
		$this->flagForDespawn();
	}
	
	/**
	 * @return int
	 */
	public function getResultDamage() : int{
		return 1;
	}
	
	/**
	 * @param AxisAlignedBB $bb
	 * @param Liquid $material
	 *
	 * @return bool
	 */
	public function isLiquidInBoundingBox(AxisAlignedBB $bb) : bool{
		$minX = (int) floor($bb->minX);
		$minY = (int) floor($bb->minY);
		$minZ = (int) floor($bb->minZ);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		for($x = $minX; $x < $maxX; ++$x){
			for($y = $minY; $y < $maxY; ++$y){
				for($z = $minZ; $z < $maxZ; ++$z){
					$block = $this->level->getBlockAt($x, $y, $z);

					if($block instanceof Liquid){
						$j2 = $block->getDamage();
						$d0 = $y + 1;

						if($j2 < 8){
							$d0 -= $j2 / 8;
						}

						if($d0 >= $bb->minY){
							return true;
						}
					}
				}
			}
		}

		return false;
	}
	
	protected function tryChangeMovement() : void{
	}
	
}