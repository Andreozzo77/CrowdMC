<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\inventory\SaveableInventory;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;

class InventoryCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"inventory",
			"Open your backup inventory",
			"/inventory",
			["inv"],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName("Backup Inventory");
        $inventory = SaveableInventory::createInventory("inv2_" . $sender->getName());
        $menu->getInventory()->setContents($inventory->getContents(true));
        $beforeCopyPlayer = $sender->getInventory()->getContents(true);
        $menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inv) use($inventory, $beforeCopyPlayer){
        	foreach($inventory->getContents(true) as $slot => $item){
        		if($slot > 26){
        			break;
        		}
        		$itemInNew = $inv->getItem($slot);
        		if(!$itemInNew->equalsExact($item) && $itemInNew->getId() !== 0){
        			$player->sendMessage("backupinv-error-takeout");
        			$player->getInventory()->setContents($beforeCopyPlayer); //Revert
        			return;
        		}
        	}
        	$inventory->setContents($inv->getContents());
        });
        $menu->send($sender);
		return true;
	}
	
}