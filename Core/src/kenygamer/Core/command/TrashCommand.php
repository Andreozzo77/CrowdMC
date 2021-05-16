<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use kenygamer\Core\util\ItemUtils;

class TrashCommand extends BaseCommand{
		
	public function __construct(){
		parent::__construct(
			"trash",
			"Clear your inventory",
			"/trash <hand/all/toggle/undo>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		switch(mb_strtolower($args[0])){
			case "hand":
				$item = $sender->getInventory()->getItemInHand();
	    	    if($item->getId() === Item::AIR){
	    	    	$sender->sendMessage("hold-item");
	    	    	break;
	    	    }
	    	    $this->getPlugin()->trashRollback[$sender->getName()][0][] = $item;
	    	    $sender->getInventory()->setItemInHand(ItemFactory::get(Item::AIR, 0, 0));
				$sender->sendMessage("trash-hand-cleared", ItemUtils::getDescription($item));
	    	    break;
	    	case "all":
	    	    $items = $sender->getInventory()->getContents(false);
	    	    $armor = $sender->getArmorInventory()->getContents(false);
	    	    if(empty($items) && empty($armor)){
	    	    	$sender->sendMessage("trash-all-none");
	    	    	break;
	    	    }
	    	    $this->getPlugin()->trashRollback[$sender->getName()] = [$items, $armor];
	    	    $sender->getInventory()->setContents([]);
	    	    $sender->getArmorInventory()->setContents([]);
				$sender->sendMessage("trash-cleared", count($items));
	    	    break;
	    	case "toggle":
	    	    $mode = isset($this->getPlugin()->trashMode[$sender->getName()]);
	    	    if($mode){
	    	    	$new = false;
	    	    	unset($this->getPlugin()->trashMode[$sender->getName()]);
	    	    }else{
	    	    	$new = true;
	    	    	$this->getPlugin()->trashMode[$sender->getName()] = $new;
	    	    }
				$sender->sendMessage("trash-toggle", $new ? "off" : "on");
	    	    break;
	    	case "undo":
	    	    if(!isset($this->getPlugin()->trashRollback[$sender->getName()])){
					$sender->sendMessage("trash-undo-none");
	    	    	break;
	    	    }
	    	    $all = $this->getPlugin()->trashRollback[$sender->getName()];
	    	    foreach($all[0] as $index => $item){
	    	    	if($sender->getInventory()->canAddItem($item)){
	    	    		$sender->getInventory()->addItem($item);
	    	    		unset($this->getPlugin()->trashRollback[$sender->getName()][0][$index]);
	    	    	}else{
	    	    		$sender->sendMessage("inventory-nospace");
	    	    		break 2;
	    	    	}
	    	    }
	    	    foreach($all[1] ?? [] as $index => $item){
	    	    	if($sender->getArmorInventory()->getItem($index)->isNull()){
	    	    		$sender->getArmorInventory()->setItem($index, $item);
	    	    		unset($this->getPlugin()->trashRollback[$sender->getName()][1][$index]);
	    	    	}else{
	    	    		$sender->sendMessage("inventory-nospace");
	    	    		break 2;
	    	    	}
	    	    }
				$sender->sendMessage("trash-undo", count($all, COUNT_RECURSIVE) - 2);
	    	    break;
		}
		return true;
	}
	
}
		