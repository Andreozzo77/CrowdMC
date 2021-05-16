<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class MaintenanceTask extends Task{
	/** @var int */
	private $runs;
	/** @var int */
	private static $maintenanceSince;
	
	public function __construct(){
		$this->runs = 0;
		self::$maintenanceSince = time();
	}
	
	public static function resetMaintenanceSince() : void{
		self::$maintenanceSince = time();
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$plugin->getServer()->setConfigBool("white-list", false);
		$maintenanceCfg = $plugin->getMaintenanceConfig();
		if($maintenanceCfg->get("manual-status") && !$plugin->maintenanceMode){
			$plugin->maintenanceMode = true;
			self::resetMaintenanceSince();
			$plugin->getServer()->getLogger()->warning(LangManager::translate("maintenance-status", "on"));
		}elseif($plugin->maintenanceMode){
			foreach($plugin->getServer()->getOnlinePlayers() as $player){
				if(!$plugin->getServer()->isOp($player->getName())){
					$player->kick($plugin->getMaintenanceReason(), false);
				}
			}
		    if(++$this->runs >= 60 * 5){
		    	$timeLeft = $plugin->getTimeLeft(time() + (time() - self::$maintenanceSince));
		    	if(!empty($timeLeft)){
		    		$since = $plugin->formatTime($timeLeft);
		    		$plugin->getServer()->getLogger()->warning(LangManager::translate("maintenance-alert", $since));
		    	}
		    	$this->runs = 0;
		    }
		}else{
		    foreach($plugin->getConfig()->get("depend") as $name){
		    	$obj = $plugin->getServer()->getPluginManager()->getPlugin($name);
		    	if($obj === null || !$obj->isEnabled()){
		    		$plugin->maintenanceMode = true;
		    		LangManager::broadcast("maintenance-on-plugin", $name);
		    		break;
				}
			}
		}
	}
	
}