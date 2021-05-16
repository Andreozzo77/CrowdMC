<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class FreezeTask
 * @package CustomEnchants\Tasks
 */
class FreezeTask extends Task
{
    private $plugin;
	
	/**
     * FreezeTask constructor.
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
        foreach($this->plugin->freeze as $player => $time) {
            if ((time() - $time) > $this->plugin->cold) {
                $p = $this->plugin->getServer()->getPlayer($player);
                if ($p instanceof Player){
                    $p->sendMessage(TextFormat::colorize("&aYou are no longer frozen."));
                    unset($this->plugin->freeze[$player]);
				} else {				
			    	unset($this->plugin->freeze[$player]);
				}
            }
        }
    }
}