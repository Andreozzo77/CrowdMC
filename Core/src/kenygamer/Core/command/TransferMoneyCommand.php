<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class TransferMoneyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"transfermoney",
			"Transfer your money into EXP",
			"/transfermoney <money/all>",
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
	    	$money = (int) floor($sender->getMoney());
	    }else{
	    	$money = (int) floor($args[0]);
	    }
	    $resultXp = intval($money / 1000);
	    if($money < 1000){
			$sender->sendMessage("transfermoney-toolow");
	    	return true;
	    }
	    $myMoney = $sender->getMoney();
	    if($money <= $myMoney){
	    	$this->getPlugin()->reduceMoney($sender, $money);
	    	$sender->addXp($resultXp);
			$sender->sendMessage("transfermoney-transferred", $money, $resultXp
		);
	    	return true;
	    }
		$sender->sendMessage("money-needed", $money);
		return true;
	}
	
}