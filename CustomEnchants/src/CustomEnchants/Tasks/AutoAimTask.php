<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

/**
 * Class AutoAimTask
 * @package CustomEnchants
 */
class AutoAimTask extends Task
{
    private $plugin;
    private $lastPosition;

    /**
     * AutoAimTask constructor.
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
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $enchantment = $player->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::AUTOAIM);
            if ($enchantment !== null) {
                $detected = $this->plugin->findNearestEntity($player, $enchantment->getLevel() * 20, Player::class, $player);
                if (!is_null($detected)) {
                    if (!isset($this->lastPosition[$player->getName()])) {
                        $this->lastPosition[$player->getName()] = $detected->asVector3();
                    }
                    if ($detected instanceof Player) {
                        if ($detected->asVector3() == $this->lastPosition[$player->getName()] && isset($this->plugin->moved[$player->getName()]) !== true) {
                            break;
                        }
                        if (isset($this->plugin->moved[$player->getName()])) {
                            if ($this->plugin->moved[$player->getName()] < 15) {
                                $this->plugin->moved[$player->getName()]++;
                                break;
                            }
                            unset($this->plugin->moved[$player->getName()]);
                        }
                        $this->lastPosition[$player->getName()] = $detected->asVector3();
                        $player->lookAt($detected);
                        $player->sendPosition($player);
                        break;
                    }
                }
            }
        }
    }
}