<?php
 
declare(strict_types=1);
 
namespace kenygamer\Core\clipboard;
 
use pocketmine\scheduler\Task;
use pocketmine\math\AxisAlignedBB;
use pocketmine\level\Level;
 
use kenygamer\Core\Main;
 
class CopyTask extends Task{
	private const BLOCKS_PER_SHARD = 100000;
 	
 	/** @var AxisAlignedBB */
 	private $bb;
 	/** @var Level */
 	private $level;
 	/** @var string */
 	private $name;
 	/** @var int */
 	private $blockCount;
 	/** @var int */
 	private $flags;
 	/** @var \Closure|null */
 	private $callback;
 	
 	/** @var int */
 	private $shard;
 	
 	public function __construct(AxisAlignedBB $bb, Level $level, string $name, int $blockCount, int $flags, ?\Closure $callback = null, int $shard = 0){
 		$this->bb = $bb;
 		$this->level = $level;
 		$this->name = $name;
 		$this->blockCount = $blockCount;
 		$this->callback = $callback;
 		$this->flags = $flags;
 		 
 		$this->shard = $shard;
 	}
 	
 	public function onRun(int $currentTick) : void{
 		$manager = ClipboardManager::getInstance();
 		$workingDirectory = $manager->getPath() . $this->name . "/";
 		$startBlock = ($this->shard * self::BLOCKS_PER_SHARD) - 1; ///Block index
 		
 		$block = 0; //Block index
 		$shardBlocks = [];
 		for($x = $this->bb->minX, $relX = 0; $x <= $this->bb->maxX; $x++, $relX++){
 			for($y = $this->bb->minY, $relY = 0; $y <= $this->bb->maxY; $y++, $relY++){
 				for($z = $this->bb->minZ, $relZ = 0; $z <= $this->bb->maxZ; $z++, $relZ++){
 					if($block >= $startBlock){
 						$isLastBlock = $block === $this->blockCount - 1;
 						if(count($shardBlocks) === self::BLOCKS_PER_SHARD || $isLastBlock){
 							file_put_contents($workingDirectory . str_replace("{i}", $this->shard, ClipboardManager::SHARD_FILE), json_encode($shardBlocks));
 							Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
 							if(!$isLastBlock){
 								
 								Main::getInstance()->getScheduler()->scheduleDelayedTask(new self($this->bb, $this->level, $this->name, $this->blockCount, $this->flags, $this->callback, ++$this->shard), 10);
 							}else{
 								
 								$callback = $this->callback;
 								$callback(); 
 							}
 							break 3;
 						}
 						$x = (int) $x;
 						$y = (int) $y;
 						$z = (int) $z;
 						/** @var int 0-255 */
 						$id = $this->level->getBlockIdAt($x, $y, $z);
 						if(!($id === 0 && $this->flags & ClipboardManager::FLAG_NO_AIR)){
 							/** @var int 0-15 */
 							$data = $this->level->getBlockDataAt($x, $y, $z);
 							$shardBlocks[] = [$id, $data, [$relX, $relY, $relZ]];
 						}
 					}
 					$block++;
 				}
 			}
 		}
 	}
 	
 }