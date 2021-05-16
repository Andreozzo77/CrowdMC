<?php

declare(strict_types=1);

namespace kenygamer\Core\command;
	
class AFKCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"afk",
			"Toggle your AFK status",
			"/afk",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$new = !$this->getPlugin()->isAFK($sender, true);
		if($new){
			$sender->sendMessage("afk-on");
		}else{
			$sender->sendMessage("afk-off");
		}
		return true;
	}
	
}