<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\ChainChestplate as PMChainChestplate;

class ChainChestplate extends PMChainChestplate{
	
	public function getMaxDurability() : int{
		return 600;
	}
	
}