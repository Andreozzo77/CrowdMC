<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener2;
use pocketmine\item\Item;
use pocketmine\nbt\JsonNbtParser;

class AddNbtCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"addnbt",
			"Add NBT to an entity / item",
			"/addnbt <entity/item> <...nbt>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset(MiscListener2::$addNbt[$sender->getName()])){
			unset(MiscListener2::$addNbt[$sender->getName()]);
	    	$sender->sendMessage("exitted");
	    	return true;
	    }
		$tags = null;
	    try{
	    	$tags = JsonNbtParser::parseJson(implode(" ", array_slice($args, 1)));
	    }catch(\Exception $e){
	    	$sender->sendMessage($e->getMessage());
	    	return true;
	    }
		switch($target = array_shift($args)){
			case "entity":
			    $RESERVED_TAGS = [
			    	"Pos", "Rotation", "Motion", "FallDistance", "Fire", "Air", "OnGround", "Invulnerable" 
				];
				break;
			case "item":
			    $RESERVED_TAGS = [
					"id", "Count", "Damage", Item::TAG_ENCH, Item::TAG_DISPLAY, Item::TAG_BLOCK_ENTITY_TAG
				];
				break;
		}
		foreach($tags->getValue() as $tag){
			if(in_array($tag->getName(), $RESERVED_TAGS)){
				$tags->removeTag($tag->getName());
			}
		}
		$sender->sendMessage($tags->toString());
		if($target === "item"){
			$item = $sender->getInventory()->getItemInHand();
			$nbt = $item->getNamedTag();
			foreach($tags->getValue() as $tag){
				$nbt->setTag($tag);
			}
		}else{
	    	MiscListener2::$addNbt[$sender->getName()] = $tags;
	    	$sender->sendMessage("tap-entity");
		}
		return true;
	}
	
}