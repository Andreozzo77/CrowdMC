<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

/**
 * Class GuardianTask
 * @package CustomEnchants\Tasks
 */
class GuardianTask extends Task
{
    private $plugin;
    private $entity;

    /**
     * GuardianTask constructor.
     * @param Main $plugin
     * @param Entity $entity
     */
    public function __construct(Main $plugin, Entity $entity)
    {
        $this->plugin = $plugin;
        $this->entity = $entity;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        $entity = $this->entity;
        $pk = new LevelEventPacket();
        $pk->evid = LevelEventPacket::EVENT_GUARDIAN_CURSE;
        $pk->data = 1;
        $pk->position = $entity->asVector3();
        $entity->dataPacket($pk);
    }
}