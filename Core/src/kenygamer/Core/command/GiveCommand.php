<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\command\Command;
use pocketmine\lang\TranslationContainer;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\CompoundTag;
use pocketmine\utils\TextFormat;
use kenygamer\Core\util\ItemUtils;

class GiveCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"give",
			"Gives the specified player a certain amount of items",
			"/give <player> <item[:damage]> [amount] [tags...]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(count($args) < 2){
	    	return false;
	    }
	    $player = $sender->getServer()->getPlayer($args[0]);
	    if($player === null){
	    	$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.player.notFound"));
	    	return true;
	    }
	    try{
	    	/** @var Item|Item[] */
	    	$item = ItemUtils::get($args[1]);
	    }catch(\InvalidArgumentException $e){
	    	$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$args[1]]));
	    	return true;
	    }
	    
	    if(!is_array($item)){
	    	$items[] = $item;
	    }else{
	    	$items = $item;
	    }
	    foreach($items as $i){
	    	if(isset($args[2])){
	    		$i->setCount((int) $args[2]);
	    	}
	    	if(isset($args[3]) && count($items) === 1){
	    		$tags = $exception = null;
	    		$data = implode(" ", array_slice($args, 3));
	    		try{
	    			$tags = JsonNbtParser::parseJson($data);
	    		}catch(\Exception $ex){
	    			$exception = $ex;
	    		}
	    		if(!($tags instanceof CompoundTag) or $exception !== null){
	    			$sender->sendMessage(new TranslationContainer("commands.give.tagError", [$exception !== null ? $exception->getMessage() : "Invalid tag conversion"]));
	    			return true;
	    		}
	    		$i->setNamedTag($tags);
	    	}
	    	$player->getInventory()->addItem(clone $i);
	    	Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.give.success", [
	    	TextFormat::clean($i->getName()) . " (" . $i->getId() . ":" . $i->getDamage() . ")", (string) $i->getCount(), $player->getName()]));
	    }
		return true;
	}
	
}