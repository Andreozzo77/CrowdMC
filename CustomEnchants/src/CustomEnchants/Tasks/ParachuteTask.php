<?php

namespace CustomEnchants\Tasks;


use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

/**
 * Class ParachuteTask
 * @package CustomEnchants\Tasks
 */
class ParachuteTask extends Task
{
    private $plugin;

    /**
     * ParachuteTask constructor.
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
		return; //Todo
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $chestplate = $player->getArmorInventory()->getChestplate();
            $enchantment = $chestplate->getEnchantment(CustomEnchantsIds::PARACHUTE);
            if ($enchantment !== null) {
                $motion = $player->getMotion();
                if ($this->plugin->checkBlocks($player, 0, 3)) {
                    $player->setMotion(new Vector3(0, $motion->y * 0.75, 0));
                    $player->resetFallDistance();
                }
            }
        }
    }
}