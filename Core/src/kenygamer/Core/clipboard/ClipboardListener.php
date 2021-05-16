<?php

declare(strict_types=1);

namespace kenygamer\Core\clipboard;

use pocketmine\event\Listener;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;
use pocketmine\level\Level;

use kenygamer\Core\Main;

class ClipboardListener implements Listener{
	/** @var array<string, string[] */
	private $queue = [];
	
	/**
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	/**
	 * @param string $ref
	 * @return bool
	 */
	public function isQueuePopulated(string $ref) : bool{
		return !isset($this->queue[$ref]);
	}
	
	/**
	 * @param array $chunks
	 * @param Level $level
	 * @param string $ref
	 */
	public function createQueue(array $chunks, Level $level, string $ref) : void{
		if(!isset($this->queue[$ref])){
			$this->queue[$ref] = [array_filter($chunks, function($chunk) use($level){
				list($chunkX, $chunkZ) = explode(":", $chunk);
				return !$level->getChunk((int) $chunkX, (int) $chunkZ, true)->isPopulated();
			}), $level->getFolderName()];
			if(empty($this->queue[$ref][0])){
				unset($this->queue[$ref]);
			}
		}
	}
	
	/**
	 * @param string $ref
	 */
	public function populateQueue(string $ref) : void{
		if(isset($this->queue[$ref])){
			list($chunks, $level) = $this->queue[$ref];
			$level = Server::getInstance()->getLevelByName($level);
			if($level !== null){
				foreach($chunks as $chunk){
					list($chunkX, $chunkZ) = explode(":", $chunk);
					$level->populateChunk((int) $chunkX, (int) $chunkZ, true);
				}
			}
		}
	}
	
	/**
	 * @param PlayerQuitEvent $event
	 * @priority NORMAL
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		ClipboardManager::getInstance()->destroySession($player);
	}
	
	/**
	 * @param ChunkPopulateEvent $event
	 * @ignoreCancelled true
	 */
	public function onChunkPopulate(ChunkPopulateEvent $event) : void{
		$level = $event->getLevel();
		$chunk = $event->getChunk();
		$chunkStr = $chunk->getX() . ":" . $chunk->getZ();
		foreach($this->queue as $ref => $data){
			list($chunks, $lvl) = $data;
			if(in_array($chunkStr, $chunks) && $lvl === $level->getFolderName()){
				unset($this->queue[$ref][0][array_search($chunkStr, $chunks)]);
				if(count($chunks) === 1){
					unset($this->queue[$ref]);
				}else{
					$this->populateQueue($ref);
				}
			}
		}
	}
}