<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;

class ToggleLoveCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"togglelove",
			"Toggle love requests",
			"/togglelove",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op" //true
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!$this->getPlugin()->love->getNested($sender->getName() . ".nolove")){
	    	$this->getPlugin()->love->setNested($sender->getName() . ".nolove", true);
	    	$sender->sendMessage("togglelove-off");
	    	return true;
	    }
	    $this->getPlugin()->love->setNested($sender->getName() . ".nolove", false);
	    $sender->sendMessage("togglelove-on");
		return true;
	}
	
}