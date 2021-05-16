<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use kenygamer\Core\Main;
 
class GiveawayCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"giveaway",
			"Start a giveaway",
			"/giveaway <playerCount>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $playerCount = intval($args[0]);
	    if($playerCount < 0 xor $playerCount > ($maxPlayers = $this->getPlugin()->getServer()->getMaxPlayers())){
	    	$sender->sendMessage("giveaway-playercount", $maxPlayers);
	    	return true;
	    }
	    $menu = InvMenu::create(InvMenu::TYPE_CHEST);
	    $menu->setName("Giveaway Items");
	    $menu->send($sender);
	    $menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) use($playerCount){
	    	$items = $inventory->getContents(false);
	    	if(empty($items)){
	    		$player->sendMessage("giveaway-noitems");
	    	}else{
	    		Main::$giveawayStatus[0] = true;
	    		Main::$giveawayStatus[5] = $items;
	    		Main::$giveawayStatus[6] = $playerCount;
	    		$player->sendMessage("giveaway-started", $playerCount);
	    	}
	    });
		return true;
	}
	
}