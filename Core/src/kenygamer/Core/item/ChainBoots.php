<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\ChainBoots as PMChainBoots;

class ChainBoots extends PMChainBoots{
	
	public function getMaxDurability() : int{
		return 600;
	}
}