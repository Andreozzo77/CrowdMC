<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\Main;

class RaidCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"raid",
			"Raid locked chests",
			"/raid",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!isset(Main::$raiding[$sender->getName()])){
    		Main::$raiding[$sender->getName()] = true;
    		$sender->sendMessage("raid-tap");
    		return true;
		}
   		unset(Main::$raiding[$sender->getName()]);
    	$sender->sendMessage("exitted");
		return true;
	}
	
}