<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class SetLanguageCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"setlanguage",
			"Set your language",
			"/setlanguage <lang>",
			["setlang"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $lang = $args[0];
	    if(!in_array($lang, $list = LangManager::ALL_ISO_CODES) && $lang !== "auto"){
	    	$sender->sendMessage("setlang-error", $lang, implode(", ", $list));
	    	return true;
	    }
		$sender->sendMessage("setlang", $lang);
	    if($lang === "auto"){
	    	$this->getPlugin()->resetEntry($sender, Main::ENTRY_LANG);
	    }else{
	    	$this->getPlugin()->registerEntry($sender, Main::ENTRY_LANG, $lang);
	    }
	    return true;
	}
	
}