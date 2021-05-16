<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use kenygamer\Core\Main;
use kenygamer\Core\tile\ShulkerBoxTile;
use pocketmine\block\Block;
use pocketmine\tile\Tile;
use pocketmine\block\BlockToolType;
use pocketmine\block\Transparent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Container;

class ShulkerBox extends Transparent{
	
	/**
	 * @param int $id
	 * @param int $meta
	 */
	public function __construct(int $id = self::SHULKER_BOX, int $meta = 0){
		$this->id = $id;
		$this->meta = $meta;
	}
	
	/**
	 * @return float
	 */
	public function getResistance() : float{
		return 30;
	}
	
	/**
	 * @return float
	 */
	public function getHardness() : float{
		return 2;
	}
	
	/**
	 * @return int
	 */
	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}
	
	/**
	 * @return string
	 */
	public function getName() : string{
		return "Shulker Box";
	}
	
	/**
	 * @param Item $item
	 * @param Block $blockReplace
	 * @param Block$blockClicked
	 * @param int $face
	 * @param Vector3 $clickVector
	 * @param Player $player
	 * @return bool
	 */
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$this->getLevel()->setBlock($blockReplace, $this, true, true);
		
		$nbt = ShulkerBoxTile::createNBT($this, $face, $item, $player);
		
		$items = $item->getNamedTag()->getTag(Container::TAG_ITEMS);
		if($items !== null){
			$nbt->setTag($items);
		}
		Tile::createTile("ShulkerBoxTile", $this->getLevel(), $nbt);

		($inv = $player->getInventory())->clear($inv->getHeldItemIndex()); // TODO: We need PMMP to be able to set max stack size in blocks... ree
		return true;
	}
	
	/**
	 * @param Item $item
	 * @param Player $player
	 * @return bool
	 */
	public function onBreak(Item $item, Player $player = null) : bool{
		/** @var TileShulkerBox $t */
		$t = $this->getLevel()->getTile($this);
		if($t instanceof ShulkerBoxTile){
			$item = ItemFactory::get($this->id, $this->id != self::UNDYED_SHULKER_BOX ? $this->meta : 0, 1);
			$itemNBT = clone $item->getNamedTag();
			$itemNBT->setTag($t->getCleanedNBT()->getTag(Container::TAG_ITEMS));
			$item->setNamedTag($itemNBT);
			$this->getLevel()->dropItem($this->add(0.5,0.5,0.5), $item);

			$t->getInventory()->clearAll();
		}
		$this->getLevel()->setBlock($this, Block::get(Block::AIR), true, true);

		return true;
	}
	
	/**
	 * @param Item $item
	 * @param Player Player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$t = $this->getLevel()->getTile($this);
			if(!($t instanceof ShulkerBoxTile)){
				$t = Tile::createTile("ShulkerBoxTile", $this->getLevel(), TileShulkerBox::createNBT($this));
			}
			if(!$this->getSide(Vector3::SIDE_UP)->isTransparent() || !$t->canOpenWith($item->getCustomName()) || ($player->isCreative())){
				return true;
			}
			$player->addWindow($t->getInventory());
		}

		return true;
	}
	
	/**
	 * @param Item $item
	 * @return Item[]
	 */
	public function getDrops(Item $item) : array{
		return [];
	}
	
}