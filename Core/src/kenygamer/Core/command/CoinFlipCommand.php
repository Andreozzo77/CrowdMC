<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\task\CoinFlipTask;

class CoinFlipCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"coinflip",
			"Flip a coin with another player",
			"/coinflip <player> <money>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(in_array($sender->getName(), Main::$gambling)){
	    	$sender->sendMessage("coinflip-playing");
	    	return true;
	    }
	    $player = $this->getPlugin()->getServer()->getPlayer($args[0]);
	    if(!($player instanceof Player)){
	    	$sender->sendMessage("player-notfound");
	    	return true;
	    }
	    if($player === $sender){
			$sender->sendMessage("coinflip-notyou");
	    	return true;
	    }
	    $money = intval($args[1]);
	    if($money < 1000000){
			$sender->sendMessage("coinflip-minimum");
	    	return true;
	    }
	    if($sender->getMoney() < $money){
			$sender->sendMessage("money-needed", $money);
	    	return true;
	    }
	    if($player->myMoney() < $money){
			$sender->sendMessage("coinflip-targetnomoney");
	    	return true;
	    }
	    foreach(Main::getInstance()->coinFlip as $requester => $data){
	    	list($receiver, $amount) = $data;
	    	if($receiver === $sender->getName() && $money === $amount){
	    		Main::$gambling[] = $receiver;
	    		Main::$gambling[] = $requester;
	    		foreach([$player, $sender] as $recipient){
					$recipient->sendMessage("coinflip-accepted");
	    		}
	    		unset($this->getPlugin()->coinFlip[$requester]);
	    		$this->getPlugin()->getServer()->getAsyncPool()->submitTask(new CoinFlipTask($requester, $receiver, $amount));
	    		return true;
	    	}
	    }
		$player->sendMessage("coinflip-request", $sender->getName(), $money);
		$sender->sendMessage("coinflip-sent", $player->getName());
	    $this->getPlugin()->coinFlip[$sender->getName()] = [$player->getName(), $money];
	    return true;
	}
	
}