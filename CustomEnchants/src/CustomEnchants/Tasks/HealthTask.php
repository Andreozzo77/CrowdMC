<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;

/**
 * Class HealthTask
 * @package CustomEnchants\Tasks
 */
class HealthTask extends Task
{
    private $plugin;

    /**
     * HealthTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
	    foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
	        foreach ($player->getArmorInventory()->getContents(true) as $slot => $armor) {
                $enchantment = $armor->getEnchantment(CustomEnchantsIds::OVERLOAD);
                if ($enchantment !== null || $player instanceof Player) {
                    if (!isset($this->plugin->overload[$player->getName() . "||" . $slot])) {
                        $max = $player->getMaxHealth() + (2 * $enchantment->getLevel());
                        if ($max >= 0) {
                        	$player->setMaxHealth($max);
                        }
                        $player->setHealth($player->getHealth() + (2 * $enchantment->getLevel()) < $player->getMaxHealth() ? $player->getHealth() + (2 * $enchantment->getLevel()) : $player->getMaxHealth());
                        $this->plugin->overload[$player->getName() . "||" . $slot] = $enchantment->getLevel();
                    }
                } else {
                    if (isset($this->plugin->overload[$player->getName() . "||" . $slot])) {
                        $level = $this->plugin->overload[$player->getName() . "||" . $slot];
                        $max = $player->getMaxHealth() - (2 * $level);
                        if ($max >= 0) {
                        	$player->setMaxHealth($max);
                        }
                        if ($player->isAlive()) {
                        	$health = $player->getHealth() - (2 * $level) < $player->getMaxHealth() ? ($player->getHealth() - (2 * $level) <= 0 ? 1 : $player->getHealth() - (2 * $level)) : $player->getMaxHealth();
                        	$player->setHealth($health > 1 ? $health : 1); //must not go below 1 or will kill the player
                        }
                        unset($this->plugin->overload[$player->getName() . "||" . $slot]);
                    }
                }
            }
            if($player->getMaxHealth() < 20){ //fix random kills
                $player->setMaxHealth(20);
            }
		}
	}
}