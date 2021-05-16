<?php

namespace CustomEnchants\Tasks;


use CustomEnchants\Main;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

/**
 * Class PlaceTask
 * @package CustomEnchants\Tasks
 */
class PlaceTask extends Task
{
    private $plugin;
    private $position;
    private $level;
    private $seed;
    private $player;

    /**
     * PlaceTask constructor.
     * @param Main $plugin
     * @param Vector3 $position
     * @param Level $level
     * @param Item $seed
     * @param Player $player
     */
    public function __construct(Main $plugin, Vector3 $position, Level $level, Item $seed, Player $player)
    {
        $this->plugin = $plugin;
        $this->position = $position;
        $this->level = $level;
        $this->seed = $seed;
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        $this->level->useItemOn($this->position, $this->seed, 1, $this->position, $this->player);
        $this->player->getInventory()->removeItem(Item::get($this->seed->getId(), 0, 1));
    }
}