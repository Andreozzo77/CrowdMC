<?php

declare(strict_types=1);

namespace kenygamer\Core\tile;


use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;
use pocketmine\tile\Nameable;
use pocketmine\tile\NameableTrait;
use pocketmine\tile\Spawnable;
use kenygamer\Core\inventory\BrewingInventory;
use kenygamer\Core\brewing\BrewingManager;

class BrewingStand extends Spawnable implements InventoryHolder, Container, Nameable{
	use NameableTrait, ContainerTrait;

	public const TAG_BREW_TIME = "BrewTime";
	public const TAG_FUEL = "Fuel";
	public const TAG_HAS_BOTTLE_0 = "brewing_stand_slot_a_bit";
	public const TAG_HAS_BOTTLE_1 = "brewing_stand_slot_b_bit";
	public const TAG_HAS_BOTTLE_2 = "brewing_stand_slot_c_bit";

	private const TAG_HAS_BOTTLE_BASE = "has_bottle_";

	public const MAX_BREW_TIME = 400;
	public const MAX_FUEL = 20;
	
	public const INGREDIENTS = [
		Item::NETHER_WART,
		Item::GLOWSTONE_DUST,
		Item::REDSTONE,
		Item::FERMENTED_SPIDER_EYE,
		Item::MAGMA_CREAM,
		Item::SUGAR,
		Item::GLISTERING_MELON,
		Item::SPIDER_EYE,
		Item::GHAST_TEAR,
		Item::BLAZE_POWDER,
		Item::GOLDEN_CARROT,
		Item::PUFFERFISH,
		Item::RABBIT_FOOT,
		Item::GUNPOWDER,
		Item::DRAGON_BREATH
	];
	
	/** @var bool */
	public $brewing = false;
	/** @var CompoundTag */
	private $nbt;
	/** @var BrewingInventory */
	private $inventory = null;
	
	/**
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		if($nbt->hasTag(self::TAG_BREW_TIME, ShortTag::class)){
			$nbt->removeTag(self::TAG_BREW_TIME);
		}
		if($nbt->hasTag(self::TAG_FUEL, IntTag::class)){
			$nbt->removeTag(self::TAG_FUEL);
		}
		if(!$nbt->hasTag(self::TAG_BREW_TIME, IntTag::class)){
			$nbt->setInt(self::TAG_BREW_TIME, 0);
		}
		if(!$nbt->hasTag(self::TAG_FUEL, ByteTag::class)){
			$nbt->setByte(self::TAG_FUEL, 0);
		}

		$this->inventory = new BrewingInventory($this);
		$this->loadItems($nbt);
		$this->scheduleUpdate();
	}
	
	/**
	 * @return BrewingInventory
	 */
	public function getRealInventory() : BrewingInventory{
		return $this->inventory;
	}
	
	/**
	 * @return string
	 */
	public function getDefaultName(): string{
		return "Brewing Stand";
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	public function addAdditionalSpawnData(CompoundTag $nbt): void{
		$nbt->setShort(self::TAG_BREW_TIME, self::MAX_BREW_TIME);
	}
	
	/**
	 * @param Item $item
	 * @return bool
	 */
	public function isValidFuel(Item $item): bool{
		return ($item->getId() == Item::BLAZE_POWDER && $item->getDamage() == 0);
	}
	
	/**
	 * @param Item $ingredient
	 * @param Item $potion
	 * @return bool
	 */
	public static function isValidMatch(Item $ingredient, Item $potion): bool{
		$recipe = BrewingManager::getInstance()->matchBrewingRecipe($ingredient, $potion);
		return $recipe !== null;
	}

	public function onUpdate(): bool{
		if($this->isClosed()){
			return false;
		}

		$return = $consumeFuel = $canBrew = false;

		$this->timings->startTiming();

		$fuel = $this->getInventory()->getFuel();
		$ingredient = $this->getInventory()->getIngredient();

		for($i = 1; $i <= 3; $i++){
			$hasBottle = false;
			$currItem = $this->inventory->getItem($i);
			if($this->isValidPotion($currItem)){
				$canBrew = true;
				$hasBottle = true;
			}
			$this->setBottle($i - 1, $hasBottle);
		}

		if($this->getFuelValue() > 0){
			$canBrew = true;
			$this->broadcastFuelAmount($this->getFuelValue());
			$this->broadcastFuelTotal(self::MAX_FUEL);
		}else{
			if(!$fuel->isNull()){
				if($fuel->equals(Item::get(Item::BLAZE_POWDER, 0), true, false)){
					$consumeFuel = true;
					$canBrew = true;
				}
			}else{
				$canBrew = false;
			}
		}

		if(!$ingredient->isNull() && $canBrew){
			if($canBrew && $this->isValidIngredient($ingredient)){
				foreach($this->inventory->getPotions() as $potion){
					$recipe = BrewingManager::getInstance()->matchBrewingRecipe($ingredient, $potion);
					if($recipe !== null){
						$canBrew = true;
						break;
					}
					$canBrew = false;
				}
			}
		}else{
			$canBrew = false;
		}
		$this->broadcastFuelAmount(self::MAX_FUEL);
		//$this->broadcastBrewTime(10);
		//var_dump(compact("canBrew", "consumeFuel", "return"));
		//$canBrew = true;
		if($canBrew){
			if($consumeFuel){
				$fuel->count--;
				if($fuel->getCount() <= 0){
					$fuel = Item::get(Item::AIR);
				}
				$this->inventory->setFuel($fuel);
				$this->setFuelValue(self::MAX_FUEL);
				$this->broadcastFuelAmount(self::MAX_FUEL);
			}
			$return = true;
			$brewTime = $this->getBrewTime();
			$brewTime -= 1;
			$this->setBrewTime($brewTime);
			$this->brewing = true;

			$this->broadcastBrewTime($brewTime);
			$this->broadcastFuelTotal(self::MAX_FUEL);

			if($brewTime <= 0){
				//echo "[BrewingStand] " . $this->asPosition()->__toString() . ": brewTime < 0" . PHP_EOL;
				for($i = 1; $i <= 3; $i++){
					$hasBottle = false;
					$potion = $this->inventory->getItem($i);
					$recipe = BrewingManager::getInstance()->matchBrewingRecipe($ingredient, $potion);
					if($recipe != null and !$potion->isNull()){
						//echo "[BrewingStand] " . $this->asPosition()->__toString() . ": recipe != null and !potion->isNull()" . PHP_EOL;
						$this->inventory->setItem($i, $recipe->getResult());
						$hasBottle = true;
					}
					$this->setBottle($i - 1, $hasBottle);
				}
				$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_POTION_BREWED);
				$ingredient->count--;
				if($ingredient->getCount() <= 0){
					$ingredient = Item::get(Item::AIR);
				}
				$this->inventory->setIngredient($ingredient);
				$this->saveItems($this->nbt);

				$fuelAmount = max($this->getFuelValue() - 1, 0);
				$this->setFuelValue($fuelAmount);
				$this->broadcastFuelAmount($fuelAmount);
				$this->brewing = false;
			}
		}else{
			echo "FULL BREW TIME\n";
			$this->setBrewTime(self::MAX_BREW_TIME);
			$this->broadcastBrewTime(0);
			$this->brewing = false;
		}

		if($return){
			$this->inventory->sendContents($this->inventory->getViewers());
			$this->onChanged();
		}

		$this->timings->stopTiming();
var_dump($this->nbt->__toString());
		return $return;
	}

	public function getInventory(){
		return $this->inventory;
	}

	public function isValidPotion(Item $item): bool{
		return (in_array($item->getId(), [Item::POTION, Item::SPLASH_POTION]));
	}

	public function setBottle(int $slot, bool $hasBottle): void{
		switch($slot){
			case 0:
				$tag = self::TAG_HAS_BOTTLE_0;
				break;
			case 1:
				$tag = self::TAG_HAS_BOTTLE_1;
				break;
			case 2:
				$tag = self::TAG_HAS_BOTTLE_2;
				break;
			default:
				throw new \InvalidArgumentException("Slot must be in the range of 0-2.");
		}
		$this->nbt->setInt($tag, intval($hasBottle));
	}
	
	/**
	 * @return int
	 */
	public function getFuelValue(): int{
		return $this->nbt->getByte(self::TAG_FUEL, 0);
	}
	
	/**
	 * @param int $value
	 */
	public function broadcastFuelAmount(int $value): void{
		echo "BROADCASTING $value FUEL AMOUNT\n";
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_AMOUNT;
		$pk->value = $value;
		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}
	
	/**
	 * @param int $value
	 */
	public function broadcastFuelTotal(int $value): void{
		echo "BROADCAST FUEL TOTAL $value\n";
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_TOTAL;
		$pk->value = $value;
		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}
	
	/**
	 * @param Item $item
	 */
	public function isValidIngredient(Item $item): bool{
		return (in_array($item->getId(), self::INGREDIENTS) && $item->getDamage() == 0);
	}
	
	/**
	 * @param int $fuel
	 */
	public function setFuelValue(int $fuel): void{
		$this->nbt->setByte(self::TAG_FUEL, $fuel);
	}
	
	/**
	 * @return int
	 */
	public function getBrewTime(): int{
		return $this->nbt->getInt(self::TAG_BREW_TIME);
	}

	/**
	 * @param int $time
	 */
	public function setBrewTime(int $time): void{
		$this->nbt->setInt(self::TAG_BREW_TIME, $time);
	}
	
	/**
	 * @param int $time
	 */
	public function broadcastBrewTime(int $time): void{
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_BREW_TIME;
		$pk->value = $time;
		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}
	
	/**
	 * @return CompoundTag
	 */
	public function saveNBT(): CompoundTag{
		$this->saveItems($this->nbt);

		return parent::saveNBT();
	}

	public function loadBottles(): void{
		$this->loadItems($this->nbt);
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	protected function readSaveData(CompoundTag $nbt): void{
		$this->nbt = $nbt;
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	protected function writeSaveData(CompoundTag $nbt): void{
		$nbt->setShort(self::TAG_BREW_TIME, self::MAX_BREW_TIME);
	}
	
}