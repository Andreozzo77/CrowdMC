<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use pocketmine\block\Block;
use pocketmine\block\EnchantingTable as PMEnchantingTable;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\inventory\EnchantInventory as PMEnchantInventory;
use pocketmine\item\Item;
use pocketmine\Player;

use kenygamer\Core\ElitePlayer;
use kenygamer\Core\listener\MiscListener2;

class EnchantingTable extends PMEnchantingTable{
	
	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$inventory = new EnchantInventory($this);
			
			//Count bookshelf
			$set = [];
			$bookshelf = 0;
			for($y = 0; $y <= 1; $y++){ //0-1
				for($x = -2; $x <= 2; $x++){
			   		for($z = -2; $z <= 2; $z++){
			   			if(abs($x) === 2 || abs($z) === 2){
			   				if($this->getLevel()->getBlockIdAt($this->x - $x, $this->y - $y, $this->z - $z) === Block::BOOKSHELF){
			   					$bookshelf++;
			   				}
			   			}
					}
				}
			}
			if($bookshelf > 15){
				$bookshelf = 15;
			}
			$base = \kenygamer\Core\Main::mt_rand(1, 8) + floor($bookshelf / 2) + \kenygamer\Core\Main::mt_rand(0, $bookshelf);
			$level = max($base / 3, 1); //Middle Slot (0-21)
			//$player->addWindow($inventory); //Returns PocketMine window ID
			
			//Set level
			
			if(!array_key_exists($windowId = ElitePlayer::HARDCODED_ENCHANTING_TABLE_WINDOW_ID, $player->openHardcodedWindows)){
				MiscListener2::$usingEnchantingTable[$player->getName()][0] = $level; //Set level
				$pk = new ContainerOpenPacket();
				$pk->windowId = $windowId;
				$pk->type = WindowTypes::ENCHANTMENT;
				$pk->x = $this->getFloorX();
				$pk->y = $this->getFloorY();
				$pk->z = $this->getFloorZ();
				$player->sendDataPacket($pk);
				$player->openHardcodedWindows[$windowId] = true;
			}
		}
		return true;
	}
}
/**
 * TODO: what does this do here
 */
class EnchantInventory extends PMEnchantInventory{
	public function onClose(Player $who) : void{
		unset(MiscListener2::$usingEnchantingTable[$who->getName()]);
	}
}