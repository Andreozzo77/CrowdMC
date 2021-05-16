<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\inventory\InventoryEventProcessor;
use pocketmine\inventory\PlayerInventory;
use pocketmine\Player;
use kenygamer\Core\network\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\inventory\SaveableInventory;
use kenygamer\Core\LangManager;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;

/**
 * A starkly hacky approach to send attractive popups when an item is added to the player's inventory.
 */
class ElitePlayerInventoryAdapter implements Inventory{
	/** @var PlayerInventory An Inventory held by another Inventory :laugh: */
	private $realInventory;
	
	public function __construct(PlayerInventory $realInventory){
		$this->realInventory = $realInventory;
		
		$this->realInventory->setSize($this->realInventory->getSize() + ($extraSlots = 5)); //36 + 5 = 41

        $property = (new \ReflectionClass($this->realInventory->getHolder()))->getProperty("namedtag");
        $property->setAccessible(true);
        $namedtag = $property->getValue($this->realInventory->getHolder());
		$inventoryTag = $namedtag->getListTag("Inventory");
		if($inventoryTag !== null){
			/** @var CompoundTag $item */
			foreach($inventoryTag as $i => $item){
				$slot = $item->getByte("Slot");
				if($slot >= 9 + $extraSlots && $slot < $this->realInventory->getSize() + 9){
					$this->realInventory->setItem($slot - 9, Item::nbtDeserialize($item));
				}
			}
		}

	}
	
	//Utils
	public static function getCapacityUsage(Inventory $inventory) : float{
		$slotsUsed = 0;
		foreach($inventory->getContents(true) as $item){
			if(!$item->isNull()){
				$slotsUsed++;
			}
		}
		return $slotsUsed / $inventory->getSize();
	}
	
	public function __call($function, $args){ // PlayerInventory methods
		return $this->realInventory->{$function}(...$args);
	}
	
	public function addItem(Item ...$slots) : array{
		$item = array_shift($slots);
		if($this->realInventory->getItemInHand()->hasEnchantment(CustomEnchantsIds::AUTOSTACK)){
			//Attempts to stack the item added with the other inventory items
			foreach($this->realInventory->getContents(true) as $slot => $item2){
				$result = MiscListener2::attemptStack($item, $item2);
				if($result !== false){
					$item = $result;
					$this->realInventory->setItem($slot, ItemFactory::get(Item::AIR));
				}
			}
		}
		array_unshift($slots, $item);
		$return = $this->realInventory->addItem(...$slots); // items that failed adding
		foreach($return as $item){
			foreach($slots as $i => $iitem){
				if($item->equalsExact($iitem)){
					unset($slots[$i]);
					break;
				}
			}
		}
		foreach($slots as $i => $slot){
			if(!$slot->isNull()){
				MiscListener2::$items[$this->getHolder()->getName()][] = [$slot, 0, self::getCapacityUsage($this->realInventory)];
			}
		}
		if(($errors = count($return) > 0)){
			$isFirstError = true;
			if($this->getHolder()->hasPermission("core.command.inventory")){
				$backup = SaveableInventory::createInventory("inv2_" . $this->getHolder()->getName());
				foreach($return as $item){
					if($backup->canAddItem($item)){
						if($isFirstError){ //Do not override
							$errors = false;
							$isFirstError = false;
						}
						$backup->addItem($item);
						MiscListener2::$items[$this->getHolder()->getName()][] = [$item, 1, self::getCapacityUsage($backup)];
						
						foreach($return as $index => $iitem){
							if($iitem->equalsExact($item)){
								unset($return[$index]);
								break;
							}
						}
						
					}else{
						$errors = true;
					}
				}
			}
		}
		if($errors){
			$this->getHolder()->sendPopup("\n\n\n" . LangManager::translate("inventory-nospace", $this->getHolder()));
		}
		return $return;
	}

	public function getSize() : int{
		return $this->realInventory->getSize();
	}

	public function getMaxStackSize() : int{
		return $this->realInventory->getMaxStackSize();
	}

	public function setMaxStackSize(int $size) : void{
		$this->realInventory->setMaxStackSize($size);
	}

	public function getName() : string{
		return $this->realInventory->getName();
	}

	public function getTitle() : string{
		return $this->realInventory->getTitle();
	}

	public function getItem(int $index) : Item{
		return $this->realInventory->getItem($index);
	}

	public function setItem(int $index, Item $item, bool $send = true) : bool{
		return $this->realInventory->setItem($index, $item, $send);
	}

	public function canAddItem(Item $item) : bool{
		$return = $this->realInventory->canAddItem($item);
		if(!$return){
			if($this->getHolder()->hasPermission("core.inventory")){
				$backup = SaveableInventory::createInventory("inv2_" . $this->getHolder()->getName());
				$return = $backup->canAddItem($item);
			}
		}
		if(!$return){
			$this->getHolder()->sendPopup("\n\n\n" . LangManager::translate("inventory-nospace", $this->getHolder()));
		}
		return $return;
	}

	public function removeItem(Item ...$slots) : array{
		return $this->realInventory->removeItem(...$slots);
	}
	
	public function sendContents($target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new InventoryContentPacket();
		$pk->items = [];
		foreach($this->realInventory->getContents(true) as $item){
			try{
				$itemStackWrapper = ItemStackWrapper::legacy($item);
			}catch(\InvalidArgumentException $e){
				$itemStackWrapper = ItemStackWrapper::legacy(ItemFactory::get(Item::AIR));
				//Beetroot seeds
				var_dump($e->getMessage());
				continue;
			}
			$pk->items[] = $itemStackWrapper;
		}
		foreach($target as $player){
			if(($id = $player->getWindowId($this->realInventory)) === ContainerIds::NONE){
				$this->realInventory->close($player);
				continue;
			}
			$pk->windowId = $id;
			$player->dataPacket($pk);
		}
    }

	public function getContents(bool $includeEmpty = false) : array{
		return $this->realInventory->getContents($includeEmpty);
	}

	public function setContents(array $items, bool $send = true) : void{
		$this->realInventory->setContents($items, $send);
	}

	public function dropContents(Level $level, Vector3 $position) : void{
		$this->realInventory->dropContents($level, $position);
	}

	public function sendSlot(int $index, $target) : void{
		$this->realInventory->sendSlot($index, $target);
	}

	public function contains(Item $item) : bool{
		return $this->realInventory->contains($item);
	}

	public function all(Item $item) : array{
		return $this->realInventory->all($item);
	}
	
	public function first(Item $item, bool $exact = false) : int{
		return $this->realInventory->first($item, $exact);
	}

	public function firstEmpty() : int{
		return $this->realInventory->firstEmpty();
	}

	public function isSlotEmpty(int $index) : bool{
		return $this->realInventory->isSlotEmpty($index);
	}

	public function remove(Item $item) : void{
		$this->realInventory->remove($item);
	}

	public function clear(int $index, bool $send = true) : bool{
		return $this->realInventory->clear($index, $send);
	}

	public function clearAll(bool $send = true) : void{
		$this->realInventory->clearAll($send);
	}

	public function getViewers() : array{
		return $this->realInventory->getViewers();
	}

	public function onOpen(Player $who) : void{
		$this->realInventory->onOpen($who);
	}

	public function open(Player $who) : bool{
		return $this->realInventory->open($who);
	}

	public function close(Player $who) : void{
		$this->realInventory->close($who);
	}

	public function onClose(Player $who) : void{
		$this->realInventory->onClose($who);
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void{
		$this->realInventory->onSlotChange($index, $before, $send);
	}

	public function slotExists(int $slot) : bool{
		return $this->realInventory->slotExists($slot);
	}

	public function getEventProcessor() : ?InventoryEventProcessor{
		return $this->realInventory->getEventProcessor();
	}

	public function setEventProcessor(?InventoryEventProcessor $eventProcessor) : void{
		$this->realInventory->setEventProcessor($eventProcessor);
	}
}