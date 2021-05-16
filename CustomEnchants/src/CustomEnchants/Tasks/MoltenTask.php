<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\Player;

/**
 * Class MoltenTask
 * @package CustomEnchants\Tasks
 */
class MoltenTask extends Task
{
    private $plugin;
    private $entity;
    private $level;

    /**
     * MoltenTask constructor.
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
    	if($this->entity instanceof Player && $this->entity->isOnline()){
    		$this->entity->setOnFire(3 * $this->level);
    	}
    }
}