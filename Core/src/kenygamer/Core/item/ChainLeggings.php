<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\ChainLeggings as PMChainLeggings;

class ChainLeggings extends PMChainLeggings{
	
	public function getMaxDurability() : int{
		return 600;
	}
}