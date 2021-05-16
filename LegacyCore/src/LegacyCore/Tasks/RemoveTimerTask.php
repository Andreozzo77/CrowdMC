<?php

namespace LegacyCore\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class RemoveTimerTask extends Task{

    /**
     * WarzoneTask constructor.
     * @param Main $plugin
     * @param Player $player
     */
    public function __construct($plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
        foreach($this->plugin->warzones as $player => $time) {
            if ((time() - $time) > $this->plugin->wztimer) {
                $p = $this->plugin->getServer()->getPlayer($player);
                if ($p instanceof Player){
                	LangManager::send("notinvulnerable", $p);
                    unset($this->plugin->warzones[$player]);
                } else {
			     	unset($this->plugin->warzones[$player]);
				}
            }
        }
        foreach($this->plugin->pvpmine as $player => $time) {
            if ((time() - $time) > $this->plugin->pmtimer) {
                $p = $this->plugin->getServer()->getPlayer($player);
                if ($p instanceof Player){
					$p->sendMessage("notinvulnerable");
                    unset($this->plugin->pvpmine[$player]);
                } else {
			     	unset($this->plugin->pvpmine[$player]);
				}
            }
        }
        foreach($this->plugin->suvwild as $player => $time) {
            if ((time() - $time) > $this->plugin->wildtime) {
                unset($this->plugin->suvwild[$player]);
            }
        }
    }
}