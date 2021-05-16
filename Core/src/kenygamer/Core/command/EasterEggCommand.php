<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;

class EasterEggCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"easteregg",
			"Easter Egg Command",
			"/easteregg",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!in_array($sender->getName(), MiscListener::getInstance()->placeegg)){
	    	MiscListener::getInstance()->placeegg[] = $sender->getName();
	    }else{
	    	unset(MiscListener::getInstance()->placeegg[array_search($sender->getName(), MiscListener::getInstance()->placeegg)]);
	    }
	}
	
}