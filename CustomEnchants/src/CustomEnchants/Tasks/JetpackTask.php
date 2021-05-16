<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\level\particle\FlameParticle;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class JetpackTask
 * @package CustomEnchants\Tasks
 */
class JetpackTask extends Task
{
    private $plugin;

    /**
     * JetpackTask constructor.
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
            $enchantment = $player->getArmorInventory()->getBoots()->getEnchantment(CustomEnchantsIds::JETPACK);
            if ($enchantment !== null) {
                if (isset($this->plugin->flying[$player->getName()]) && $this->plugin->flying[$player->getName()] > time()) {
                    if (!in_array($player->getLevel()->getName(), $this->plugin->jetpackDisabled)) {
                        if (!($this->plugin->flying[$player->getName()] - 30 <= time())){
                            $time = ($this->plugin->flying[$player->getName()] - time());
                            $time = is_float($time / 15) ? floor($time / 15) + 1 : $time / 15;
                            $color = $time > 10 ? TextFormat::GREEN : ($time > 5 ? TextFormat::YELLOW : TextFormat::RED);
                            $player->sendTip($color . "Power: " . str_repeat("▌", $time));
                        }
                        $this->fly($player, $enchantment->getLevel());
                        continue;
                    }
                }
            }
            if (isset($this->plugin->flying[$player->getName()])) {
                if ($this->plugin->flying[$player->getName()] > time()) {
                    $this->plugin->flyremaining[$player->getName()] = $this->plugin->flying[$player->getName()] - time();
                    unset($this->plugin->jetpackcd[$player->getName()]);
                }
                unset($this->plugin->flying[$player->getName()]);
            }
            if (isset($this->plugin->flyremaining[$player->getName()])) {
                if ($this->plugin->flyremaining[$player->getName()] < 300) {
                    if (!isset($this->plugin->jetpackChargeTick[$player->getName()])) {
                        $this->plugin->jetpackChargeTick[$player->getName()] = 0;
                    }
                    $this->plugin->jetpackChargeTick[$player->getName()]++;
                    if ($this->plugin->jetpackChargeTick[$player->getName()] >= 30) {
                        $this->plugin->flyremaining[$player->getName()]++;
                    }
                }
            }
        }
    }

    /**
     * @param Player $player
     * @param $level
     */
    public function fly(Player $player, $level)
    {
        $player->setMotion($player->getDirectionVector()->multiply($level));
        $player->getLevel()->addParticle(new FlameParticle($player));
    }
}