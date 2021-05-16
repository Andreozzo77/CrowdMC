<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class ClearChatCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"clearchat",
			"Clear your chat",
			"/clearchat",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		for($i = 0; $i < 99; $i++){
			$sender->sendMessage(str_repeat("§f", $i + 1));
		}
		$sender->sendMessage("clearchat");
		return true;
	}
	
}
	