<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use kenygamer\Core\tile\BrewingStand;
use pocketmine\inventory\ContainerInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class BrewingInventory extends ContainerInventory{
	public const SLOT_INGREDIENT = 0;
	public const SLOT_LEFT = 1;
	public const SLOT_MIDDLE = 2;
	public const SLOT_RIGHT = 3;
	public const SLOT_FUEL = 4;
	
	/** @var BrewingStand */
	protected $holder;

	public function __construct(BrewingStand $holder, array $items = [], int $size = \null, string $title = \null){
		parent::__construct($holder, $items, $size, $title);
	}
	
	/**
	 * @return int
	 */
	public function getDefaultSize(): int{
		return 5;
	}
	
	/**
	 * @return string
	 */
	public function getName(): string{
		return "Brewing";
	}
	
	/**
	 * @return int
	 */
	public function getNetworkType(): int{
		return WindowTypes::BREWING_STAND;
	}
	
	/**
	 * @param int $index
	 * @param Item $before
	 * @param bool $send
	 */
	public function onSlotChange(int $index, Item $before, bool $send): void{
		$this->holder->scheduleUpdate();
		parent::onSlotChange($index, $before, $send);
	}
	
	/**
	 * @return Item
	 */
	public function getIngredient(): Item{
		return $this->getItem(self::SLOT_INGREDIENT);
	}
	
	/**
	 * @param Item $item
	 */
	public function setIngredient(Item $item): void{
		$this->setItem(self::SLOT_INGREDIENT, $item, true);
	}

	/**
	 * @return Item[]
	 */
	public function getPotions(): array{
		$return = [];
		for($i = 1; $i <= 3; $i++){
			$return[] = $this->getItem($i);
		}

		return $return;
	}

	public function onClose(Player $who): void{
		parent::onClose($who);
		$this->holder->saveNBT();
	}

	public function onOpen(Player $who): void{
		parent::onOpen($who);
		$this->holder->loadBottles();
	}

	public function getFuel(): Item{
		return $this->getItem(self::SLOT_FUEL);
	}

	public function setFuel(Item $fuel): void{
		$this->setItem(self::SLOT_FUEL, $fuel);
	}
}
