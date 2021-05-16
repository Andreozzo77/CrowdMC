<?php

declare(strict_types=1);

namespace CustomEnchants;

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\entity\projectile\Arrow;
use pocketmine\level\Explosion;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

use LegacyCore\Events\Area;

class CowArrow extends Arrow{
	public const NETWORK_ID = self::COW;
	
	public $explosionSize = 0.0;
	
	protected function onHit(ProjectileHitEvent $event) : void{
		$this->setCritical(false);
		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_DEATH);
		if(!$this->closed){
			$this->flagForDespawn();
		}
		
		$player = $this->getOwningEntity();
		if($player instanceof Player){
			$cmd = Area::getInstance()->cmd;
			
			$explosion = new Explosion($this->asPosition(), $this->explosionSize);
			$explosion->explodeA();
			$player = $this->getOwningEntity();
			foreach($explosion->affectedBlocks as $i => $block){
				if(!($cmd->canEdit($player, $block))){
					unset($explosion->affectedBlocks[$i]);
				}
			}
			$explosion->explodeB();
		}
	}
}