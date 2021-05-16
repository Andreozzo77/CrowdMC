<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener2;

class ItemCaseCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"ic",
			"ItemCase Command",
			"/ic",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset(MiscListener2::$itemCase[$sender->getName()])){
			unset(MiscListener2::$itemCase[$sender->getName()]);
			$sender->sendMessage("ic-cancel");
			return true;
		}
		MiscListener2::$itemCase[$sender->getName()] = true;
		$sender->sendMessage("ic");
		return true;
	}
	
}