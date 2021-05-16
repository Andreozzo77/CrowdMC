<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\tile\Chest;
use pocketmine\math\AxisAlignedBB;
use kenygamer\Core\task\OutlineAreaTask;
use kenygamer\Core\LangManager;
use revivalpmmp\pureentities\tile\MobSpawner;

class ChunkCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"chunk",
			"Provides more information about this chunk",
			"/chunk",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		/** @var Chunk */
		$chunk = $sender->getLevel()->getChunk($sender->getX() >> 4, $sender->getZ() >> 4);
		$players = -1;
	    foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player){
	    	$ch = $player->getLevel()->getChunk($player->getX() >> 4, $player->getZ() >> 4);
	    	if($ch->getX() === $chunk->getX() && $ch->getZ() === $chunk->getZ()){
	    		$players++;
	    	}
	    }
	    $chests = 0;
	    $spawners = 0;
	    foreach($chunk->getTiles() as $tile){
	    	if(!$tile->isClosed()){
	    		if($tile instanceof Chest){
	    			$chests++;
	    		}elseif($tile instanceof MobSpawner){
	    			$spawners++;
	    		}
	    	}
	    }
	    if(empty($chests) && empty($spawners)){
	        LangManager::send("chunk-none", $sender);
	        return true;
	    }
	    $sender->sendMessage("chunk-info", $chests, $spawners, $players);
	    
	    $startX = $chunk->getX();
	    $startZ = $chunk->getZ();
	    $minX = $sender->getFloorX();
	    $minZ = $sender->getFloorZ();
	    while($minX >> 4 === $startX){
	    	$minX--;
	    }
	    while($minZ >> 4 === $startZ){
	    	$minZ--;
	    }
	    $bb = new AxisAlignedBB($minX, 0, $minZ, $minX + 16, 0, $minZ + 16);
	    $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new OutlineAreaTask($bb, $player->getLevel(), 3), 20);
		return true;
	}
	
}