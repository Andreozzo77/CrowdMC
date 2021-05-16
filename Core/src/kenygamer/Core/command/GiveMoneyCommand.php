<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;

class GiveMoneyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"givemoney",
			"Gives money to player",
			"/givemoney <player> <money>",
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
		if($player instanceof Player){
			$player->sendMessage("givemoney-money-given", number_format($money));
		}
		$this->getPlugin()->addMoney($player, $money);
		$sender->sendMessage("givemoney-gave-money", number_format($money), $player->getName());
		return true;
	}
	
}