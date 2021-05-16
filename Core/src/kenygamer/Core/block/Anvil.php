<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use pocketmine\block\Anvil as PMAnvil;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\inventory\AnvilInventory as PMAnvilInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use kenygamer\Core\ElitePlayer;
use kenygamer\Core\listener\MiscListener2;

class Anvil extends PMAnvil{
	
	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$inventory = new AnvilInventory($this);
			MiscListener2::$usingAnvil[$player->getName()][0] = true;
			
			//$player->addWindow($inventory); //Returns PocketMine window ID
			if(!array_key_exists($windowId = ElitePlayer::HARDCODED_ANVIL_WINDOW_ID, $player->openHardcodedWindows)){
				$pk = new ContainerOpenPacket();
				$pk->windowId = $windowId;
				$pk->type = WindowTypes::ANVIL;
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

class AnvilInventory extends PMAnvilInventory{
	public function onClose(Player $who) : void{
		unset(MiscListener2::$usingAnvil[$who->getName()]);
	}
}