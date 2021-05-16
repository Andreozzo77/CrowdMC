<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;

class TakeMoneyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"takemoney",
			"Takes money from player",
			"/takemoney <player> <money>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = $this->getPlugin()->getServer()->getOfflinePlayer(array_shift($args));
		$money = (float) array_shift($args);
		if($money < 1){
			$sender->sendMessage("positive-value");
			return true;
		}
		if(!$this->getPlugin()->reduceMoney($player, $money)){
			$sender->sendMessage("takemoney-error", $player->getName(), $this->getPlugin()->myMoney($player));
			return true;
		}
		if($player instanceof Player){
			$player->sendMessage("takemoney-money-taken", number_format($money));
		}
		$sender->sendMessage("takemoney-took-money", number_format($money), $player->getName());
		return true;
	}
	
}