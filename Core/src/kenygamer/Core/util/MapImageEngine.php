<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\tile\ItemFrame;
use pocketmine\level\Position;
use pocketmine\utils\BinaryStream;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Color;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\CompressBatchedTask;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener;

final class MapImageEngine{
	/** @var self|null */
	private static $instance = null;
	/** @var string */
	private $mapPath;
	/** @var array Mapped by chunk => player => bool */
	private $cache = [];
	/** @var array Mapped by chunk => bool */
	private $regeneratingCache = [];
	/** @var array Mapped by chunk => player => bool */
	private $sentMaps = [];
	
	public const MAP_WIDTH = 128;
	public const MAP_HEIGHT = 128;
	
	private $ENABLE_DEBUG = false;
	
	private function __construct(){
		self::$instance = $this;
		$this->mapPath = Main::getInstance()->getDataFolder() . "maps/";
		@mkdir($this->mapPath, 0777);
	}
	
	/**
	 * @return self
	 */
	public static function getInstance() : self{
		if(!(self::$instance instanceof self)){
			return self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @param string|int $map_uuid
	 * @return string
	 */
	private function getMapPath($map_uuid) : string{
		return $this->mapPath . $map_uuid . ".dat";
	}
	
	/**
	 * @param Position $pos
	 * @return string
	 */
	private function getChunk(Position $pos) : string{
		if(!Server::getInstance()->isRunning()){
			return "N/A";
		}
		if($pos->getLevel() === null){
			return "0:0";
		}
		return ($pos->getX() >> 4) . ":" . ($pos->getZ() >> 4) . ":" . $pos->getLevel()->getFolderName();
	}
	
	/**
	 * Clears filled maps that are not linked to any image.
	 * @param Position $pos
	 * @return int
	 */
	private function clearGarbageMaps(Position $pos) : int{
		assert($pos->getLevel() !== null);
		$cleared = 0;
		$chunk = $this->getChunk($pos);
		foreach($pos->getLevel()->getTiles() as $tile){
			if(!($tile instanceof ItemFrame)){
				continue;
			}
			$item = $tile->getItem();		
			if(($map_uuid = $item->getNamedTag()->getString("map_uuid", "")) !== ""){
				if(!file_exists($this->getMapPath($map_uuid))){
					$tile->setItem(ItemFactory::get(Item::AIR));
					$cleared++;
				}
			}
		}
		if($this->ENABLE_DEBUG && $cleared > 0){
			echo "Cleared " . $cleared . " garbage maps" . PHP_EOL;
		}
		return $cleared;
	}
	
	/**
	 * @api
	 * @param Player $player
	 * @param bool $force
	 */
	public function sendMapsToPlayer(Player $player, bool $force = false) : void{
		if(!$this->regenerateCacheIfNotCached($player)){
			$this->sendMaps($player, $force);
		}
	}
	
	/**
	 * @param Position $pos
	 * @return bool
	 */
	private function regenerateCacheIfNotCached(Position $pos) : bool{
		$chunk = $this->getChunk($pos);
		if(!isset($this->cache[$chunk])){
			$this->regenerateCache($pos); //Has a callback to self::sendMaps()
			return true;
		}
		return false;
	}
	
	/**
	 * Regenerates cache and sends maps to all players in the chunk.
	 * @param Position $pos
	 */
	public function regenerateCache(Position $pos) : void{
		assert($pos->getLevel() !== null);
		$chunk = $this->getChunk($pos);
		if(isset($this->regeneratingCache[$chunk])){
			return;
		}
		if($this->ENABLE_DEBUG){
			echo __FUNCTION__ . ": Regenerating cache of chunk " . $chunk . "..." . PHP_EOL;
		}
		
		$dimension = Main::getInstance()->getWorldDimension($pos->getLevel()->getFolderName());
		$this->clearGarbageMaps($pos);
		$this->cache[$chunk] = [];
		
		$maps = [];
		foreach($pos->getLevel()->getTiles() as $tile){
			if($tile instanceof ItemFrame && ($nbt = $tile->getItem()->getNamedTag())->hasTag("map_uuid")){
				$uuid = $nbt->getString("map_uuid");
				$maps[] = $uuid;
			}
		}
		if($this->ENABLE_DEBUG){
			echo __FUNCTION__ . ": Regenerating cache of chunk " . $chunk . "..." . PHP_EOL;
		}
		
		$haystack = array_chunk($maps, 500); //500 is max packets per batch
		if(!empty($haystack)){
			$this->regeneratingCache[$chunk] = true;
		}
		if($this->ENABLE_DEBUG){
			echo count($haystack) . " batches will be compressed..." . PHP_EOL;
		}
		foreach($haystack as $i => $maps){
			$batch = new BatchPacket();
			foreach($maps as $map_uuid){
				$colors = $this->readMap($map_uuid);
				
				$pk = new ClientboundMapItemDataPacket();
				$pk->mapId = (int) $map_uuid;
				$pk->scale = 0; 
				$pk->dimensionId = $dimension;
				$pk->width = self::MAP_WIDTH;
				$pk->height = self::MAP_HEIGHT;
				$pk->colors = $colors;
				
				$batch->addPacket($pk);
			}
			$end = $i === count($haystack) - 1;
			
			$this->compressBatch($batch, function($result) use($pos, $end){
				$chunk = $this->getChunk($pos);
				$this->cache[$chunk][] = $result;
				if($end){
					unset($this->regeneratingCache[$chunk]);
					$this->sendMaps($pos, true);
				}
			});
		}
	}
	
	/**
	 * Compresses a batch and calls the closure on completion.
	 *
	 * @param BatchPacket $batch
	 * @param \Closure $callback
	 */
	public function compressBatch(BatchPacket $batch, \Closure $callback = null) : void{
		Main::getInstance()->getServer()->getAsyncPool()->submitTask(new class($batch, $callback) extends CompressBatchedTask{
			public function __construct(BatchPacket $batch, \Closure $callback = null){
				$this->data = $batch->payload;
				$this->storeLocal($callback);
			}
			
			public function onCompletion(Server $server) : void{
				$pk = new BatchPacket($this->getResult());
				$pk->isEncoded = true;
				$callback = $this->fetchLocal();
				if($callback !== null){
					$callback($pk);
				}
				if($this->ENABLE_DEBUG){
					echo __FUNCTION__ . ": Batch compression finalized, " . ($callback !== null ? "ran a callback" : "did not run a callback") . PHP_EOL;
				}
			}
		});
		if($this->ENABLE_DEBUG){
			echo __FUNCTION__ . ": Batch compression started" . PHP_EOL;
		}
	}
	
	/**
	 * Sends maps to all players in the chunk.
	 * @api
	 * @param Position $pos
	 * @param bool $force
	 */
	public function sendMaps(Position $pos, bool $force = false) : void{
		$chunk = $this->getChunk($pos);
		if($this->ENABLE_DEBUG){
			echo __FUNCTION__ . ": Sending maps in chunk " . $chunk . "..." . PHP_EOL;
		}
		if($pos->getLevel() === null){
			return;
		}
		foreach($pos->getLevel()->getPlayers() as $player){
			if($this->getChunk($player) === $chunk){
				if(isset($this->sentMaps[$chunk][$player->getName()]) && !$force){
					if($this->ENABLE_DEBUG){
						echo __FUNCTION__ . ": Tried to send maps without force parameter" . PHP_EOL;
					}
					continue;
				}
				foreach($this->cache[$chunk] ?? [] as $batch){
					$player->directDataPacket(clone $batch);
					if($this->ENABLE_DEBUG){
						echo __FUNCTION__ . ": Sent batch of chunk " . $chunk . " to player " . $player->getName() . PHP_EOL;
					}
				}
				if(!isset($this->sentMaps[$chunk][$player->getName()])){
					$this->sentMaps[$chunk][$player->getName()] = true;
				}
			}
		}
	}
	
	/**
	 * @param int|string $map_uuid
	 * @return Color[]
	 */
	public function readMap($map_uuid) : array{
		$colors = [];
		$stream = new BinaryStream(\gzinflate(\file_get_contents($this->getMapPath($map_uuid))));
		while(!$stream->feof()){
			$colors[$stream->getInt()][$stream->getInt()] = new Color($stream->getByte(), $stream->getByte(), $stream->getByte());
		}
		return $colors;
	}
	
	/**
	 * @param int|string $map_uuid
	 * @param Color[] $colors
	 * @return bool
	 */
	public function saveMap($map_uuid, array $colors) : bool{
		$path = $this->getMapPath($map_uuid);
		if(file_exists($path)){
			return false;
		}
		$stream = new BinaryStream();
		foreach($colors as $y => $columns){
			foreach($columns as $x => $color){
				$stream->putInt($y);
				$stream->putInt($x);
				$stream->putByte($color->getR());
				$stream->putByte($color->getG());
				$stream->putByte($color->getB());
			}
		}
		return \file_put_contents($path, \gzdeflate($stream->buffer, 9)) !== false;
	}
	
}