<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;

class LoveCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"love",
			"Love the specified player",
			"/love <player>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op" //true
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if($this->getPlugin()->love->getNested($sender->getName() . ".loving") !== ""){
	    	$lovedata = $this->getPlugin()->love->get($sender->getName());
	    	$sender->sendMessage("love-loving", $lovedata["loving"]);
	    }else{
	    	$player = $this->getPlugin()->getServer()->getPlayerExact($args[0]);
	    	if(!($player instanceof Player)){
	    		$sender->sendMessage("love-offline");
	    		return true;
	    	}
	    	if($player === $sender){
	    		$sender->sendMessage("love-me");
	    		return true;
	    	}
	    	if($this->getPlugin()->love->getNested($player->getName() . ".loving") !== ""){
	    		$alonePlayers = 0;
	    		foreach($this->getPlugin()->love->getAll() as $pl => $entry){
	    			if($entry["loving"] === "" && $pl !== $sender->getName()){
	    				$alonePlayers++;
	    			}
	    		}
	    	    $sender->sendMessage("love-taken", $player->getName(), $alonePlayers);
	    	    return true;
	    	}
	    	if($this->getPlugin()->love->getNested($player->getName() . ".nolove")){
	    		$sender->sendMessage("love-disabled", $player->getName());
	    		return true;
	    	}
	    	foreach($this->getPlugin()->loveRequests as $requester => $receiver){
	    		if(strcasecmp($receiver, $sender->getName()) === 0 && strcasecmp($player->getName(), $requester) === 0){
	    			$this->getPlugin()->love->setNested($receiver . ".loving", $requester);
	    			$this->getPlugin()->love->setNested($requester . ".loving", $receiver);
	    			$sender->sendMessage("love-accept", $player->getName());
	    			$player->sendMessage("love-accepted", $sender->getName());
	    			unset($this->getPlugin()->loveRequests[$requester]);
	    			return true;
	    		}
	    	}
	    	$this->getPlugin()->loveRequests[$sender->getName()] = $player->getName();
	    	$player->sendMessage("love-request", $sender->getName());
	    	$sender->sendMessage("love-requested", $player->getName());
	    }
	    return true;
	}

}