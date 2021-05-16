<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;
use kenygamer\Core\inventory\SaveableInventory;
use kenygamer\Core\LangManager;

class VaultCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"vault",
			"Open your vault",
			"/vault [player]",
			["pv"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = array_shift($args);
		if(is_string($player)){
			if(!$sender->hasPermission("core.vaults.see")){
				return false;
			}
			$player = $this->getPlugin()->getServer()->getPlayer($player);
			if($player === null){
				$sender->sendMessage("player-notfound");
				return true;
			}
			$menu = InvMenu::create(InvMenu::TYPE_CHEST);
			$inventory = SaveableInventory::createInventory("inv_" . $player->getName());
			$menu->setName(LangManager::translate("vault", $sender));
			$menu->getInventory()->setContents($inventory->getContents());
			$menu->setListener(InvMenu::readonly());
			$menu->send($sender);
			return true;
		}
		if(!$this->getPlugin()->hasVotedToday($sender) && !$sender->isOp()){
			$sender->sendMessage("vote-notvoted");
			return true;
		}
		$menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$inventory = SaveableInventory::createInventory("inv_" . $sender->getName());
		$menu->setName(LangManager::translate("vault", $sender));
		$menu->getInventory()->setContents($inventory->getContents());
		$menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inv) use($inventory){
			$inventory->setContents($inv->getContents());
		});
		$menu->send($sender);
		return true;
	}
	
}