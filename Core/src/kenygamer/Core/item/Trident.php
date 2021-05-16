<?php

namespace kenygamer\Core\item;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;

class Trident extends Item{
	
	public function __construct(int $meta = 0){
	   	parent::__construct(self::TRIDENT, $meta, "Trident");
	}
	
	public function getMaxStackSize() : int{
		return 1;
	}
	
	public function getMaxDurability(): int{
		return 251;
	}

	public function getAttackPoints(): int{
		return 8;
	}
}
