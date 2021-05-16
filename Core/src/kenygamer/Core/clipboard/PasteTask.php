<?php

declare(strict_types=1);

namespace kenygamer\Core\clipboard;

use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\Server;

use JsonMachine\JsonMachine;
use kenygamer\Core\Main;
use kenygamer\Core\clipboard\ClipboardException;

class PasteTask extends AsyncTask{
	private const SHARDS_PER_TASK = 50;
	
	/** @var string serialized Vector3 */
	private $pasteVector;
	/** @var string */
	private $level;
	/** @var int */
	private $flags;
	/** @var string */
	private $workingDirectory;
	/** @var string serialized int[] */
	private $shards;
	/** @var string serialized Chunk[] */
	private $chunks;
	
	private function serializeChunks(array $checkChunks, Level $level) : array{
		$chunks = [];
		foreach($checkChunks as $chunk){
			list($chunkX, $chunkZ) = explode(":", $chunk);
			$chunkX = (int) $chunkX;
			$chunkZ = (int) $chunkZ;
			$chunks[Level::chunkHash($chunkX, $chunkZ)] = $level->getChunk($chunkX, $chunkZ, true)->fastSerialize();
		}
		return $chunks;
	}
	
	public function __construct(Position $pastePos, string $workingDirectory, array $shards, array $chunks = [], int $flags, array $callback = null){
		if(!class_exists(JsonMachine::class)){
			throw new ClipboardException("JsonMachine library required. Require it in composer.");
		}
		$this->pasteVector = serialize($pastePos->asVector3());
		$this->flags = $flags;
		$this->level = $pastePos->getLevel()->getFolderName();
		$this->workingDirectory = $workingDirectory;
		$this->shards = serialize($shards);
		
		$isSerialized = gettype(array_slice(array_keys($chunks), 0, 1)) === "string"; //Check if has string indexes
		$this->chunks = serialize(!$isSerialized ? $this->serializeChunks($chunks, $pastePos->getLevel()) : $chunks);
		
		if($callback !== null){
			assert(is_callable(array_slice($callback, 0, 2)));
			foreach(array_slice($callback, 2) as $i => $arg){
				assert(is_int($i));
				if(!is_scalar($arg)){ //int, string, boolean, or float
				   throw new \InvalidArgumentException("Scalar types are not safely serializable: in callback arguments");
				}
			}
		}
		$this->storeLocal($callback);
	}
	
	public function onRun() : void{
		$pasteVector = unserialize($this->pasteVector);
		$chunks = unserialize($this->chunks);
		$shards = unserialize($this->shards);
		foreach($chunks as $hash => $binary){
			$chunks[$hash] = Chunk::fastDeserialize($binary);
		}
		$touchedChunks = [];
		$shardCount = count($shards);
		for($i = 0; $i < $shardCount; $i++){
			$blocks = JsonMachine::fromFile($this->workingDirectory . str_replace("{i}", $i, ClipboardManager::SHARD_FILE));
			foreach($blocks as $block){
				list($id, $data, $rel) = $block;
				$rel[0] += $pasteVector->x; //Absolute
				$rel[1] += $pasteVector->y; //Absolute
				$rel[2] += $pasteVector->z; //Absolute
				$hash = Level::chunkHash($rel[0] >> 4, $rel[2] >> 4);
				if(isset($chunks[$hash])){
					$touchedChunks[$hash] = true;
					//0x7f = 127, 0xff = 255, 0x0f = 15
					$chunks[$hash]->getSubChunk($rel[1] >> 4, true)->setBlock($rel[0] & 0x0f, $rel[1] & 0x0f, $rel[2] & 0x0f, $id & 0xff, $data & 0xff);
				}
			}
			unset($shards[$i]);
			if($i + 1 === self::SHARDS_PER_TASK || ($i === $shardCount - 1)){
				$this->shards = serialize($shards);
				break;
			}
		}
		$this->setResult([$chunks, $touchedChunks]);
	}
	
	public function onCompletion(Server $server) : void{
		list($chunks, $touchedChunks) = $this->getResult();
		$level = $server->getLevelByName($this->level);
		if($level !== null){
			$newChunks = [];
			foreach($chunks as $hash => $chunk){ 
			    Level::getXZ($hash, $x, $z);
			    $newChunks[] = $x . ":" . $z;
			    if(isset($touchedChunks[$hash])){
			    	$level->setChunk($x, $z, $chunk, false);
			    }
			    unset($chunks[$hash]);
            }
            $shards = unserialize($this->shards);
            $callback = $this->fetchLocal();
            if(!empty($shards)){
            	$pasteVector = unserialize($this->pasteVector);
            	$workingDirectory = $this->workingDirectory;
            	    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($pasteVector, $level, $workingDirectory, $shards, $newChunks, $callback) : void{
            	    	Server::getInstance()->getAsyncPool()->submitTask(new self(Position::fromObject($pasteVector, $level), $workingDirectory, $shards, $newChunks, $this->flags, $callback));
            	    }), 20 * 5);
            }else{
            	if($callback !== null){
            		call_user_func(array_slice($callback, 0, 2), ...array_slice($callback, 2));
            	}
            }
        }
    }
    
}