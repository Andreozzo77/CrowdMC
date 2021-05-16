<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use kenygamer\Core\LangManager;

class HeadhuntingCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"headhunting",
			"Headhunting Command",
			"/headhunting [sell]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		switch($args[0] ?? null){
	    	case "sell":
	    	    $value = 0;
	    	    $count = 0;
	    	    foreach($sender->getInventory()->getContents() as $item){
	    	    	if($item->getId() === Item::SKULL){
	    	    		$c = $item->getCount();
	    	    		if($item->getDamage() === 3){ //Human Mob Head
	    	    		    $value += $c; //1
	    	    		    $count += $c;
	    	    		    $sender->getInventory()->removeItem($item);
	    	    		}elseif($item->getDamage() === 0 && $item->getNamedTag()->hasTag("EntityId", IntTag::class)){
	    	    			$spawnerName = $this->getPlugin()->getSpawnerName($item->getNamedTag()->getInt("EntityId"));
	    	    			if(is_string($spawnerName)){
	    	    				$value += $this->getPlugin()->spawners[$spawnerName]["headhunting"] * $c;
	    	    				$count += $c;
	    	    				$sender->getInventory()->removeItem($item);
	    	    			}
	    	    		}
	    	    	}
	    	    }
	    	    if($value > 0){
	    	    	/** @var int */
	    	    	$old = $this->getPlugin()->getAffordableSpawner($sender);
	    	    	
	    	    	if($this->getPlugin()->addHeadhuntingXp($sender, $value, $level)){
	    	    		$sender->addTitle(LangManager::translate("headhunting-levelup"), LangManager::translate("headhunting-levelup2", $level));
	    	    		/** @var int */
	    	    		$new = $this->getPlugin()->getAffordableSpawner($sender);
	    	    		
	    	    		if($new > $old){
	    	    			$spawnerName = array_keys($this->getPlugin()->spawners)[$new];
			    			$sender->sendMessage("headhunting-unlocked", $spawnerName);
			    			break;
			    		}
			    	}
			    	$sender->sendMessage("headhunting-sell", number_format($count), number_format($value));
	    	    }else{
	    	    	$sender->sendMessage("headhunting-sell-none");
	    	    }
	    	    break;
	    	default:
				$sender->sendMessage("headhunting", $this->getPlugin()->getHeadhuntingLevel($sender));
		}
		return true;
	}
	
}