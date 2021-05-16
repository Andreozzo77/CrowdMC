<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class SizeTask
 * @package CustomEnchants
 */
class SizeTask extends Task
{
    private $plugin;

    /**
     * SizeTask constructor.
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
            $shrinkpoints = 0;
            $growpoints = 0;
            foreach ($player->getArmorInventory()->getContents() as $armor) {
                $enchantment = $armor->getEnchantment(CustomEnchantsIds::SHRINK);
                if ($enchantment !== null) {
                    $shrinkpoints++;
                }
            }
            if (isset($this->plugin->shrunk[$player->getName()]) && ($this->plugin->shrunk[$player->getName()] <= time() || $shrinkpoints < 4)) {
                if ($this->plugin->shrunk[$player->getName()] > time()) {
                    $this->plugin->shrinkremaining[$player->getName()] = $this->plugin->shrunk[$player->getName()] - time();
                    unset($this->plugin->shrinkcd[$player->getName()]);
                }
                unset($this->plugin->shrunk[$player->getName()]);
                $player->setScale(1);
            }
            foreach ($player->getArmorInventory()->getContents() as $armor) {
                $enchantment = $armor->getEnchantment(CustomEnchantsIds::GROW);
                if ($enchantment !== null) {
                    $growpoints++;
                }
            }
            if (isset($this->plugin->grew[$player->getName()]) && ($this->plugin->grew[$player->getName()] <= time() || $growpoints < 4)) {
                if ($this->plugin->grew[$player->getName()] > time()) {
                    $this->plugin->growremaining[$player->getName()] = $this->plugin->grew[$player->getName()] - time();
                    unset($this->plugin->growcd[$player->getName()]);
                }
                unset($this->plugin->grew[$player->getName()]);
                $player->setScale(1);
            }
        }
    }
}