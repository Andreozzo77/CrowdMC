<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use muqsit\invmenu\InvMenu;

class BragHouseCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"braghouse",
			"Opens the brag house",
			"/braghouse",
			["bh"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(empty(Main::$bragHouse)){
			$sender->sendMessage("brag-norecent");
	    	return true;
	    }
	    $brags = Main::$bragHouse;
	    $form = new SimpleForm(function(Player $player, ?string $brag) use($brags){
	    	if(!is_string($brag)){
	    		return;
	    	}
	    	if(isset($brags[$brag])){
	    		$bragger = $brags[$brag]["player"];
	    		$items = $brags[$brag]["items"];
				$player->sendMessage("brag-viewing", $bragger);
	    		if(count($items) > 1){
	    			$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
	    			$menu->setName(LangManager::send("brag", $player, $bragger, LangManager::translate("brag-inventory", $player)));
	    		}else{
	    			$menu = InvMenu::create(InvMenu::TYPE_HOPPER);
	    			$menu->setName(LangManager::send("brag", $player, $bragger, LangManager::translate("brag-item", $player)));
	    		}
	    		$menu->getInventory()->setContents($items);
	    		$menu->setListener(InvMenu::readonly());
				/*$menu->setListener(function(Player $player, \pocketmine\item\Item $itemClicked, \pocketmine\item\Item $itemClickedWith, \pocketmine\inventory\transaction\action\SlotChangeAction $action){
					var_dump($itemClicked);
					var_dump($itemClickedWith);
					var_dump($action);
				});*/
	    		$menu->send($player);
	    	}
	    });
	    $form->setTitle(LangManager::translate("brag-prefix", $sender));
	    $form->setContent(LangManager::translate("brag-latest", $sender));
	    foreach($brags as $i => $brag){
	    	$form->addButton(LangManager::translate("brag", $sender, $brag["player"], (count($brag["items"]) > 1 ? LangManager::translate("brag-inventory", $sender) : LangManager::translate("brag-item", $sender))), -1, "", strval($i));
	    }
	    $sender->sendForm($form);
	    return true;
	}
	
}