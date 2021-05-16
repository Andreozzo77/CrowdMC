<?php

namespace kenygamer\Core\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Vehicle as PMVehicle;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;

use kenygamer\Core\util\MathUtils;
use kenygamer\Core\listener\MiscListener2;

abstract class Vehicle extends PMVehicle{

	/** @var Entity */
	protected $linkedEntity = null;
	/** @var bool */
	protected $canInteract;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
	}

	public function getRollingAmplitude(): int{
		return $this->propertyManager->getInt(self::DATA_HURT_TIME);
	}

	public function setRollingAmplitude(int $time){
		$this->propertyManager->setInt(self::DATA_HURT_TIME, $time);
	}

	public function getDamage(): int{
		// This tag should be DATA_DAMAGE_TAKEN but okay?
		return $this->propertyManager->getInt(self::DATA_HEALTH);
	}

	public function getRollingDirection(): int{
		return $this->propertyManager->getInt(self::DATA_HURT_DIRECTION);
	}

	public function setRollingDirection(int $direction){
		$this->propertyManager->setInt(self::DATA_HURT_DIRECTION, $direction);
	}

	public function setDamage(int $damage){
		if($damage > 40 || $damage < -20){
			$damage = 40;
		}
		$this->propertyManager->setInt(self::DATA_HEALTH, $damage);
	}

	public function getInteractButtonText(): string{
		return "Mount";
	}

	public function getLinkedEntity(): ?Entity{
		return $this->linkedEntity;
	}

	public function canDoInteraction(){
		return $this->linkedEntity == null && $this->canInteract;
	}

	public function initEntity(): void{
		parent::initEntity();

		$this->setRollingAmplitude(0);
		$this->setDamage(0);
		$this->setRollingDirection(0);

		$this->y += $this->baseOffset;
	}

	public function attack(EntityDamageEvent $source): void{
		$damage = null;
		$instantKill = false;
		if($source instanceof EntityDamageByEntityEvent){
			$damage = $source->getDamager();
			$instantKill = $damage instanceof Player && $damage->isCreative();
		}

		if(!$instantKill) $this->performHurtAnimation(rand(4, 8)); // Random is fun

		if($instantKill || $this->getDamage() <= 0){
			if($this->linkedEntity != null){
				$this->mountEntity($this->linkedEntity);
			}

			if($instantKill){
				$this->kill();
			}else{
				$this->close();
			}
		}else{
			if($damage !== null && $damage instanceof Player){
				$this->mountEntity($damage);
				$this->setDamage(0);
			}
		}
	}

	/**
	 * Mount or Dismounts an Entity from a vehicle
	 *
	 * @param Entity $entity The target Entity
	 * @return boolean {@code true} if the mounting successful
	 */
	public function mountEntity(Entity $entity): bool{
		if(is_null($entity)){
			$this->server->getInstance()->getLogger()->error("The target of the mounting entity can't be null or must be player");

			return false;
		}

		$riding = new EntityLink();
		// At least it will work... This ain't java
		if(isset(MiscListener2::$riding[$entity->getName()])){
			// TODO: an event for the interaction

			$pk = new SetActorLinkPacket();
			$riding->fromEntityUniqueId = $this->getId(); //Weird Weird Weird
			$riding->toEntityUniqueId = $entity->getId();
			$riding->type = EntityLink::TYPE_REMOVE;
			$pk->link = $riding;
			$this->server->broadcastPacket($this->hasSpawned, $pk);

			// Second packet, need to be send to player
			if($entity instanceof Player){
				$pk = new SetActorLinkPacket();
				$riding->fromEntityUniqueId = $this->getId(); //Weird Weird Weird
				$riding->toEntityUniqueId = $entity->getId();
				$riding->type = EntityLink::TYPE_REMOVE;
				$pk->link = $riding;
				$entity->dataPacket($pk);
			}

			unset(MiscListener2::$riding[$entity->getName()]);
			$this->linkedEntity = null;
			$entity->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

			return true;
		}
		
		$this->propertyManager->setVector3(self::DATA_RIDER_SEAT_POSITION, new Vector3(0, $this->baseOffset * 2, 0));

		$pk = new SetActorLinkPacket();
		$riding->fromEntityUniqueId = $this->getId();
		$riding->toEntityUniqueId = $entity->getId();
		$riding->type = EntityLink::TYPE_PASSENGER;
		$pk->link = $riding;
		$this->server->broadcastPacket($this->hasSpawned, $pk);
		
		// Send the other packet to the player
		if($entity instanceof Player){
			$pk = new SetActorLinkPacket();
			$riding->fromEntityUniqueId = $this->getId();
			$riding->toEntityUniqueId = 0;
			$riding->type = EntityLink::TYPE_PASSENGER;
			$pk->link = $riding;
			$entity->dataPacket($pk);
		}

		MiscListener2::$riding[$entity->getName()] = $this;
		$this->linkedEntity = $entity;
		$entity->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);
		var_dump($this->baseOffset . " is base offset");

		return true;
	}

	public function onUpdate(int $currentTick): bool {
		$hasUpdated = parent::onUpdate($currentTick);

		if($this->isFlaggedForDespawn() || !$this->isAlive()){
			return false;
		}

		// The rolling amplitude
		if($this->getRollingAmplitude() > 0){
			$this->setRollingAmplitude($this->getRollingAmplitude() - 1);
			$hasUpdated = true;
		}

		// The damage token
		// Now mojang just fudge this up by reversing this
		if($this->getDamage() >= -10 && $this->getDamage() <= 40){
			$this->setDamage($this->getDamage() + 1);
			$hasUpdated = true;
		}

		return $hasUpdated;
	}

	protected $rollingDirection = true;

	protected function performHurtAnimation(float $damage){
		// Vehicle does not respond hurt animation on packets
		// It only respond on vehicle data flags. Such as these
		$this->setRollingAmplitude(10);
		$this->setRollingDirection($this->rollingDirection ? 1 : -1);
		$this->rollingDirection = !$this->rollingDirection;
		$this->setDamage($this->getDamage() - $damage);

		return true;
	}

	public function applyEntityCollision(Entity $to){
		if((!isset($to->riding) || $to->riding != $this) && (!isset($to->linkedEntity) || $to->linkedEntity != $this)){
			$dx = $this->x - $to->x;
			$dy = $this->z - $to->z;
			$dz = MathUtils::getDirection($dx, $dy);

			if($dz >= 0.01){
				$dz = sqrt($dz);
				$dx /= $dz;
				$dy /= $dz;
				$d3 = 1 / $dz;

				if($d3 > 1){
					$d3 = 1;
				}

				$dx *= $d3;
				$dy *= $d3;
				$dx *= 0.05;
				$dy *= 0.05;
				if(!isset($to->riding) || $to->riding != null){
					$this->motion->x -= $dx;
					$this->motion->z -= $dz;
					//var_dump($dx . ":" . $dz);
				}
			}
		}
	}
	
}