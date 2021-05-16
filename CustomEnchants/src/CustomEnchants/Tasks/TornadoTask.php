<?php

declare(strict_types=1);

namespace CustomEnchants\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use CustomEnchants\Main;

class TornadoTask extends Task{
	/** @var Main */
	private $plugin;
	/** @var Player */
	private $player;
	/** @var int */
	private $taskTicks = 120;
	/** @var float */
	private $yaw;
	
	public function __construct(Main $plugin, Player $player){
		$this->plugin = $plugin;
		$this->player = $player;
		$this->yaw = $player->getYaw();
	}
	
	public function onRun(int $currentTick) : void{
		if($this->player->isOnline()){
			$this->yaw += 3;
			if($this->yaw > 360){
				$this->yaw = 0;
			}
			$this->player->teleport($this->player->asVector3(), $this->yaw);
		}else{
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
			return;
		}
		if(--$this->taskTicks <= 0){
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
		}
	}
}