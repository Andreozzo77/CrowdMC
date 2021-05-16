<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use kenygamer\Core\Main;

class PardonTask extends Task{
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach($plugin->bans->getAll() as $player => $bans){
			if(empty($bans)) continue;
			if($plugin->isBanned($player) && $plugin->getBanTime($player) < 1){
				$plugin->warns->set($player, []);
				$plugin->updateDiscordEntry($player, "warns", "");
			}
		}
	}
	
}