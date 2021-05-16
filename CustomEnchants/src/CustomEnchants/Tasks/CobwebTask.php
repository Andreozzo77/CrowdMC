<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

/**
 * Class CobwebTask
 * @package CustomEnchants
 */
class CobwebTask extends Task
{
    private $plugin;
    private $player;
    private $position;
    private $time = 0;

    /**
     * CobwebTask constructor.
     * @param Main $plugin
     * @param Player $player
     * @param Position $position
     */
    public function __construct(Main $plugin, Player $player, Position $position)
    {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->position = $position;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        $position = $this->position;
        $this->time++;
        for ($x = $position->x - 1; $x <= $position->x + 1; $x++) {
            for ($y = $position->y - 1; $y <= $position->y + 2; $y++) {
                for ($z = $position->z - 1; $z <= $position->z + 1; $z++) {
                    $pos = new Position($x, $y, $z, $position->getLevel());
                    if ($this->time >= 20 * 30) {
                        $position->getLevel()->sendBlocks([$this->player], [$position->getLevel()->getBlock($pos)]);
                    } else {
                        if ($pos->equals($position) !== true) {
                            if ($pos->equals($position->add(0, 1)) !== true) {
                                $block = Block::get(Block::COBWEB);
                            } else {
                                $block = Block::get(Block::COBWEB);
                            }
                        } else {
                            $block = Block::get(Block::COBWEB);
                        }
                        $block->setComponents((int)$pos->x, (int)$pos->y, (int)$pos->z);
                        $position->getLevel()->sendBlocks([$this->player], [$block]);
                    }
                }
            }
        }
        if ($this->time >= 20 * 30) {
            unset($this->plugin->cobweb[$this->player->getName()]);
            $this->plugin->getScheduler()->cancelTask($this->getHandler()->getTaskId());
        }
    }
}