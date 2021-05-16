<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

/**
 * Class GoeyTask
 * @package CustomEnchants
 */
class GoeyTask extends Task
{
    private $plugin;
    private $entity;
    private $level;

    /**
     * GoeyTask constructor.
     * @param Main $plugin
     * @param Entity $entity
     * @param $level
     */
    public function __construct(Main $plugin, Entity $entity, $level)
    {
        $this->plugin = $plugin;
        $this->entity = $entity;
        $this->level = $level;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        $this->entity->setMotion(new Vector3($this->entity->getMotion()->x, (3 * $this->level * 0.05) + 0.75, $this->entity->getMotion()->z));
    }
}