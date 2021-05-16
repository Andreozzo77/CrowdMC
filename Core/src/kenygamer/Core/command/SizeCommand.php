<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\LangManager;

class SizeCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"size",
			"Change your size",
			"/size <value>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $size = (float) $args[0];
	    if(isset($args[1]) && $sender->isOp()){
	    	$target = $this->getPlugin()->getServer()->getPlayerExact($args[1]);
	    	if(!($target instanceof Player)){
	    		$sender->sendMessage("player-notfound");
	    		return true;
	    	}
	    }else{
	    	$target = $sender;
	    }
	    try{
	    	$target->setScale($size);
	    }catch(\InvalidArgumentException $e){
	    	$sender->sendMessage($e->getMessage());
	    	return true;
	    }
	    $sender->sendMessage($target === $sender ? LangManager::translate("size-updated-me", $sender) : LangManager::translate("size-updated-other", $sender, $target->getName()));
	    return true;
	}
	
}