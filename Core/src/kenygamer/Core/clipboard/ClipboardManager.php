<?php

declare(strict_types=1);

namespace kenygamer\Core\clipboard;

use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;

use kenygamer\Core\Main;
use kenygamer\Core\clipboard\ClipboardException;

class ClipboardManager{
	public const CLIPBOARD_PATH = "clipboard/";
	private const INFO_FILE = "clipboard.js";
	public const SHARD_FILE = "shard.{i}.js";
	
	//Copy flags
	public const FLAG_NO_AIR = 0x1;
	
	//Paste flags
	public const FLAG_RELATIVE = 0x2;
	
	private static $instance = null;
	private $listener;
	
	/** @var string[] */
	private $copyQueue = [];
	/** @var array */
	private $sessions = [];
	
	public function __construct(Main $plugin){
		$this->path = $plugin->getDataFolder() . self::CLIPBOARD_PATH;
		@mkdir($this->path);
		
		$property = (new \ReflectionClass(PluginBase::class))->getProperty("file");
		$property->setAccessible(true);
		$src = $property->getValue($plugin) . "resources" . DIRECTORY_SEPARATOR . "clipboard";
		$dest = $plugin->getDataFolder() . "resources" . DIRECTORY_SEPARATOR . "clipboard";
		
		//Copy PMine
		foreach($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item){
  			if($item->isDir()){
    			@mkdir($dest . $iterator->getSubPathName(), 0777);
  			}else{
    			@copy($item->getFileName(), $dest . $iterator->getSubPathName());
			}
		}

		$this->listener = new ClipboardListener($plugin);
		self::$instance = $this;
	}
	
	/**
	 * @param string[] $args
	 * @return int
	 */
	public static function parseFlags(array $args) : int{
		$constants = (new \ReflectionClass(self::class))->getConstants();
		$flags = [];
		foreach($constants as $name => $value){
			if(strpos($name, "FLAG_") !== false){
				//Example: FLAG_NO_AIR constant turns into --flag-no-air
				$flags["--" . mb_strtolower(str_replace(["FLAG_", "_"], ["", "-"], $name))] = $value;
			}
		}
		$return = 0;
		foreach($args as $arg){
		    if(isset($flags[$arg])){
		    	$return |= $flags[$arg];
		    }
		}
		return $return;
	}
	
	public function setSessionVar($sess, $var, $data){
		if($sess instanceof Player){
			$sess = $sess->getId();
		}elseif(!is_int($sess)){
			return false;
		}
		$this->sessions[$sess][$var] = $data;
	}
	
	public function getSessionVar($sess, $var, $default = null){
		if($sess instanceof Player){
			$sess = $sess->getId();
		}elseif(!is_int($sess)){
			return false;
		}
		return $this->sessions[$sess][$var] ?? $default;
	}
	
	public function unsetSessionVar($sess, $var){
		if($sess instanceof Player){
			$sess = $sess->getId();
		}elseif(!is_int($sess)){
			return false;
		}
		unset($this->sessions[$sess][$var]);
	}
	
	public function destroySession($sess){
		if($sess instanceof Player){
			$sess = $sess->getId();
		}elseif(!is_int($sess)){
			return false;
		}
		unset($this->sessions[$sess]);
	}
	
	public function getPath() : string{
		return $this->path;
	}
	
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	public function getAllClipboards() : array{
		return array_map(function(string $path){
			return str_replace($this->getPath(), "", $path);
		}, glob($this->getPath() . "*"));
	}
	
	/**
	 * @return AxisAlignedBB
	 */
	public function getBoundingBox(Vector3 $pos1, Vector3 $pos2) : AxisAlignedBB{
		return new AxisAlignedBB(min($pos1->getX(), $pos2->getX()), min($pos1->getY(), $pos2->getY()), min($pos1->getZ(), $pos2->getZ()), max($pos1->getX(), $pos2->getX()), max($pos1->getY(), $pos2->getY()), max($pos1->getZ(), $pos2->getZ()));
	}
	
	/**
	 * @return string[]
	 */
	private function getCheckChunks(Vector3 $pos1, Vector3 $pos2) : array{
		$chunks = [];
		$bb = $this->getBoundingBox($pos1, $pos2);
		for($x = $bb->minX; $x - 16 <= $bb->maxX; $x += 16){
			for($z = $bb->minZ; $z - 16 <= $bb->maxZ; $z += 16){
				$chunks[] = ($x >> 4) . ":" . ($z >> 4);
			}
		}
		return $chunks;
	}
	
	/**
	 * @param array $chunks
	 * @param Level $level
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFailure
	 * @param int $timeout
	 */
	public function populateChunks(array $chunks, Level $level, ?\Closure $onSuccess = null, ?\Closure $onFailure = null, int $timeout = 0){
		$ref = uniqid("", true);
		$start = time();
		$this->listener->createQueue($chunks, $level, $ref);
		$this->listener->populateQueue($ref); //This will populate adjacent chunks
		$handler = Main::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) use($chunks, $level, $onSuccess, $onFailure, $ref, $start, $timeout, &$handler) : void{
			if($timeout > 0 && time() - $start >= $timeout){
				$handler->cancel();
				$onFailure();
			}else{
				if($this->listener->isQueuePopulated($ref)){
					$handler->cancel();
					$onSuccess();
				}
			}
		}), 20);
	}
	
	public function getClipboardInfo(string $name) : array{
		$workingDirectory = $this->getPath() . $name . "/";
		$infoFile = $workingDirectory . self::INFO_FILE;
		if(!is_dir($workingDirectory)){
			throw new ClipboardException($workingDirectory . " is not a valid directory");
		}
		if(!file_exists($infoFile)){
			throw new ClipboardException($infoFile . " is not a valid file");
		}
		$arr = json_decode(file_get_contents($infoFile), true);
		if(count($arr) < 6){
			throw new ClipboardException($infoFile . " contains insufficient primitives (at least 6 expected, got " . count($arr) . ")");
		}
		return $arr;
	}
	
	/**
	 * @param Position $pastePos
	 * @param string $name
	 * @param int $flags
	 * @param array $callable NOT a callable directly. Use call_user_func([array_shift($callable, array_shift($callable)], ...$callable
	 */
	public function pasteClipboard(Position $pastePos, string $name, int $flags = 0, array $callback = null) : void{
		if(!($pastePos->getLevel() instanceof Level)){
			throw new ClipboardException("Position must have a Level object");
		}
		list($minX, $minY, $minZ, $maxX, $maxY, $maxZ) = $this->getClipboardInfo($name);
		$checkChunks = $this->getCheckChunks($pastePos->asVector3(), new Vector3($pastePos->x + ($maxX - $minX), $pastePos->y + ($maxY - $minY), $pastePos->z + ($maxZ - $minZ)));
		$workingDirectory = $this->getPath() . $name . "/";
		$this->populateChunks($checkChunks, $pastePos->getLevel(), function() use($pastePos, $workingDirectory, $checkChunks, $flags, $callback){
			$shards = array_map(function(string $path) : int{
				return intval(pathinfo($path)["filename"]);
			}, glob($workingDirectory . str_replace("{i}", "*", self::SHARD_FILE)));
			Server::getInstance()->getAsyncPool()->submitTask(new PasteTask($pastePos, $workingDirectory, $shards, $checkChunks, $flags, $callback));
		});
	}
	
	/**
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param Level $level
	 * @param string $name
	 * @param int $flags
	 * @param array $callback On copy success
	 * @return bool
	 */
	public function createClipboard(Vector3 $pos1, Vector3 $pos2, Level $level, string $name, int $flags, ?\Closure $callback = null) : void{
		if(!in_array($name, $this->copyQueue)){
			$this->copyQueue[] = $name;
			$this->populateChunks($this->getCheckChunks($pos1, $pos2), $level, function() use($pos1, $pos2, $level, $name, $flags, $callback){
				$bb = $this->getBoundingBox($pos1, $pos2, $level, $name);
				$workingDirectory = $this->getPath() . $name . "/";
				@mkdir($workingDirectory, 0777);
				$this->deleteClipboard($name);
			    $blockCount = intval(($bb->maxX - $bb->minX) * ($bb->maxY - $bb->minY) * ($bb->maxZ - $bb->minZ));
			    file_put_contents($workingDirectory . self::INFO_FILE, json_encode([intval(floor($bb->minX)), intval(floor($bb->minY)), intval(floor($bb->minZ)), intval(floor($bb->maxX)), intval(floor($bb->maxY)), intval(floor($bb->maxZ))], JSON_PRETTY_PRINT));
				Main::getInstance()->getScheduler()->scheduleTask(new CopyTask($bb, $level, $name, $blockCount, $flags, function() use($name, $callback){
					unset($this->copyQueue[array_search($name, $this->copyQueue)]);
					if($callback !== null){
						$callback();
					}
				}));
			}, function() use($name){
				unset($this->copyQueue[array_search($name, $this->copyQueue)]);
			});
			return;
		}
		throw new ClipboardException("There is already a clipboard with this name in queue");
	}
	
	public function deleteClipboard(string $name) : void{
		if(trim($name) !== ""){
			array_map("unlink", glob(self::CLIPBOARD_PATH . $name . "/*.js"));
			@rmdir(self::CLIPBOARD_PATH . $name);
		}
	}
}