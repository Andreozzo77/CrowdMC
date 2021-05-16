<?php


declare(strict_types=1);

namespace kenygamer\Core\inventory;

use kenygamer\Core\tile\BeaconTile;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\inventory\InventoryHolder;

class BeaconInventory extends ContainerInventory{
	/**
	 * @param Beacon $tile
	 */
	public function __construct(BeaconTile $tile){
		parent::__construct($tile);
	}
	
	/**
	 * @return int
	 */
	public function getNetworkType() : int{
		return WindowTypes::BEACON;
	}
	
	/**
	 * @return string
	*/
	public function getName() : string{
		return "Beacon";
	}
	
	/**
	 * @return int
	 */
	public function getDefaultSize(): int{
		return 1;
	}
	
	/**
	 * @return InventoryHolder
	 */
	public function getHolder() : InventoryHolder{
		return $this->holder;
	}
	
}