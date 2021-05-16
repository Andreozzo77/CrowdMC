<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockPets\pets\creatures;

use BlockHorizons\BlockPets\pets\SmallCreature;
use BlockHorizons\BlockPets\pets\WalkingPet;

class FoxPet extends WalkingPet implements SmallCreature {

	const NETWORK_ID = 121;

	public $name = "Fox Pet";

	public $width = 0.7;
	public $height = 0.6;
	
}
