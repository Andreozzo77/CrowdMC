<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\task\FFATask;
use pocketmine\Player;

class FfaCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"ffa",
			"Ffa Command",
			"/ffa",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if($this->getPlugin()->ffaWinner !== $sender->getName()){
	    	$sender->sendMessage("ffa-nowinner");
	    	return true;
	    }
	    $items = [
	        [ItemUtils::get("legendary_key")->setCount(3), ItemUtils::get("rare_book")->setCount(1), ItemUtils::get("atlas_crate")->setCount(2)],
	        [ItemUtils::get("casino_coin")->setCount(2), ItemUtils::get("bank_note(30000000)"), ItemUtils::get("hestia_crate")],
	        [ItemUtils::get("casino_coin")->setCount(3), ItemUtils::get("bank_note(10000000)"), ItemUtils::get("mythic_book")->setCount(1)],
	        [ItemUtils::get("casino_coin")->setCount(3), ItemUtils::get("bank_note(5000000)"), ItemUtils::get("lucky_block")->setCount(16)],
	        [ItemUtils::get("casino_coin")->setCount(2), ItemUtils::get("bank_note(1000000)"), ItemUtils::get("healing_cookie")]
	    ];
		$descriptions = [];
		foreach($items as $i => $item){
			$descriptions[$i] = ItemUtils::getDescription($item);
		}
		$rewards = array_combine($descriptions, $items);
	    
	    $form = new SimpleForm(function(Player $player, ?int $index) use($rewards){
	    	if($index !== null && $index < count($rewards)){
	    		if($this->getPlugin()->testSlot($player, 3)){
	    			$indexes = array_values($rewards);
	    			$items = $indexes[$index];
	    			foreach($items as $item){
	    				$player->getInventory()->addItem($item);
	    			}
	    			$descs = array_keys($rewards);
	    			$desc = $descs[$index];
	    			LangManager::send("ffa-claimed", $player, $desc);
	    			$this->getPlugin()->ffaWinner = "";
	    		}
	    	}
	    });
	    $form->setTitle(LangManager::translate("ffa-pick", $sender));
	    $form->setContent(LangManager::translate("ffa-select", $sender));
	    foreach($rewards as $desc => $items){
	    	$form->addButton(LangManager::translate("ffa-reward", $sender, $desc));
	    }
	    $sender->sendForm($form);
		return true;
	}
	
}