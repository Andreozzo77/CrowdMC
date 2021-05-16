<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class BleedingTask
 * @package CustomEnchants\Tasks
 */
class BleedingTask extends Task
{
    private $plugin;
	
	/**
     * BleedingTask constructor.
     * @param Main $plugin
     * @param Entity $entity
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
        foreach($this->plugin->bleeding as $player => $time) {
            if ((time() - $time) > $this->plugin->bleed) {
                $p = $this->plugin->getServer()->getPlayer($player);
                if ($p instanceof Player){
                    $p->sendMessage("§l§e(!) §r§6Bleeding: §eYou are no longer bleeding.");
                    unset($this->plugin->bleeding[$player]);
                } else { 
				    unset($this->plugin->bleeding[$player]);
				}
            }
        }
    }
}