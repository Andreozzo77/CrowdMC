<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Living;
use pocketmine\level\particle\FlameParticle;
use pocketmine\scheduler\Task;

/**
 * Class HellForgedTask
 * @package CustomEnchants\Tasks
 */
class HellForgedTask extends Task
{
    private $plugin;

    /**
     * HellForgedTask constructor.
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
            $enchantment = $player->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::HELLFORGED);
            if ($enchantment !== null) {
                $radius = $enchantment->getLevel() * 0.5;
                foreach ($player->getLevel()->getEntities() as $entity) {
                    if ($entity !== $player && $entity instanceof Living && $entity->distance($player) <= $radius) {
                    }
                }
                if (!isset($this->plugin->hellfireTick[$player->getName()])) {
                    $this->plugin->hellfireTick[$player->getName()] = 0;
                }
                $this->plugin->hellfireTick[$player->getName()]++;
                if ($this->plugin->hellfireTick[$player->getName()] >= 20) {
                    for ($x = -$radius; $x <= $radius; $x += 0.15) {
                        for ($y = -$radius; $y <= $radius; $y += 0.15) {
                            for ($z = -$radius; $z <= $radius; $z += 0.15) {
                                $random = \kenygamer\Core\Main::mt_rand(1, 100 * $enchantment->getLevel());
                                if ($random == 100 * $enchantment->getLevel()) {
                                    $player->getLevel()->addParticle(new FlameParticle($player->add($x, $y, $z), 34, 139, 34));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}