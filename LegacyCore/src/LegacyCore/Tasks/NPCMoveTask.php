<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use kenygamer\Core\entity\Bandit;
use kenygamer\Core\entity\Goblin;
use LegacyCore\Entities\knight;
use kenygamer\Core\entity\Vampire;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\block\Solid;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\scheduler\Task;

use LegacyCore\Events\NPCEvents;

/**
 * @package LegacyCore\Tasks
 */
class NPCMoveTask extends Task{

	/** @var Core $plugin */
	private $plugin;
	/** @var Entity $entity */
	private $entity;

	public function __construct(Core $plugin, Entity $entity, Level $level) {
		$this->plugin = $plugin;
		$this->entity = $entity;
		$this->level = $level;
	}

	/**
     * @param $currentTick
     */
	public function onRun(int $currentTick) : void{
		if (!$this->entity->isAlive()) {
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
			return;
		}
		$entity = $this->entity;
		$entity->setTheLevel($this->level);
		if (!$this->entity->isAlive() or $entity->getLevel() === null) {
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
			return;
		}
		$direc = $entity->getDirectionVector();
		$direc->y = 0;
		if (!$entity->onGround) return;
		
		$entity->setMotion($direc);
		$entity->jump();
		$distance = 8;
			foreach($entity->getLevel()->getNearByEntities($entity->getBoundingBox()->expandedCopy($distance, $distance, $distance), $entity) as $bot) {
			if ($bot instanceof Player) {
				$v = new Vector2($entity->x, $entity->z);
				$dist = $v->distance($bot->x, $bot->z);
				if ($dist < 3) return;
			}
		}
	}
	
	public function onCancel() : void{
		unset(NPCEvents::getInstance()->tasks[$this->entity->getId()]);
	}

}