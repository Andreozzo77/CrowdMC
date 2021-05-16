<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;

class TimeOnlineCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"timeonline",
			"View your or someone's time online",
			"/timeonline <player> [days]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$target = $args[1] ?? $sender->getName();
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($target);
		$timeonline = $this->getPlugin()->timeonline->getAll();
		$days = $args[0] ?? -1;
		$s = 0;
		foreach($timeonline as $p => $sessions){
			if(strcasecmp($p, $player->getName()) === 0){
				foreach($sessions as $session){
					foreach($session as $start => $seconds){
						if($days == -1 || time() - $start < 86400 * $days){
							$s += $seconds;
						}
					}
				}
			}
		}
		if($s < 1){
			$sender->sendMessage("player-notfound");
			return true;
		}
		$time = $this->getPlugin()->formatTime($this->getPlugin()->getTimeEllapsed(time() - $s));
		if($player->getName() !== $sender->getName()){
			$sender->sendMessage("timeonline-other", $player->getName(), $time);
		}else{
			$sender->sendMessage("timeonline", $time);
		}
		return true;
	}
	
}