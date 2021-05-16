<?php

declare(strict_types=1);

namespace kenygamer\Core\bedwars;

use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\util\ItemUtils;
use CustomEnchants\CustomEnchants\CustomEnchants;

class BedWarsManager{
	/** @var BedWarsArena[] */
	private $arenas = [];
	/** @var array */
	private $queue = [];
	
	public function __construct(Main $plugin){
		new BedWarsListener($plugin);
		foreach(["bedwars-arenas.yml", "bedwars-items.yml"] as $resource){
			$plugin->saveResource($resource, true);
		}
		
		$data = new Config($plugin->getDataFolder() . "bedwars-arenas.yml", Config::YAML);
		$data2 = new Config($plugin->getDataFolder() . "bedwars-items.yml", Config::YAML);
		$this->arenas = [];
		foreach($data->getAll() as $arena){
			if(!isset($arena["world"]) || !isset($arena["spawns"]) || !isset($arena["mode"]) || !isset($arena["radius"])){
				throw new \RuntimeException("Arena does not have all required fields: world, spawns, mode, radius");
			}
			$world = (string) $arena["world"];
			$spawns = (array) $arena["spawns"];
			$mode = (int) $arena["mode"];
			$items = ItemUtils::parseItems($data2->get(self::getGameModeString(Main2::BEDWARS_MODE_NORMAL), []));
			if($mode === Main2::BEDWARS_MODE_CUSTOM){
				$items = $items + ItemUtils::parseItems($data2->get(self::getGameModeString(Main2::BEDWARS_MODE_CUSTOM), []));
				$books = [];
				$enchants = Main::getInstance()->getPlugin("CustomEnchants")->enchants;
				shuffle($enchants);
				for($i = 0; $i < ceil(count($items) / 2); $i++){
					if(!isset($enchants[$i])){
						break;
					}
					$data = $enchants[$i];
					if(in_array($data[1], ["Weapons", "Armor", "Tools"])){
						$enchantment = CustomEnchants::getEnchantmentByName($data[0]);
						if($enchantment !== null){
							$books[] = ItemUtils::get("book", "", [], [$data[0] => \kenygamer\Core\Main::mt_rand(1, $enchantment->getMaxLevel())]);
						}
					}
				}
				$items = $items + $books;
			}
			$radius = (int) $arena["radius"];
			if($radius < 1){
				throw new \RuntimeException("Radius must not be less than 1");
			}
			
			$property = (new \ReflectionClass(PluginBase::class))->getProperty("file");
			$property->setAccessible(true);
			$path = $property->getValue($plugin);
			if(!file_exists($fullPath = $path . "resources/" . $world . ".zip")){
				throw new \RuntimeException($world . ".zip does not exist. Please check that the world is in the " . $path . "resources/ directory");
			}
			$plugin->saveResource($world . ".zip", true);
			$this->arenas[] = new BedWarsArena($world, $mode, $radius, $items, ...$spawns);
		}
		$plugin->getScheduler()->scheduleRepeatingTask(new BedWarsTask(), 20);
	}
	
	public function getArenas() : array{
		return $this->arenas;
	}
	
	public function getAvailableArenas(int $mode) : array{
		$ret = [];
		foreach($this->arenas as $arena){
			if($arena->isSetup() && $arena->getGameStatus() === BedWarsArena::GAME_STATUS_INACTIVE && $arena->getGameMode() === $mode){
				$ret[] = $arena;
			}
		}
		shuffle($ret);
		return $ret;
	}
	
	public static function getGameModeString(int $mode) : string{
		switch($mode){
			case Main2::BEDWARS_MODE_NORMAL:
			   return "Normal";
			case Main2::BEDWARS_MODE_CUSTOM:
			   return "Custom";
		}
		return "Unknown";
	}
	
	/**
	 * @internal
	 */
	public function isQueued(Player $player) : bool{
		foreach($this->queue as $mode => $players){
			if(in_array($player->getName(), $players)){
				return true;
			}
		}
		return false;
	}
	
	public function dequeuePlayer(Player $player) : bool{
		foreach($this->queue as $mode => $players){
			if(in_array($player->getName(), $players)){
				unset($this->queue[$mode][array_search($player->getName(), $this->queue[$mode])]);
				return true;
			}
		}
		return false;
	}
	
	public function enqueuePlayer(Player $player, int $mode) : bool{
		$player->sendMessage("coming-soon");
		return false;
		
		if(!$this->isQueued($player)){
			if($mode !== Main2::BEDWARS_MODE_NORMAL && $mode !== Main2::BEDWARS_MODE_CUSTOM){
				throw new \InvalidArgumentException($mode . " is not a valid BedWars game mode");
			}
			$this->queue[$mode][] = $player->getName();
			return true;
		}
		return false;
	}
	
	public function removeSpectator(Player $player) : bool{
		foreach($this->arenas as $arena){
			if($arena->isSpectating($player)){
				$arena->removeSpectator($player);
				return true;
			}
		}
		return false;
	}
	
	public function removeFromArena(Player $player) : ?BedWarsArena{
		foreach($this->arenas as $arena){
			if($arena->isPlaying($player)){
				$arena->removePlayer($player);
				return $arena;
			}
		}
		return null;
	}
	
	public function getArenaByPlayer(Player $player) : ?BedWarsArena{
		foreach($this->arenas as $arena){
			if($arena->isPlaying($player)){
				return $arena;
			}
		}
		return null;
	}
	
	public function getArenaBySpectator(Player $player) : ?BedWarsArena{
		foreach($this->arenas as $arena){
			if($arena->isSpectating($player)){
				return $arena;
			}
		}
		return null;
	}
	
	public function getQueue() : array{
		return $this->queue;
	}
}