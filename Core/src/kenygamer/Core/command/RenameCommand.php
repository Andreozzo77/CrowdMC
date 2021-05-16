<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
	
class RenameCommand extends BaseCommand{
	/** @var int */
	private $cost = 0;
	
	public function __construct(){
		parent::__construct(
			"rename",
			"Rename an item",
			"/rename",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
		$this->cost = $this->getPlugin()->getConfig()->get("rename-cost");
	}
	
	protected function onExecute($sender, array $args) : bool{
		$item = $sender->getInventory()->getItemInHand();
        if($item->getId() === Item::AIR){
			$sender->sendMessage("hold-item");
        	return true;
        }
		$RESERVED_ITEMS = [
			Item::TRIPWIRE_HOOK, Item::NETHER_STAR, Item::ENCHANTED_BOOK, Item::SLIME_BALL
		];
        if(in_array($item->getId(), $RESERVED_ITEMS)){
			$sender->sendMessage("rename-itembanned");
        	return true;
        }
        if($sender->getCurrentTotalXp() < $this->cost){
			$sender->sendMessage("exp-needed", $this->cost);
        	return true;
        }
        if(!isset($args[0])){
			$sender->sendMessage("rename-noname");
        	return true;
        }
		$name = implode(" ", $args);
        if(preg_match("/\\n|\n|§k/", $name)){
			$sender->sendMessage("rename-invalidname");
        	return true;
        }
        if(!preg_match("/[a-zA-Z0-9 §]/", $name)){
			$sender->sendMessage("rename-symbols");
        	return true;
        }
        if(strlen(TextFormat::clean($name)) > 16){
			$sender->sendMessage("rename-toolong");
        	return true;
        }
        $new_name = explode("\n", $item->getName());
        $new_name[0] = $name;
        $item->setCustomName($new_name = implode("\n", $new_name));
        $sender->getInventory()->setItemInHand($item);
        $sender->subtractXp($this->cost);
		$sender->sendMessage("rename-renamed", TextFormat::clean($new_name));
        return true;
	}
	
}