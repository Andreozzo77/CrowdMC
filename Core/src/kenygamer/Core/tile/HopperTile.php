<?php

declare(strict_types=1);

namespace kenygamer\Core\tile;

use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;
use pocketmine\tile\Nameable;
use pocketmine\tile\NameableTrait;
use pocketmine\tile\Spawnable;

use kenygamer\Core\inventory\HopperInventory;
use kenygamer\Core\block\HopperBlock;

class HopperTile extends Spawnable implements InventoryHolder, Container, Nameable {
	use NameableTrait, ContainerTrait;

	/** @var HopperInventory */
	private $inventory = null;
	/** @var CompoundTag */
	private $nbt;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);

		$this->inventory = new HopperInventory($this);

		$this->loadItems($nbt);
		$this->scheduleUpdate();
	}

	protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): void{
		$nbt->setTag(new ListTag("Items", [], NBT::TAG_Compound));

		if($item !== null and $item->hasCustomName()){
			$nbt->setString("CustomName", $item->getCustomName());
		}
	}

	public function getRealInventory(){
		return $this->inventory;
	}

	public function getSize(): int{
		return 5;
	}

	public function getDefaultName(): string{
		return "Hopper";
	}

	public function addAdditionalSpawnData(CompoundTag $nbt): void{
		if($this->hasName()){
			$nbt->setTag($this->nbt->getTag("CustomName"));
		}
	}

	public function close(): void{
		if(!$this->isClosed()){
			foreach($this->getInventory()->getViewers() as $viewer){
				$viewer->removeWindow($this->getInventory());
			}

			parent::close();
		}
	}

	public function getInventory(){
		return $this->inventory;
	}

	public function onUpdate(): bool{
		if((Server::getInstance()->getTick() % 8) == 0){
			if(!($this->getBlock() instanceof HopperBlock)){
				return false;
			}
			// suck item entities
			$boundingBox = $this->getBlock()->getBoundingBox();
			$boundingBox->maxY += round(($boundingBox->maxY + 1), 0, PHP_ROUND_HALF_UP);
			foreach($this->getLevel()->getNearbyEntities($boundingBox) as $entity){
				if(!($entity instanceof ItemEntity) or !$entity->isAlive() or $entity->isFlaggedForDespawn() or $entity->isClosed()){
					continue;
				}

				$item = $entity->getItem();
				if($item instanceof Item){
					if($item->isNull()){
						$entity->kill();
						continue;
					}

					$itemClone = clone $item;
					$itemClone->setCount(1);
					if($this->inventory->canAddItem($itemClone)){
						$this->inventory->addItem($itemClone);
						$item->count--;
						if($item->getCount() <= 0){
							$entity->flagForDespawn();
						}
					}
				}
			}

			// suck items from container above it
			$source = $this->getLevel()->getTile($this->getBlock()->getSide(Vector3::SIDE_UP));
			if($source instanceof Container){ // follow vanilla rules
				$inventory = $source->getInventory();
				$firstOccupied = null;
				if(!($source instanceof BrewingStand)){
					for($index = 0; $index < $inventory->getSize(); $index++){
						if(!$inventory->getItem($index)->isNull()){
							$firstOccupied = $index;
							break;
						}
					}
				}else{
					if(!$source->brewing){
						for($index = 1; $index <= 3; $index++){
							if(!$inventory->getItem($index)->isNull()){
								$firstOccupied = $index;
								break;
							}
						}
					}
				}
				if($firstOccupied !== null){ // if changed from null
					$item = clone $inventory->getItem($firstOccupied);
					$item->setCount(1);
					if(!$item->isNull()){
						if($this->inventory->canAddItem($item)){
							$this->inventory->addItem($item);
							$inventory->removeItem($item);
							$inventory->sendContents($inventory->getViewers());
							if($source instanceof Chest){
								if($source->isPaired()){
									$pair = $source->getPair();
									$pInv = $pair->getInventory();
									$pInv->sendContents($pInv->getViewers());
								}
							}
						}
					}
				}
			}

			//TODO: Delay it
			// put items to target
			if(!($this->getLevel()->getTile($this->getBlock()->getSide(Vector3::SIDE_DOWN)) instanceof self)){ // vanilla way of doing it
				$target = $this->getLevel()->getTile($this->getBlock()->getSide($this->getBlock()->getDamage()));
				if($target instanceof Container){
					$inv = $target->getInventory();
					foreach($this->inventory->getContents() as $item){
						if($item->isNull()){
							continue;
						}
						$targetItem = clone $item;
						$targetItem->setCount(1);

						// Its now accurate
						if($inv instanceof DoubleChestInventory){
							/** @var $left ChestInventory */
							/** @var $right ChestInventory */
							$left = $inv->getLeftSide();
							$right = $inv->getRightSide();

							if($right->canAddItem($targetItem)){
								$inv = $right;
							}else{
								$inv = $left;
							}
						}

						if($inv->canAddItem($targetItem)){
							if(!($target instanceof BrewingStand)){
								$inv->addItem($targetItem);
								$this->inventory->removeItem($targetItem);
								$inv->sendContents($inv->getViewers());
							}
							if($target instanceof Chest){
								if($target->isPaired()){
									$pair = $target->getPair();
									$pInv = $pair->getInventory();
									$pInv->sendContents($pInv->getViewers());
								}
								break;
							}elseif($target instanceof BrewingStand){
								if(!$target->brewing){
									$remove = false;
									if($target->isValidIngredient($targetItem)){
										if($target->getInventory()->getIngredient()->isNull()){
											$target->getInventory()->setIngredient($targetItem);
											$this->inventory->removeItem($targetItem);
											$inv->sendContents($inv->getViewers());
											$target->scheduleUpdate();
											$remove = true;
										}
									}
									if($target->isValidFuel($targetItem)){
										if($target->getInventory()->getFuel()->isNull()){
											$target->getInventory()->setFuel($targetItem);
											$this->inventory->removeItem($targetItem);
											$inv->sendContents($inv->getViewers());
											$target->scheduleUpdate();
											$remove = true;
										}
									}
									if(!$target->getInventory()->getIngredient()->isNull() || $target->getInventory()->getIngredient()->equals($targetItem)){
										for($i = 1; $i <= 3; $i++){
											if($target->getInventory()->getItem($i)->isNull()){
												if($target->isValidMatch($target->getInventory()->getIngredient(), $targetItem)){
													$target->getInventory()->setItem($i, $targetItem);
													$inv->sendContents($inv->getViewers());
													$target->scheduleUpdate();
													$remove = true;
													break;
												}
											}
										}
									}
									if($remove){
										$this->inventory->removeItem($targetItem);
										$inv->sendContents($inv->getViewers());
									}
								}
							}else{
								break;
							}
						}
					}
				}
			}
		}

		return true;
	}

	public function saveNBT(): CompoundTag{
		$this->saveItems($this->nbt);

		return parent::saveNBT();
	}

	protected function readSaveData(CompoundTag $nbt): void{
		$this->nbt = $nbt;
	}

	protected function writeSaveData(CompoundTag $nbt): void{
	}
}