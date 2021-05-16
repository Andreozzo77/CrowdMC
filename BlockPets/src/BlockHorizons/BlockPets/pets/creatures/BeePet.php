<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockPets\pets\creatures;

use BlockHorizons\BlockPets\pets\HoveringPet;
use BlockHorizons\BlockPets\pets\SmallCreature;

class BeePet extends HoveringPet implements SmallCreature {

	const NETWORK_ID = 122;

	public $name = "Bee Pet";

	public $width = 0.7;
	public $height = 0.6;
}