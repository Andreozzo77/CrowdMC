<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use pocketmine\item\Item;
use pocketmine\inventory\ArmorInventory as PMArmorInventory;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use kenygamer\Core\network\InventoryContentPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\inventory\InventoryEventProcessor;	
use pocketmine\entity\Living;

class ArmorInventoryAdapter extends PMArmorInventory{
	private $inventory;
	private $player;
	
	public function __construct($inventory){
		$this->inventory = $inventory;
		$this->player = $inventory->getHolder();
	
	}
	
	public function sendContents($target) : void{
		if($target instanceof Player){
			$target = [$target];
		}
	
		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->player->getId();
		$pk->head = $this->inventory->getHelmet();
		$pk->chest = $this->inventory->getChestplate();
		$pk->legs = $this->inventory->getLeggings();
		$pk->feet = $this->inventory->getBoots();
		$pk->encode();

		foreach($target as $player){
			if($player === $this->player){
				$pk2 = new InventoryContentPacket();
				$pk2->windowId = $player->getWindowId($this->inventory);
				$pk2->items = array_map([ItemStackWrapper::class, 'legacy'], $this->inventory->getContents(true));
				$player->dataPacket($pk2);
			}else{
				$player->dataPacket($pk);
			}
		}
	}
	
	public function getHolder() : Living{
		return $this->player;
	}

	public function getDefaultSize() : int{
		return 4;
	}

	public function getHelmet() : Item{
		return $this->inventory->getHelmet();
	}

	public function getChestplate() : Item{
		return $this->inventory->getChestplate();
	}

	public function getLeggings() : Item{
		return $this->inventory->getLeggings();
	}

	public function getBoots() : Item{
		return $this->inventory->getBoots();
	}

	public function setHelmet(Item $helmet) : bool{
		return $this->inventory->setHelmet($helmet);
	}

	public function setChestplate(Item $chestplate) : bool{
		return $this->inventory->setChestplate($chestplate);
	}

	public function setLeggings(Item $leggings) : bool{
		return $this->inventory->setLeggings($leggings);
	}

	public function setBoots(Item $boots) : bool{
		return $this->inventory->setBoots($boots);
	}
	
	public function getMaxStackSize() : int{
		return $this->inventory->getMaxStackSize();
	}

	public function setMaxStackSize(int $size) : void{
		$this->inventory->setMaxStackSize($size);
	}

	public function getName() : string{
		return $this->inventory->getName();
	}

	public function getTitle() : string{
		return $this->inventory->getTitle();
	}

	public function getItem(int $index) : Item{
		return $this->inventory->getItem($index);
	}

	public function setItem(int $index, Item $item, bool $send = true) : bool{
		return $this->inventory->setItem($index, $item, $send);
	}
	
	public function addItem(Item ...$slots) : array{
		echo "[adding]\n";
		return $this->inventory->addItem(...$slots);
	}
	public function canAddItem(Item $item) : bool{
		return $this->inventory->canAddItem($item);
	}

	
	public function removeItem(Item ...$slots) : array{
		return $this->inventory->removeItem(...$slots);
	}
	
	public function getContents(bool $includeEmpty = false) : array{
		return $this->inventory->getContents($includeEmpty);
	}

	public function setContents(array $items, bool $send = true) : void{
		$this->inventory->setContents($items, $send);
	}

	public function dropContents(Level $level, Vector3 $position) : void{
		$this->inventory->dropContents($level, $position);
	}

	public function sendSlot(int $index, $target) : void{
		$this->inventory->sendSlot($index, $target);
	}

	public function contains(Item $item) : bool{
		return $this->inventory->contains($item);
	}

	public function all(Item $item) : array{
		return $this->inventory->all($item);
	}
	
	public function first(Item $item, bool $exact = false) : int{
		return $this->inventory->first($item, $exact);
	}

	public function firstEmpty() : int{
		return $this->inventory->firstEmpty();
	}

	public function isSlotEmpty(int $index) : bool{
		return $this->inventory->isSlotEmpty($index);
	}

	public function remove(Item $item) : void{
		$this->inventory->remove($item);
	}

	public function clear(int $index, bool $send = true) : bool{
		return $this->inventory->clear($index, $send);
	}

	public function clearAll(bool $send = true) : void{
		$this->inventory->clearAll($send);
	}

	public function getViewers() : array{
		return $this->inventory->getViewers();
	}

	public function onOpen(Player $who) : void{
		$this->inventory->onOpen($who);
	}

	public function open(Player $who) : bool{
		return $this->inventory->open($who);
	}

	public function close(Player $who) : void{
		$this->inventory->close($who);
	}

	public function onClose(Player $who) : void{
		$this->inventory->onClose($who);
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void{
		$this->inventory->onSlotChange($index, $before, $send);
	}

	public function slotExists(int $slot) : bool{
		return $this->inventory->slotExists($slot);
	}

	public function getEventProcessor() : ?InventoryEventProcessor{
		return $this->inventory->getEventProcessor();
	}

	public function setEventProcessor(?InventoryEventProcessor $eventProcessor) : void{
		$this->inventory->setEventProcessor($eventProcessor);
	}

	}