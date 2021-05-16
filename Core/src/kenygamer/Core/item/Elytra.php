<?php

namespace kenygamer\Core\item;

use pocketmine\item\Item;
use pocketmine\item\Durable;

class Elytra extends Durable{
	
	public function __construct(int $meta = 0){
	   	parent::__construct(self::ELYTRA, $meta, "Elytra Wings");
	}
	
	public function getMaxStackSize() : int{
		return 1;
	}
	
	public function getMaxDurability(): int{
		return 433;
	}
}
