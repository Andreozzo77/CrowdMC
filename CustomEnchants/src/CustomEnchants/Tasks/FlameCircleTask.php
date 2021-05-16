<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\utils\TextFormat;
use pocketmine\level\particle\FlameParticle;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;

class FlameCircleTask extends Task
{
    private $plugin;

    /**
     * FlameCircleTask constructor.
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
            $enchantment = $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::FLAMECIRCLE);
            if ($enchantment !== null) {
			    $effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 240, $enchantment->getLevel() + 3, false); 
                $player->addEffect($effect);
			 	$effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 240, $enchantment->getLevel() + 3, false); 
                $player->addEffect($effect2);
                $r = rand(1, 300);
                $g = rand(1, 300);
                $b = rand(1, 300);
                $x = $player->getX();
                $y = $player->getY();
                $z = $player->getZ();
                $center = new Vector3($x, $y, $z);
                $particle = new FlameParticle($center, $r, $g, $b, 1);
                for($yaw = 0, $y = $center->y; $y < $center->y + 1.5; $yaw += (M_PI * 2) / 20, $y += 1 / 20){
                    $x = -sin($yaw) + $center->x;
                    $z = cos($yaw) + $center->z;
                    $particle->setComponents($x, $y, $z);
                    $player->getLevel()->addParticle($particle);
				    $this->plugin->flamecircle[$player->getName()] = true;
				}
            } else {
                if (isset($this->plugin->flamecircle[$player->getName()])) {
                    $player->removeEffect(Effect::STRENGTH);
					$player->removeEffect(Effect::DAMAGE_RESISTANCE);
                    unset($this->plugin->flamecircle[$player->getName()]);
                }
            }
		}
	}
}
