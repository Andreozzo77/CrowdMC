<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class TransferXpCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"transferxp",
			"Transfer your EXP into money",
			"/transferxp <exp/all>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!isset($args[0])){
			return false;
		}
		if($args[0] === "all"){
	    	$exp = (int) floor($sender->getCurrentTotalXp());
	    }else{
	    	$exp = (int) floor($args[0]);
	    }
	    $resultMoney = $exp * 1000;
	    if($exp < 1){
			$sender->sendMessage("transferxp-toolow");
	    	return true;
	    }
	    if(!($sender->getCurrentTotalXp() < $exp)){
	    	$sender->subtractXp($exp);
	    	$this->getPlugin()->addMoney($sender, $resultMoney);
			$sender->sendMessage("transferxp-transferred", $exp, $resultMoney);
	    	return true;
	    }
		$sender->sendMessage("exp-needed");
	    return true;
	}
	
}