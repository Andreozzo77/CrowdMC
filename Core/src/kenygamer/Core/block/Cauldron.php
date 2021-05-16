<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use pocketmine\block\Solid;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\Player;

final class Cauldron extends Solid{
	protected $id = self::CAULDRON_BLOCK;
	
	/**
	 * @param Item $item
	 * @param Player $player
	 */
	public function onActivate(Item $item, Player $player = null) : bool{
		return true;
	}
	
	/**
	 * @param int $meta
	 */
	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}
	
	public function getName() : string{
		return "Cauldron";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_STONE;
	}

	public function getHardness() : float{
		return 2;
	}
	
}