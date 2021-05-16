<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use kenygamer\Core\map\MapData;
use kenygamer\Core\map\MapFactory;
use kenygamer\Core\util\Utils;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EmptyMap extends Item{
	public const TYPE_EXPLORER_PLAYER = 2;

	/**
	 * @param int $meta
	 */
	public function __construct(int $meta = 0){
		parent::__construct(self::EMPTY_MAP, $meta, "Empty Map");
	}
	
	/**
	 * @param Player $player
	 * @param Vector3 $directionVector
	 */
	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$map = ItemFactory::get(Item::FILLED_MAP, 0, 1);
		$map->setDisplayPlayers($this->meta === self::TYPE_EXPLORER_PLAYER);
		$map->setMapId(MapFactory::getInstance()->nextId());

		$colors = [];
		for($x = 0; $x < 128; $x++){
			for($y = 0; $y < 128; $y++){
				$realX = $player->getFloorX() - 64 + $x;
				$realY = $player->getFloorZ() - 64 + $y;
				$maxY = $player->getLevel()->getHighestBlockAt($realX, $realY);
				$block = $player->getLevel()->getBlockAt($realX, $maxY, $realY);
				$color = Utils::getMapColorByBlock($block);
				$colors[$y][$x] = $color;
			}
		}
		MapFactory::getInstance()->registerData(new MapData($map->getMapId(), $colors, $map->getDisplayPlayers(), $player->floor()));
		if($player->getInventory()->canAddItem($map)){
			$player->getInventory()->addItem($map);
		}else{
			$player->getLevel()->dropItem($player->floor()->add(0.5, 0.5, 0.5), $map);
		}
		$this->pop();
		return true;
	}
	
}