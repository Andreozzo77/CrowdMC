<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\command\utils\InvalidCommandSyntaxException;

class CeSplitCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"cesplit",
			"Split your custom enchant book to lower level books",
			"/cesplit <...level:quantity>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$item = $sender->getInventory()->getItemInHand();
	    if($item->getId() !== Item::ENCHANTED_BOOK || count($item->getEnchantments()) < 1){
			$sender->sendMessage("hold-enchantedbook");
	    	return true;
	    }
	    $enchantments = $item->getEnchantments();
	    $enchantment = array_shift($enchantments);
	    
	    $combinations = $args;
	    if(empty($combinations)){
			throw new InvalidCommandSyntaxException();
	    }
	    $books = [];
	    $levelRequired = 0;
	    foreach($combinations as $combination){
	    	$parts = explode(":", $combination);
	    	if(count($parts) !== 2 || !ctype_digit($parts[0]) || $parts[0] > $enchantment->getType()->getMaxLevel() || $parts[0] < 1 || !ctype_digit($parts[1]) || $parts[1] > 64 || $parts[1] < 1){
				return false;
	    	}
	    	list($level, $quantity) = $parts;
	    	
	    	for($i = 0; $i < $quantity; $i++){
	    		$b = ItemFactory::get(Item::BOOK, 0, 1);
	    		if($level == $enchantment->getLevel() && $quantity == 1){
					$sender->sendMessage("cesplit-same");
	    			return true;
	    		}
	    		$b = $this->getPlugin()->getPlugin("CustomEnchants")->addEnchantment($b, $enchantment->getType()->getId(), intval($level));
	    		$books[] = $b;
	    		$levelRequired += $level;
	    	}
	    	if($levelRequired > 0x7fffffff){
				return true;
			}
	        if($enchantment->getLevel() !== $levelRequired){
				$sender->sendMessage("cesplit-levelrequired", $this->getPlugin()->getPlugin("CustomEnchants")->getRomanNumber($levelRequired), $this->getPlugin()->getPlugin("CustomEnchants")->getRomanNumber($enchantment->getLevel()));
	        	return true;
	        }
	    }
	    if(count($books) < 1){
	    	return false;
	    }
	    if($this->getPlugin()->testSlot($sender, count($books))){
	    	$sender->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
	    	foreach($books as $book){
	    		$sender->getInventory()->addItem($book);
	    	}
			$sender->sendMessage("cesplit-done", count($books));
	    }
		return true;
	}
	
}