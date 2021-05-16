<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockPets\pets\creatures;

use BlockHorizons\BlockPets\pets\HoveringPet;
use pocketmine\math\Vector3;

class EnderDragonPet extends HoveringPet {

	const NETWORK_ID = self::ENDER_DRAGON;

	public $name = "Ender Dragon Pet";

	public $width = 2.5;
	public $height = 1;
	
	protected function initEntity() : void{
		parent::initEntity();
	}
}
