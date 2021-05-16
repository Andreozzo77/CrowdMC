<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

/**
 * Class TotemTask
 * @package CustomEnchants\Tasks
 */
class TotemTask extends Task
{
    private $plugin;

    /**
     * TotemTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if(isset($this->plugin->shieldplayer[$player->getName()])){
                $pk = new EntityEventPacket();
                $pk->entityRuntimeId = $player->getId(); 
                $pk->event = EntityEventPacket::CONSUME_TOTEM;
                $pk->data = 0;
                $player->dataPacket($pk);
            }
		}
    }
}