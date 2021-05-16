<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use LegacyCore\Commands\Tell;
	
class ReplyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"reply",
			"Reply to the last private message",
			"/reply <...msg>",
			["r"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset(Tell::$reply[$sender->getName()])){
	    	$sender->chat("/tell \"" . Tell::$reply[$sender->getName()] . "\" \"" . implode(" ", $args) . "\"");
	    	unset(Tell::$reply[$sender->getName()]);
	    	return true;
	    }
	    $sender->sendMessage("reply-nomsg");
		return true;
	}
	
}