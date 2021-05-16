<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\Player;

/**
 * Class SpiderTask
 * @package CustomEnchants\Tasks
 */
class SpiderTask extends Task
{
    private $plugin;

    /**
     * SpiderTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    
    private function canClimb(Player $player) : bool{
    	foreach(array_merge($player->getLevel()->getBlock($player->add(0, (count($player->getLevel()->getBlock($player)->getCollisionBoxes()) > 0 ? ceil($player->y) - $player->y + 0.01 : 0)))->getHorizontalSides(), $player->getLevel()->getBlock($player->add(0, 1))->getHorizontalSides()) as $block){
    		if($block->isSolid()){
    			return true;
            }
        }
        return false;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
		return; //TODO
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $enchantment = $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::SPIDER);
            if ($enchantment !== null) {
                $player->setCanClimbWalls($this->canClimb($player));
            } else {
            	$player->setCanClimbWalls(false);
            }
        }
    }
}