<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use pocketmine\inventory\BaseInventory;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use kenygamer\Core\network\InventoryContentPacket;
use pocketmine\Player;

class TestInventory extends BaseInventory{
	
	public function getName() : string{
		return "Inventory";
	}
	
	public function getDefaultSize() : int{
		return 1;
	}
	
	public function sendContents($target) : void{
		
		if($target instanceof Player){
			$target = [$target];
		}
		if(count($target) === 0){
			return;
		}
		$target = array_values($target);
		$pk = new InventoryContentPacket();
		$pk->items = array_map([ItemStackWrapper::class, 'legacy'], $this->getContents(true));

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === ContainerIds::NONE){
				$this->close($player);
				continue;
			}
			$pk->windowId = $id;
			$player->dataPacket($pk);
		}
	}

}