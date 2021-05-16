<?php

namespace CustomEnchants\Tasks;


use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class MeditationTask
 * @package CustomEnchants\Tasks
 */
class MeditationTask extends Task
{
    private $plugin;

    /**
     * MeditationTask constructor.
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
            $enchantment = $player->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::MEDITATION);
            if ($enchantment !== null) {
                if (!isset($this->plugin->meditationTick[$player->getName()])) {
                    $this->plugin->meditationTick[$player->getName()] = 0;
                }
                $this->plugin->meditationTick[$player->getName()]++;
                $time = $this->plugin->meditationTick[$player->getName()] / 40;
                $player->sendTip(TextFormat::DARK_GREEN . "Meditating...\n " . TextFormat::GREEN . str_repeat("▌", $time));
                if ($this->plugin->meditationTick[$player->getName()] >= 20 * 20) {
                    $this->plugin->meditationTick[$player->getName()] = 0;
                    $event = new EntityRegainHealthEvent($player, $enchantment->getLevel(), EntityRegainHealthEvent::CAUSE_MAGIC);
                    if (!$event->isCancelled()) {
                        $player->heal($event);
                    }
                    $player->setFood($player->getFood() + $enchantment->getLevel() > 20 ? 20 : $player->getFood() + $enchantment->getLevel());
                }
            }
        }
    }
}