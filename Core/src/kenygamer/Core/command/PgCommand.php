<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;

class PgCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"pg",
			"Lock and unlock chests",
			"/pg <lock/passlock/unlock/passunlock/info>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
        switch($option = array_shift($args)){
            case "lock":
			case "unlock":
			case "public":
			case "info":
            	MiscListener::$pgQueue[$sender->getName()] = [$option];
				break;
			case "passlock":
			case "passunlock":
				$arg = array_shift($args);
                MiscListener::$pgQueue[$sender->getName()] = [$option, $arg];
				break;
		}
		$sender->sendMessage("pg-touch", ucfirst($option));
		return true;
	}
	
}