<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main2;

class WingsCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"wings",
			"Wings Editor",
			"/wings",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		Main2::wingsHome($sender);
		return true;
	}
	
}