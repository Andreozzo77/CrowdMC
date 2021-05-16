<?php

declare(strict_types=1);

namespace CustomEnchants\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\level\Location;
use pocketmine\Server;
use pocketmine\level\particle\DustParticle;

class RocketTask extends Task{
	/** @var Player */
	private $player;
	/** @var int */
	private $blocksLeft;
	/** @var Location */
	private $location;
	
	public function __construct(Player $player, int $blocks){
		$this->player = $player;
		$this->blocksLeft = $blocks;
		$this->location = $player->asLocation();
		$player->setImmobile(true);
	}
	
	public function onRun(int $currentTick) : void{
		if(!$this->player->isOnline()){
			goto end;
		}else{
			if($this->blocksLeft > 0){
				$this->blocksLeft--;
				$this->location->y++;
				$this->player->teleport($this->location);
				$this->player->level->addParticle(new DustParticle($this->player->asVector3(), 238, 130, 238, 255));
			}else{
				end: {
					$this->player->setImmobile(false);
					Server::getInstance()->getPluginManager()->getPlugin("CustomEnchants")->getScheduler()->cancelTask($this->getTaskId());
				}
			}
		}
	}
	
}