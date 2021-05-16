<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\Armor;

class NetheriteChestplate extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::NETHERITE_CHESTPLATE, $meta, "Netherite Chestplate");
	}

	public function getDefensePoints() : int{
		return 8;
	}

	public function getMaxDurability() : int{
		return 1184;
	}
} 