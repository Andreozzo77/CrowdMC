<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use kenygamer\Core\tile\BrewingStand as BrewingStand;
use pocketmine\block\Block;
use pocketmine\block\BrewingStand as PMBrewingStand;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;

class BrewingStandBlock extends PMBrewingStand{
	
	/**
	 * @param Item $item
	 * @param Block $blockReplace
	 * @param Block $blockClicked
	 * @param int $face
	 * @param Vector3 $clickVector
	 * @param Player|null $player
	 * @return bool
	 */
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = \null): bool{
		$parent = parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
		if(!$blockReplace->getSide(Vector3::SIDE_DOWN)->isTransparent()){
			$nbt = new CompoundTag("", [
				new StringTag(Tile::TAG_ID, Tile::BREWING_STAND),
				new IntTag(Tile::TAG_X, (int)$this->x),
				new IntTag(Tile::TAG_Y, (int)$this->y),
				new IntTag(Tile::TAG_Z, (int)$this->z),
			]);
			$nbt->setInt(BrewingStand::TAG_BREW_TIME, BrewingStand::MAX_BREW_TIME);
			if($item->hasCustomName()){
				$nbt->setString("CustomName", $item->getCustomName());
			}
			new BrewingStand($player->getLevel(), $nbt);
		}

		return $parent;
	}
	
	/**
	 * @return int
	 */
	public function getLightLevel(): int{
		return 1;
	}

	/**
	 * @return float
	 */
	public function getBlastResistance(): float{
		return 2.5;
	}
	
	/**
	 * @param Item $item
	 * @param Player $player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = \null): bool{
		$parent = parent::onActivate($item, $player);
		$tile = $player->getLevel()->getTile($this);
		if($tile instanceof BrewingStand){
			$player->addWindow($tile->getInventory());
		}else{
			$nbt = new CompoundTag("", [
				new StringTag(Tile::TAG_ID, Tile::BREWING_STAND),
				new IntTag(Tile::TAG_X, (int)$this->x),
				new IntTag(Tile::TAG_Y, (int)$this->y),
				new IntTag(Tile::TAG_Z, (int)$this->z),
			]);
			$nbt->setInt(BrewingStand::TAG_BREW_TIME, BrewingStand::MAX_BREW_TIME);

			if($item->hasCustomName()){
				$nbt->setString("CustomName", $item->getCustomName());
			}
			$tile = new BrewingStand($player->getLevel(), $nbt);
			$player->addWindow($tile->getInventory());
		}

		return $parent;
	}
	
}