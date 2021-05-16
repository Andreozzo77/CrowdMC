<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\particle\FlameParticle;
use pocketmine\Player;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

use kenygamer\Core\Main as EEMain;

/**
 * Class ForcefieldTask
 * @package CustomEnchants\Tasks
 */
class ForcefieldTask extends Task
{
    private $plugin;

    /**
     * ForcefieldTask constructor.
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
            $forcefields = 0;
            foreach ($player->getArmorInventory()->getContents() as $armor) {
                $enchantment = $armor->getEnchantment(CustomEnchantsIds::FORCEFIELD);
                if ($enchantment !== null) {
					if (isset($this->plugin->nopower[$player->getName()])) {
			     	} else {
                        $forcefields += $enchantment->getLevel();
					}
                }
            }
            if ($forcefields > 0 && in_array($world = $player->getLevel()->getFolderName(), EEMain::PVP_WORLDS) && $world !== "duels"){
                $radius = $forcefields * 0.75;
                $entities = $player->getLevel()->getNearbyEntities($player->getBoundingBox()->expandedCopy($radius, $radius, $radius), $player);
                foreach ($entities as $entity) {
                    if ($entity instanceof Projectile) {
                        if ($entity->getOwningEntity() !== $player) {
                            $entity->setMotion($entity->getMotion()->multiply(-1));
                        }
                    } else {
                        if (!($entity instanceof ItemEntity) && !(!($entity instanceof Player) && $entity instanceof Human)){
                            $entity->setMotion(new Vector3($player->subtract($entity)->normalize()->multiply(-0.75)->x, 0, $player->subtract($entity)->normalize()->multiply(-0.75)->z));
                        }
                    }
                }
                if (!isset($this->plugin->forcefieldParticleTick[$player->getName()])) {
                    $this->plugin->forcefieldParticleTick[$player->getName()] = 0;
                }
                $this->plugin->forcefieldParticleTick[$player->getName()]++;
                if ($this->plugin->forcefieldParticleTick[$player->getName()] >= 7.5) {
                    $diff = $radius / $forcefields;
                    for ($theta = 0; $theta <= 360; $theta += $diff) {
                        $x = $radius * sin($theta);
                        $y = 0.5;
                        $z = $radius * cos($theta);
                        $pos = $player->add($x, $y, $z);
                        $player->getLevel()->addParticle(new FlameParticle($pos));
                    }
                    $this->plugin->forcefieldParticleTick[$player->getName()] = 0;
                }
            }
        }
    }
}