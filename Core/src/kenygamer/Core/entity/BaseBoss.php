<?php

namespace kenygamer\Core\entity;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class BaseBoss extends Human{
	
	public $jumpTicks = 120;

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this === null) return false;
		parent::entityBaseTick($tickDiff);
		if($this->jumpTicks > 0) {
			$this->jumpTicks--;
		}
		return true;
	}
	
	public function broadcastMovement(bool $teleport = false) : void{
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->getOffsetPosition($this->getPosition());
        $pk->pitch = $this->getPitch();
        $pk->headYaw = $this->getYaw();
        $pk->yaw = $this->getYaw();
        $pk->mode = MovePlayerPacket::MODE_NORMAL;

        $this->getLevel()->broadcastPacketToViewers($this->getPosition(), $pk);
    }
	
	public function attackEntity(Entity $entity) : bool{
		if($this === null) return false;
		if(!$entity->isAlive()) {
			return false;
		}
		$heldItem = $this->inventory->getItemInHand();
		$event = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints() + \kenygamer\Core\Main::mt_rand(10, 30));
		if($entity instanceof Player and !$this->server->getConfigBool("pvp")) {
			$event->setCancelled();
		}
		$meleeEnchantmentDamage = \kenygamer\Core\Main::mt_rand(2, 4);
		/** @var EnchantmentInstance[] $meleeEnchantments */
		$meleeEnchantments = [];
		foreach($heldItem->getEnchantments() as $enchantment) {
			$type = $enchantment->getType();
			if($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($entity)) {
				$meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
				$meleeEnchantments[] = $enchantment;
			}
		}
		$event->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);
		if(!$this->isSprinting() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isUnderwater()) {
			$event->setModifier($event->getFinalDamage() / 4, EntityDamageEvent::MODIFIER_CRITICAL);
		}
		$entity->attack($event);
		if($event->isCancelled()) {
			if($heldItem instanceof Durable) {
				//$this->inventory->sendContents($this);
			}
			return false;
		}
		if($event->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
			
		}
		foreach($meleeEnchantments as $enchantment) {
			$type = $enchantment->getType();
			assert($type instanceof MeleeWeaponEnchantment);
			$type->onPostAttack($this, $entity, $enchantment->getLevel());
		}
		if($this->isAlive()) {
			// reactive damage like thorns might cause us to be killed by attacking another mob, which
			// would mean we'd already have dropped the inventory by the time we reached here
			if($heldItem->onAttackEntity($entity)) { // always fire the hook, even if we are survival
				$this->inventory->setItemInHand($heldItem);
			}
		}
		return true;
	}
	
	public function setTheLevel(Level $level) : void{
		$this->level = $level;
	}
	
	public function jump() : void{
		if($this->jumpTicks === 0){
			$this->motion->y = $this->getJumpVelocity() * 1.7;
			$this->jumpTicks = 120;
		}
	}
}