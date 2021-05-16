<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;

class BreakUpCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"breakup",
			"Breaks up the current romance",
			"/breakup",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op" //true
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$loving = $this->getPlugin()->love->getNested($sender->getName() . ".loving");
	    if($loving === ""){
	    	$sender->sendMessage("breakup-none");
	    	return true;
	    }
	    $this->getPlugin()->love->setNested($sender->getName() . ".loving", "");
	    $this->getPlugin()->love->setNested($loving . ".loving", "");
	    $sender->sendMessage("breakup", $loving);
	    $player = $this->getPlugin()->getServer()->getPlayerExact($loving);
	    if($player instanceof Player){
	    	$player->sendMessage("breakup-target", $sender->getName());
		}
		return true;
	}
	
}