<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\ChainHelmet as PMChainHelmet;

class ChainHelmet extends PMChainHelmet{
	
	public function getMaxDurability() : int{
		return 600;
	}
}