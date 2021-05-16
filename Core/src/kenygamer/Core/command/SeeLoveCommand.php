<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class SeeLoveCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"seelove",
			"View what player they are loving",
			"/seelove <lover>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op" //true
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $lover = $args[0];
	    $love = array_change_key_case($this->getPlugin()->love->getAll(), CASE_LOWER);
	    if(isset($love[mb_strtolower($lover)])){
	    	if(($loving = $love[mb_strtolower($lover)]["loving"]) !== ""){
				$sender->sendMessage("seelove", $lover, $love[mb_strtolower($lover)]["loving"]);
	    	}else{
				$sender->sendMessage("seelove-none", $lover);
	    	}
	    }else{
	    	$sender->sendMessage("seelove-invalid");
	    }
	    return true;
	}
	
}