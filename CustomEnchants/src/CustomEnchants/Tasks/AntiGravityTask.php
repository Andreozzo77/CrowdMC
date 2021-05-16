<?php

declare(strict_types=1);

namespace CustomEnchants\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\level\Location;
use pocketmine\Server;
use pocketmine\block\Block;

class AntiGravityTask extends Task{
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
				$this->location->y--;
				$below = $this->location->level->getBlock($this->location);
				if($below->isSolid()){
					$this->player->teleport($this->location);
					$this->blocksLeft--;
				}else{
					goto end;
				}
			}else{
				end: {
					$this->player->setImmobile(false);
					Server::getInstance()->getPluginManager()->getPlugin("CustomEnchants")->getScheduler()->cancelTask($this->getTaskId());
				}
			}
		}
	}
	
}