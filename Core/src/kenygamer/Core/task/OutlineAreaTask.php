<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;

use kenygamer\Core\Main;

class OutlineAreaTask extends Task{
	/** @var AxisAlignedBB */
	private $bb;
	/** @var Level */
	private $level;
	/** @var int */
	private $duration,  $durationLeft;
	
	/**
	 * OutlineAreaTask constructor.
	 *
	 * @param AxisAlignedBB $bb The area to map out
	 * @param Level $level
	 * @param int $duration Duration in seconds
	 */
	public function __construct(AxisAlignedBB $bb, Level $level, int $duration){
		$this->bb = $bb;
		$this->level = $level;
		$this->duration = $this->durationLeft = $duration;
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$this->durationLeft--;
		if($this->durationLeft === 0){
			Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
		}
		$retA = false;
		$retB = false;
		for($x = $this->bb->minX; $x <= $this->bb->maxX; $x++){
			for($y = 0; $y <= $this->level->getWorldHeight(); $y++){
				if($this->level->getBlockIdAt((int) $x, $y, (int) $this->bb->minZ) === 0 && !$retA){
					for($i = 0; $i < 1; $i++){
						$this->level->addParticle(new DustParticle(new Vector3($x, $y + 2 + $i, $this->bb->minZ), 0, 0, 0, 1));
					}
					$retA = true;
				}
				if($this->level->getBlockIdAt((int) $x, $y, (int) $this->bb->maxZ) === 0 && !$retB){
					for($i = 0; $i < 1; $i++){
						$this->level->addParticle(new DustParticle(new Vector3($x, $y + 2 + $i, $this->bb->maxZ), 0, 0, 0, 1));
					}
					$retB = true;
				}
				if($retA && $retB){
					break;
				}
			}
			$retA = false;
			$retB = false;
		}
		
		$retA = false;
		$retB = false;
		for($z = $this->bb->minZ; $z <= $this->bb->maxZ; $z++){
			for($y = 0; $y <= $this->level->getWorldHeight(); $y++){
				if($this->level->getBlockIdAt((int) $this->bb->minX, $y, (int) $z) === 0 && !$retA){
					for($i = 0; $i < 1; $i++){
						$this->level->addParticle(new DustParticle(new Vector3($this->bb->minX, $y + 2 + $i, $z), 0, 0, 0, 1));
					}
					$retA = true;
				}
				if($this->level->getBlockIdAt((int) $this->bb->maxX, $y, (int) $z) === 0 && !$retB){
					for($i = 0; $i < 1; $i++){
						$this->level->addParticle(new DustParticle(new Vector3($this->bb->maxX, $y + 2 + $i, $z), 0, 0, 0, 1));
					}
					$retB = true;
				}
				if($retA && $retB){
					break;
				}
			}
			$retA = false;
			$retB = false;
		}
	}
	
}