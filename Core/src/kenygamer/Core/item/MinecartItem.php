<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Minecart as PMMinecart;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MinecartItem extends PMMinecart{
	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool{
		$entity = Entity::createEntity(Entity::MINECART, $player->getLevel(), Entity::createBaseNBT($blockReplace->add(0.5, 0, 0.5)));

		$entity->spawnToAll();
		if($player->isSurvival()){
			$this->count--;
		}

		return true;
	}
}