<?php

declare(strict_types=1);

namespace kenygamer\Core\text;

use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class FloatingText{
	/** @var self[] */
	private static $texts = [];
	
	/** @var string */
	private $text, $title;
	/** @var \Closure|null */
	private $textParams = null;
	/** @var int */
	private $updateInterval;
	/** @var Vector3|Position */
	private $pos;
	/** @var FloatingTextParticle */
	private $particle;
	/** @var array */
	private $spawnedTo = [];
	/** @var bool */
	private $flaggedForClose = false;
	
	public static function init(Main $plugin) : void{
		new FloatingTextListener($plugin);
		$plugin->getScheduler()->scheduleRepeatingTask(new FloatingTextTask(), 20);
		
		$hub = $plugin->getServer()->getLevelByName("hub");
		//Defaults
		new FloatingText($hub->getSpawnLocation(), "welcome", "text-welcome-text", "text-welcome-title", 60);
		new FloatingText(new Position(45049, 41, -42602, $hub), "blue_crate", "", "text-1-title");
		new FloatingText(new Position(45052, 41, -42606, $hub), "red_crate", "", "text-2-title");
		new FloatingText(new Position(45057, 41, -42608, $hub), "green_crate", "", "text-3-title");
		new FloatingText(new Position(45062, 41, -42606, $hub), "yellow_crate", "", "text-4-title");
		new FloatingText(new Position(45065, 41, -42602, $hub), "purple_crate", "", "text-5-title");
		
		new FloatingText(new Position(45070, 37, -42463, $hub), "top_1", "text-top-1-text", "text-top-1-title", 60, static function() use($plugin){
			return $plugin->getTopFactions();
		});
		new FloatingText(new Position(45062, 37, -42466, $hub), "top_2", "text-top-2-text", "text-top-2-title", 60, static function() use($plugin){
			return $plugin->getTopOnline();
		});
		new FloatingText(new Position(45053, 37, -42466, $hub), "top_3", "text-top-3-text", "text-top-3-title", 60, static function() use($plugin){
			return $plugin->getTopVoters();
		});
		new FloatingText(new Position(45047, 37, -42464, $hub), "top_4", "text-top-4-text", "text-top-4-title", 60, static function() use($plugin){
			return $plugin->getTopKdr();
		});
		new FloatingText(new Position(45232, 30, -42312, $hub), "workshop", "text-workshop-text", "text-workshop-title");
		new FloatingText(new Position(45057, 41, -42594, $hub), "crates", "text-crates-text", "text-crates-title");
		
		new FloatingText(new Position(45192, 30, -42272, $hub), "tinkerer_1", "text-tinkerer-text", "text-tinkerer-title");
		new FloatingText(new Position(45192, 30, -42272, $hub), "tinkerer_2", "text-tinkerer-text", "text-tinkerer-title");
		
		new FloatingText(new Position(44899, 32, -42453, $hub), "trader_1", "text-trader-text", "text-trader-title");
		new FloatingText(new Position(44899, 32, -42453, $hub), "trader_2", "text-trader-text", "text-trader-title");
		
		new FloatingText(new Position(45235, 30, -42519, $hub), "office_1", "text-office-text", "text-office-title");
		new FloatingText(new Position(45235, 30, -42519, $hub), "office_2", "text-office-text", "text-office-title");
		
		new FloatingText(new Position(45057, 40, -42514, $hub), "shop", "text-shop-text", "text-shop-title");
		new FloatingText(new Position(45069, 39, -42502, $hub), "1", "text-2-1-text", "text-2-1-title");
		new FloatingText(new Position(45045, 39, -42502, $hub), "2", "text-2-2-text", "text-2-2-title");
	}
	
	public static function getText(string $identifier) : ?self{
		return self::$texts[$identifier] ?? null;
	}
	
	public static function getTexts() : array{
		return self::$texts;
	}
	
	/**
	 * @param Vector3 $pos
	 * @param int $updateTicks Period of update task, -1 to not update
	 * @param string $identifier
	 * @param string $text LangManager lang key
	 * @param string $title LangManager lang Key
	 * @param int $updateInterval
	 * @param Closure $textParams or null
	 */
	public function __construct(Vector3 $pos, string $identifier, string $text, string $title = "", int $updateInterval = 0, \Closure $textParams = null){
		$this->particle = new FloatingTextParticle($this->pos = $pos, "", "");
		$this->text = $text;
		$this->title = $title;
		$this->updateInterval = $updateInterval;
		if(isset(self::$texts[$identifier])){
			throw new \RuntimeException("There is already a floating text with identifier " . $identifier);
		}
		
		if(!$this->isDynamic()){ //Immediately setup text/title as self::spawnTo() doesn't do this
		    $this->textParams = $textParams; 
		    if($text !== ""){
		    	$this->particle->setText(LangManager::translate($text, ...$this->getTextParams()));
		    }
		    if($title !== ""){
		    	$this->particle->setTitle(LangManager::translate($title, ...$this->getTextParams()));
		    }
		}
		$this->textParams = $textParams;
		
		self::$texts[$identifier] = $this;
	}
	
	private function inLevel(Player $player) : bool{
		return $this->getPosition() instanceof Position ? $player->getLevel()->getFolderName() === $this->getPosition()->getLevel()->getFolderName() : true;
	}
	
	/**
	 * @return array unpackable parameters
	 */
	public function getTextParams() : array{
		$textParams = $this->textParams;
		return $textParams !== null ? $textParams() : [];
	}
	
	public function getUpdateInterval() : int{
		return $this->updateInterval;
	}
	
	public function isDynamic() : bool{
		return $this->getUpdateInterval() > 0;
	}
	
	/**
	 * Dynamic texts
	 */
	public function updateForAndSendTo(Player $player = null) : bool{
		if(!$this->isDynamic()){
			return false;
		}
		$oldText = $this->particle->getText();
		$oldTitle = $this->particle->getTitle();
		if($this->text !== ""){
			$this->particle->setText(LangManager::translate($this->text, $player, ...$this->getTextParams()));
		}
		if($this->title !== ""){
			$this->particle->setTitle(LangManager::translate($this->title, $player, ...$this->getTextParams()));
		}
		if($player !== null){
			$this->spawnTo($player, true);
		}
		$this->particle->setText($oldText);
		$this->particle->setTitle($oldTitle);
		return true;
	}
	
	public function getPosition() : Vector3{
		return $this->pos;
	}
	
	/**
	 * Non-dynamic texts; unless called from self::close() it has no real effect for dynamic.
	 */
	public function despawnFrom(Player $player, bool $sendPacket = true) : void{
		if(isset($this->spawnedTo[$player->getName()])){
			unset($this->spawnedTo[$player->getName()]);
			if($sendPacket){
				$old = $this->particle->isInvisible();
				$this->particle->setInvisible(true);
				$player->getLevel()->addParticle($this->particle, [$player]);
				$this->particle->setInvisible($old);
			}
		}
	}
	
	/**
	 * Wrapper around self::despawnTo()
	 * @see self::despawnTo()
	 */
	public function despawnFromAll(bool $sendPacket = true) : void{
		foreach($this->spawnedTo as $player){
			$player = Server::getInstance()->getPlayerExact($player);
			if($player === null){
				continue;
			}
			$this->despawnFrom($player, $sendPacket);
		}
	}
	
	/**
	 * Non-dynamic floating texts
	 */
	public function spawnTo(Player $player, bool $force = false) : void{
		if((!isset($this->spawnedTo[$player->getName()]) && $this->inLevel($player)) || $force){
			$this->spawnedTo[$player->getName()] = true;
			$old = $this->particle->isInvisible();
			$this->particle->setInvisible(false);
			$player->getLevel()->addParticle($this->particle, [$player]);
			$this->particle->setInvisible($old);
		}
	}
	
	/**
	 * Wrapper around self::spawnTo()
	 * @see self::spawnTo()
	 */
	public function spawnToAll(){
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$this->spawnTo($player);
		}
	}
	
	public function isFlaggedForClose() : bool{
		return $this->flaggedForClose;
	} 
	
	public function close(bool $taskCallback = false){
		$this->flaggedForClose = true;
		if($taskCallback){
			$this->despawnFromAll();
			foreach(self::getTexts() as $identifier => $text){
				if(spl_object_hash($text) === spl_object_hash($this)){
					unset(self::$texts[$identifier]);
					break;
				}
			}
		}
	}
	
}