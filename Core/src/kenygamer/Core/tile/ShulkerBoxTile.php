<?php

declare(strict_types=1);

namespace kenygamer\Core\tile;

use kenygamer\Core\inventory\ShulkerBoxInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;
use pocketmine\tile\Nameable;
use pocketmine\tile\NameableTrait;
use pocketmine\tile\Spawnable;

class ShulkerBoxTile extends Spawnable implements InventoryHolder, Container, Nameable{
	use NameableTrait, ContainerTrait;

	/** @var ShulkerBoxInventory */
	protected $inventory;

	public function getDefaultName() : string{
		return "Shulker Box";
	}

	public function close() : void{
		if(!$this->isClosed()){
			$this->inventory->removeAllViewers(true);
			$this->inventory = null;
			parent::close();
		}
	}

	public function getRealInventory() : ShulkerBoxInventory{
		return $this->inventory;
	}

	public function getInventory() : ShulkerBoxInventory{
		return $this->inventory;
	}

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->loadName($nbt);
		$this->inventory = new ShulkerBoxInventory($this);
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveName($nbt);
		$this->saveItems($nbt);
	}
	
}