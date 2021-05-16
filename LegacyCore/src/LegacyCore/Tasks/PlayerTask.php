<?php

namespace LegacyCore\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\entity\BaseBoss;
use LegacyCore\Core;
use LegacyCore\Events\PlayerEvents;

/**
 * @class PlayerTask
 * @package LegacyCore\Tasks
 */
class PlayerTask extends Task{
	/** @var Core */
	private $plugin;

	/**
     * PlayerTask constructor.
     * @param Core $plugin
     */
    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
    }
	
	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			$bounty = Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY);
			$device = PlayerEvents::OS_LIST[PlayerEvents::getPlayerData($player)["DeviceOS"]];
            if($bounty !== 0){
				$player->setScoreTag(TextFormat::colorize('&6• &a$' . number_format($bounty) . "\n &e[" . $device . "] &f" . $player->getHealth()  . "/" . $player->getMaxHealth() . " &c\xe2\x99\xa5"));
			}else{
			    $player->setScoreTag(TextFormat::colorize("&e[" . $device . "] &f" . $player->getHealth()  . "/" . $player->getMaxHealth() . " &c\xe2\x99\xa5"));
			}
			
			if($player->getFood() > 18){
				$player->setFood(18);
			}
		}
		foreach($this->plugin->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $npc){
	            if($npc instanceof BaseBoss){
		            $npc->setScoreTag(TextFormat::colorize("&f" . $npc->getHealth()  . "/" . $npc->getMaxHealth() . " &c\xe2\x99\xa5"));
				}
			}
        }
    }
    
}