<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use LegacyCore\Tasks\EnvoysTask;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class LastEnvoyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"lastenvoy",
			"View the last envoy coordinates",
			"/lastenvoy",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!$this->getPlugin()->isVip($sender)){
	        $sender->sendMessage("only-vip");
	        return true;
	    }
	    $envoy = EnvoysTask::$last_envoy;
	    if(!($envoy instanceof Vector3)){
			$sender->sendMessage("lastenvoy-despawned");
	    	return true;
	    }
	    $sender->sendMessage("lastenvoy", $envoy->getFloorX(), $envoy->getFloorY(), $envoy->getFloorZ());
	    $time = EnvoysTask::$last_envoy_time;
	    $timeleft = $time + 300;
	    if(!($timeleft - time() < 1)){
	    	$despawn = $this->getPlugin()->formatTime($this->getPlugin()->getTimeLeft($timeleft), TextFormat::AQUA, TextFormat::AQUA);
			$sender->sendMessage("lastenvoy-despawn", $despawn);
	    }else{
			$sender->sendMessage("lastenvoy-despawned");
	    }
	    return true;
	}
	
}