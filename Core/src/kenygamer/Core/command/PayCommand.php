<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\ModalForm;
use kenygamer\Core\LangManager;

class PayCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"pay",
			"Pays money to player",
			"/pay <player> <money>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = $this->getPlugin()->getServer()->getOfflinePlayer(array_shift($args));
		if(!$player->hasPlayedBefore()){
			$sender->sendMessage("player-notfound");
			return true;
		}
		if($player->getName() === $sender->getName()){
			$sender->sendMessage("pay-no-self");
			return true;
		}
		$money = (float) array_shift($args);
		if($sender->getMoney() < $money){
			$sender->sendMessage("pay-no-money", $player->getName());
			return true;
		}
		$form = new ModalForm(function(Player $sender, ?bool $data) use($money, $player){
			if($data){
				if($sender->reduceMoney($money)){
					$this->getPlugin()->addMoney($player, $money);
					$sender->sendMessage("pay-success", number_format($money), $player->getName());
					if($player instanceof Player){
						$player->sendMessage("money-paid", $sender->getName(), number_format($money));
					}
				}else{
					$sender->sendMessage("pay-error");
				}
			}
		});
		$form->setTitle(LangManager::translate("pay-ask-title", $sender));
		$form->setContent(LangManager::translate("pay-ask-content", $sender));
		$form->setButton1(LangManager::translate("continue", $sender));
		$form->setButton2(LangManager::translate("cancel", $sender));
		$sender->sendForm($form);
		return true;
	}
	
}