<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\ItemIds;
use pocketmine\item\Tool;

class Shield extends Tool{

	public function __construct(int $meta = 0){
		parent::__construct(ItemIds::SHIELD, $meta, "Shield");
	}

	public function getMaxDurability() : int{
		return 337;
	}
	
}