<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use kenygamer\Core\entity\Bandit;
use kenygamer\Core\entity\Goblin;
use kenygamer\Core\entity\Knight;
use kenygamer\Core\entity\Vampire;

use pocketmine\Player;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\level\Level;

/**
 * @package LegacyCore\Tasks
 */
class NPCAttackTask extends Task{

	/** @var Core $plugin */
	public $plugin;

	public function __construct(Core $plugin) {
		$this->plugin = $plugin;
	}

	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
			$level = $player->getLevel();
			$boundingbox = $player->getBoundingBox();
			$distance = 8;
			if ($level === null) return;
			foreach($level->getNearByEntities($boundingbox->expandedCopy($distance, $distance, $distance), $player) as $entity) {
				if ($entity instanceof Player) continue;
				$xdiff = $player->x - $entity->x;
				$ydiff = $player->y - $entity->y;
				$zdiff = $player->z - $entity->z;
				$angle = atan2($zdiff, $xdiff);
				$yaw = (($angle * 180) / M_PI) - 90;
				$v = new Vector2($entity->x, $entity->z);
				$dist = $v->distance($player->x, $player->z);
				$angle = atan2($dist, $ydiff);
				$pitch = (($angle * 180) / M_PI) - 90;
				// Enemy NPC
				if ($entity instanceof Bandit || $entity instanceof Goblin || $entity instanceof Knight || $entity instanceof Vampire) {
					$entity->setRotation($yaw, $pitch);
					$entity->getInventory()->setHeldItemIndex(0);
					if ($dist <= 3.6) {
						if (\kenygamer\Core\Main::mt_rand(1, 10) < 4) {
						    $packet = new AnimatePacket();
							$packet->entityRuntimeId = $entity->getId();
							$packet->action = AnimatePacket::ACTION_SWING_ARM;
							$player->dataPacket($packet);
							$entity->attackEntity($player);
							//$player->attack(new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 1));
						}
					}
				}
			}
		}
	}
}