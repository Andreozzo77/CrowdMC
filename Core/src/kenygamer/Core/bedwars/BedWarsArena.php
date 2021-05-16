<?php

declare(strict_types=1);

namespace kenygamer\Core\bedwars;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use pocketmine\tile\Chest;

use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\RestorableInventoryManager;

use xenialdan\BossBar\BossBar;

class BedWarsArena{
	
	/** @var string[] Holds a list of players who joined the game. Makes awarding possible. */
	private $playersSave = [];
	/** @var string[] */
	private $blueTeam = [];
	/** @var string[] */
	private $redTeam = [];
	/** @var array */
	private $teamBeds = [];
	
	/** @var string[] */
	private $players = [];
	/** @var string[] */
	private $spectators = [];
	/** @var bool */
	private $gameStatus = self::GAME_STATUS_INACTIVE;
	/** @var Level|null */
	private $level = null;
	/** @var int Remaining game time */
	private $gameTime = self::GAME_TIME;
	/** @var array */
	private $invulnerable = [];
	
	/** @var int */
	private $gameMode = -1;
	/** @var string */
	private $world = "";
	/** @var Item[] */
	private $items = [];
	/** @var Vector3[] */
	private $spawns = [];
	/** @var int */
	private $spawnRadius;
	/** @var BossBar|null */
	private $bossbar = null;
	/** @var bool */
	private $isSetup = false;
	
	private const COUNTDOWN_TIME = 30;
	private const GAME_TIME = 600;
	private const INVULNERABILITY_TIME = 15;
	private const ITEMS_PER_CHEST = 5;
	
	public const GAME_STATUS_RUNNING = 2;
	public const GAME_STATUS_COUNTDOWN = 1;
	public const GAME_STATUS_INACTIVE = 0;
	
	public const TEAM_BLUE = "blue";
	public const TEAM_RED = "red";
	
	public function __construct(string $world, int $gameMode, int $spawnRadius, array $items, array ...$spawns){
		$this->isSetup = false;
		$this->spawns = [];
		foreach($spawns as $spawn){
			if(!isset($spawn["x"]) || !isset($spawn["y"]) || !isset($spawn["z"])){
				throw new \RuntimeException("Incomplete spawn data for arena " . $world);
			}
			$this->spawns[] = new Location((int) $spawn["x"], (int) $spawn["y"], (int) $spawn["z"], (int) ($spawn["yaw"] ?? 0), ($spawn["pitch"] ?? 0));
		}
		if(count($this->spawns) < 2 || count($this->spawns) % 2 !== 0){
			throw new \RuntimeException("Arena " . $world . " has less than two spawns or odd spawns (" . count($this->spawns) . "), it will not be setup");
		}
		$this->playersSave = [];
		if($gameMode !== Main2::BEDWARS_MODE_NORMAL && $gameMode !== Main2::BEDWARS_MODE_CUSTOM){
			throw new \RuntimeException("Invalid game mode " . $gameMode . " for arena " . $world);
		}
		$this->items = $items;
		$this->gameMode = $gameMode;
		$this->world = $world;
		$this->radius = $spawnRadius;
		$this->bossbar = null;// new BossBar();
		
		$this->isSetup = true;
	}
	
	public function isSetup() : bool{
		return $this->isSetup;
	}
	
	public function tickGame() : bool{
		if(!$this->isSetup()){
			$this->gameStatus = self::GAME_STATUS_INACTIVE;
			return false;
		}

		
		switch($this->getGameStatus()){
			case self::GAME_STATUS_INACTIVE:
			    $spawnsCount = count($this->spawns);
			    if(count($this->players) === $spawnsCount){
			    	$this->gameStatus = self::GAME_STATUS_COUNTDOWN;
			    	$this->blueTeam = array_slice($this->playersSave, 0, $spawnsCount / 2);
			    	$this->redTeam = array_slice($this->playersSave, $spawnsCount / 2, $spawnsCount / 2);
			    	break;
			    }
			    break;
			case self::GAME_STATUS_COUNTDOWN:
			    if(count($this->players) < 2){
			    	$this->endGame(null);
			    	break;
			    }
			    $this->loadLevel();
			    $this->tick();
		        
			    if(self::GAME_TIME - $this->gameTime >= self::COUNTDOWN_TIME){
			    	
			        foreach($this->players as $player){
			        	$p = Server::getInstance()->getPlayerExact($player);
			        	if($p !== null){
			        		$p->setImmobile(false);
			        		$this->resetPlayerAttributes($p);
			        		$p->sendMessage("invulnerable", self::INVULNERABILITY_TIME);
			        		$p->addTitle(LangManager::translate("bedwars", $p), LangManager::translate("bedwars-start-title", $p), 15, 15, 15);
			        		RestorableInventoryManager::getInstance()->saveInventory($p);
			        	}
			        	$this->invulnerable[$player] = time() + self::INVULNERABILITY_TIME;
			        }
			        
			        $this->loadSpawnRadius($level = $this->getLevel());
			        
			        //Chest Filling
			        foreach($level->getTiles() as $tile){ 
			            if($tile instanceof Chest){
			            	$chest = $tile->getInventory();
			            	$chest->clearAll();
			            	
			            	shuffle($this->items);
			            	$maxIndex = min(self::ITEMS_PER_CHEST, $chest->getSize(), count($this->items)) - 1;
			            	for($index = 0; $index < $maxIndex; $index++){
			            		$chest->setItem($index, $this->items[$index]->setCount(\kenygamer\Core\Main::mt_rand(0, max(1, $this->items[$index]->getCount()))));
			            	}
			            }
			        }
			        $this->teamBeds[self::TEAM_BLUE] = $this->blueTeam;
			        $this->teamBeds[self::TEAM_RED] = $this->redTeam; 
			        $level->setTime(Level::TIME_DAY);
			        $this->broadcastMessage("bedwars-start");
			        $this->gameStatus = self::GAME_STATUS_RUNNING; //Set game to running after teleporting the players
			    }else{
			    	if($this->gameTime % 5 === 0){
			    		foreach($this->getPlayers() as $player){
			    			$p = Server::getInstance()->getPlayerExact($player);
			    			if($p !== null){
			    				$p->addTitle(LangManager::translate("bedwars", $p), LangManager::translate("bedwars-countdown", $p, self::COUNTDOWN_TIME - (self::GAME_TIME - $this->gameTime)), 10, 10, 10);
			    			}
			    		}
			    	}
			    }
			    break;
			case self::GAME_STATUS_RUNNING:
			    $this->tick();
			    switch(count($this->players)){
			    	case 0:
			    	    $this->endGame(null);
			    	    break 2;
			    	case 1:
			    	    $redBeds = count($this->teamBeds[self::TEAM_RED]);
			    	    $blueBeds = count($this->teamBeds[self::TEAM_BLUE]);
			    	    if($redBeds === $blueBeds){
			    	    	$this->endGame(null);
			    	    }else{
			    	    	$this->endGame($redBeds > $blueBeds ? self::TEAM_RED : self::TEAM_BLUE);
			    	    }
			    	    break 2;
			    	default:
			    	    foreach([self::TEAM_RED, self::TEAM_BLUE] as $team){
			    	    	if(count($this->teamBeds[$team]) < 1){
			    	    		$this->endGame($team === self::TEAM_RED ? self::TEAM_BLUE : self::TEAM_RED);
			    	    		break 3;
			    	    	}
			    	    }
			    }
			    
			    $aliveBlue = 0;
			    foreach($this->blueTeam as $player){
			    	if($this->isPlaying($player)){
			    		$aliveBlue++;
			    	}
			    }
			    $aliveRed = 0;
			    foreach($this->redTeam as $player){
			    	if($this->isPlaying($player)){
			    		$aliveRed++;
			    	}
			    }
			    
			    $all = $this->getPlayers() + $this->getSpectators();
			    foreach($all as $player){
			    	$p = Server::getInstance()->getPlayerExact($player);
			    	if($p !== null){
			    		if(isset($this->invulnerable[$player]) && time() >= $this->invulnerable[$player]){
			    			unset($this->invulnerable[$player]);
			    			$p->sendMessage("notinvulnerable");
			        	}
			        	
			    		$p->sendPopup("\n\n" . LangManager::translate($this->isPlaying($p) ? "bedwars-tip-game" : "bedwars-tip-spectator", count($this->blueTeam), $aliveBlue, count($this->redTeam), $aliveRed));
			    		if($p->getLevel()->getFolderName() !== $this->getWorldName()){
			    			$level = $this->getLevel();
			    			$p->teleport($level->getSpawnLocation());
			    		}
			    		if(stripos($p->getDisplayName(), $this->getPlayerTeam($p)) === false){
			    			$p->setDisplayName(($this->getPlayerTeam($p) === self::TEAM_BLUE ? TextFormat::BLUE . "[Blue]" : TextFormat::RED . "[Red]") . $p->getDisplayName());
			    		}
			    	}
				}
			    if($this->gameTime <= 0){
			    	$this->endGame(null);
			    }
			    break;
		}
		return true;
	}
	
	private function tick() : void{
		$this->gameTime--;
		if($this->bossbar === null){
		    return;
		}
		$this->bossbar->setTitle(TextFormat::colorize(LangManager::translate("bedwars-timer", Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft(time() + $this->gameTime), TextFormat::AQUA, TextFormat::AQUA))));
		$this->bossbar->setPercentage((self::GAME_TIME - $this->gameTime) / self::GAME_TIME);
	}
	
	/**
	 * @return string
	 */
	public function getWorldName() : string{
		return $this->world;
	}
	
	/**
	 * Returns the level of this arena. Tries to load the level first.
	 * @return Level  
	 */
	public function getLevel() : Level{
		$this->loadLevel();
		return Server::getInstance()->getLevelByName($this->getWorldName());
	}
	
	/**
	 * Loads the level. Does not return anything.
	 * @see BedWarsArena::getLevel()
	 */
	private function loadLevel() : void{
		$worldName = $this->getWorldName();
		$level = Server::getInstance()->getLevelByName($worldName);
		if($level === null){
			$worldPath = Main::getInstance()->getDataFolder() . $this->getWorldName() . ".zip";
			if(!file_exists($worldPath) || trim(preg_replace('/\\\\/', '', $worldPath)) === ""){
				throw new \RuntimeException($worldPath . " is not a file");
			}
			$worldsPath = Server::getInstance()->getDataPath() . "worlds/";
			if(is_dir($worldsPath . $worldName)){
				Server::getInstance()->getLogger()->warning("Deleting world " . $worldName . "...");
				shell_exec("rm -rf \"" . addslashes($worldsPath . $worldName) . "\"");
			}
			$command = "scp \"" . addslashes($worldPath) . "\" \"" . addslashes($worldsPath) . "\" 2>&1; echo $?";
			$exitCode = shell_exec($command);
			if($exitCode != 0){ //Loosely check, evaluates 0\n == 0 true
			    throw new \RuntimeException("Copy operation failed with status code " . strval($exitCode));
			}
			$zip = new \ZipArchive();
			if(!$zip->open($worldPath)){
				throw new \RuntimeException("Cannot open Zip " . $worldPath);
			}
			$zip->extractTo($worldsPath);
			$zip->close();
		}
			        
	    Server::getInstance()->loadLevel($worldName);
	    $level = Server::getInstance()->getLevelByName($worldName);
	    if($level === null){
	    	throw new \RuntimeException("Failed to load level " . $worldName);
	    }
	}
	
	/**
	 * @param Level $level
	 */
	private function loadSpawnRadius(Level $level) : void{
		$spawn = $level->getSpawnLocation(); 
		for($x = $spawn->getX() - ($this->spawnRadius / 2); $x < $spawn->getX() + ($this->spawnRadius / 2); $x += 16){
			for($z = $spawn->getZ() - ($this->spawnRadius / 2); $z < $spawn->getZ() + ($this->spawnRadius / 2); $z += 16){
				$level->loadChunk($x >> 4, $z >> 4); //$force is redundant since we don't want a placeholder - that is, load chunks that don't exist
			}
		}
	}
	
    /**
     * @param string $team
	 * @return string[]
	 */
	private function getTeamPlayers(string $team) : array{
		switch($team){
			case self::TEAM_BLUE:
			    return $this->blueTeam;
			    break;
			case self::TEAM_RED:
			    return $this->redTeam;
			    break;
			default:
			    throw new \InvalidArgumentException("Invalid team $team passed to " . __METHOD__);
		}
    }
    
    public function getPlayerTeam($player) : string{
    	if($this->gameStatus === self::GAME_STATUS_INACTIVE){
    		throw new \LogicException("Game is not active");
    	}
    	if($player instanceof Player){
    		$player = $player->getName();
    	}
    	if(in_array($player, $this->blueTeam)){
    		return self::TEAM_BLUE;
    	}
    	if(in_array($player, $this->redTeam)){
    		return self::TEAM_RED;
    	}
    	throw new \BadMethodCallException($player . " is not joined to a BedWars game");
    }
    
	/**
	 * @return string[]
	 */
	public function getRedTeam() : array{
		return $this->redTeam;
	}
	
	/**
	 * @return string[]
	 */
	public function getPlayers() : array{
		return $this->players;
	}
	
	/**
	 * @return string[]
	 */
	public function getPlayersSave() : array{
		return $this->playersSave;
	}
	
	/**
	 * @return Location[] 
	 */
	public function getSpawns() : array{
		return $this->spawns;
	}
	
	/**
	 * @param string|Player
	 * @return bool
	 */
	public function isInvulnerable($player) : bool{
		if($player instanceof Player){
			$player = $player->getName();
		}
		return isset($this->invulnerable[$player]);
	}
	
	/**
	 * @param string|Player $player
	 * @return bool
	 */
	public function isPlaying($player) : bool{
		if($player instanceof Player){
			$player = $player->getName();
		}
		foreach($this->players as $p){
			if($p === $player){
				return true;
			}
		}
		return false;
	}
	
	private function removePlayers() : void{
		foreach($this->getPlayers() as $player){
			$this->removePlayer($player);
		}
	}
	
	/**
	 * @param string|Player $player
	 */
	public function removePlayer($player) : void{
		if($player instanceof Player){
			$player = $player->getName();
		}
		foreach($this->players as $i => $p){
			if($p === $player){
				unset($this->players[$i]);
				break;
			}
		}
		$this->players = array_values($this->players); //Reset keys
		
		$p = Server::getInstance()->getPlayerExact($player);
		if($p !== null){
			$this->resetPlayerAttributes($p);
			$p->setImmobile(false);
			if($p->isOnline()){
				if($this->getGameStatus() === self::GAME_STATUS_RUNNING){
					$this->addSpectator($p);
				}
			}else{
				$p->teleport($p->getServer()->getDefaultLevel()->getSpawnLocation());
			}
			if($this->bossbar !== null){
				$this->bossbar->removePlayer($p);
			}
			$bossbar = Main::getInstance()->getPlugin("BossBar");
			if($this->bossbar !== null && $bossbar->bossbar !== null){
				$bossbar->bossbar->removePlayer($p);
				$bossbar->bossbar->addPlayer($p);
			}
			RestorableInventoryManager::getInstance()->returnInventory($p);
			$p->setDisplayName(TextFormat::clean(str_replace("[" . ucfirst($this->getPlayerTeam($p)) . "]", "", $p->getDisplayName())));
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function resetPlayerAttributes(Player $player) : void{
		$player->setGameMode(Player::SURVIVAL);
		$player->setFood($player->getMaxFood());
		$player->setHealth($player->getMaxHealth());
		$player->removeAllEffects();
		$player->setFlying(false);
		$player->setAllowFlight(false);
	}
	
	/**
	 * @param Player $player
	 */
	public function addPlayer(Player $player) : void{
		if(Main2::getBedWarsManager()->getArenaByPlayer($player) || Main2::getBedWarsManager()->getArenaBySpectator($player)){
			throw new \BadMethodCallException("Cannot join " . $player . " to two arenas concurrently");
		}
		if(!$this->isPlaying($player)){
			if(count($this->players) + 1 > count($this->spawns)){
				throw new \BadMethodCallException("Cannot add more players to this BedWars game");
			}
			$this->players[] = $player->getName();
			
			$this->players = array_values($this->players); //Reset keys
			
			$this->playersSave[] = $player->getName();
			
			$bossbar = Main::getInstance()->getPlugin("BossBar");
			if($this->bossbar !== null && $bossbar->bossbar !== null){
				$bossbar->bossbar->removePlayer($player);
			}
			if($this->bossbar !== null){
				$this->bossbar->addPlayer($player);
			}
			
			$i = array_search($player->getName(), $this->playersSave);
			$level = $this->getLevel();
			$player->teleport(Location::fromObject($this->spawns[$i]->asVector3(), $level, $this->spawns[$i]->yaw, $this->spawns[$i]->pitch));
			$player->setImmobile(true);
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function addSpectator(Player $player) : void{
		if(Main2::getBedWarsManager()->getArenaByPlayer($player) || Main2::getBedWarsManager()->getArenaBySpectator($player)){
			throw new \BadMethodCallException("Cannot join " . $player . " to two arenas concurrently");
		}
		if($player->isOnline()){
			$player->teleport($player->getLevel()->getSpawnLocation());
			$this->spectators[] = $player->getName();
			$player->setGameMode(Player::SPECTATOR);
			$player->sendMessage("bedwars-spectator-1");
		}
	}
	
	/**
	 * @param string|Player $player
	 * @return bool
	 */
	public function isSpectating($player) : bool{
		if($player instanceof Player){
			$player = $player->getName();
		}
		foreach($this->spectators as $p){
			if($p === $player){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @param string|Player $player
	 */
	public function removeSpectator($player) : void{
		if($player instanceof Player){
			$player = $player->getName();
		}
		foreach($this->spectators as $i => $spectator){
			if($player === $spectator){
				unset($this->spectators[$i]);
				$p = Server::getInstance()->getPlayerExact($player);
				if($p !== null){
					$p->sendMessage("bedwars-spectator-2");
					$p->setGamemode(Player::SURVIVAL);
					$p->teleport($p->getServer()->getDefaultLevel()->getSpawnLocation());
				}
				break;
			}
		}
	}
	
	/**
	 * @return string[]
	 */
	private function getSpectators() : array{
		return $this->spectators;
	}
	
	private function removeSpectators() : void{
		foreach($this->getSpectators() as $spectator){
			$this->removeSpectator($spectator);
		}
	}
	
	/**
	 * @return int
	 */
	public function getGameStatus() : int{
		return $this->gameStatus;
	}
	
	/**
	 * @param string $key
	 * @param mixed ...$args
	 */
	public function broadcastMessage(string $key, ...$args) : void{
		foreach($this->players + $this->spectators as $recipient){
			$player = Server::getInstance()->getPlayerExact($recipient);
			if($player !== null){
				$player->sendMessage($key, ...$args);
			}
		}
	}
	
	/**
	 * @return int
	 */
	public function getGameMode() : int{
		return $this->gameMode;
	}
	
	public function destroyBed(string $player) : void{
		if($this->getGameStatus() !== self::GAME_STATUS_RUNNING){
			throw new \BadMethodCallException("BedWars game is not running");
		}
		$team = $this->getPlayerTeam($player);
		if(!in_array($player, $this->teamBeds[$team])){
			return;
		}
		unset($this->teamBeds[$team][array_search($player, $this->teamBeds[$team])]);
		$p = Server::getInstance()->getPlayerExact($player);
		if($p !== null){
			$player = $p->getDisplayName();
			$p->addTitle(LangManager::translate("bedwars"), LangManager::translate("bedwars-yourbed"), 15, 15, 15);
		}
		$this->broadcastMessage("bedwars-bed", $player, ucfirst($team), count($this->teamBeds[$team]), count($this->teamBeds[$team]));
	}
			
	
	
	
	/**
	 * @param ?string $winnerTeam
	 */
	private function endGame(?string $winnerTeam) : void{
		$this->removeSpectators();
		$this->removePlayers();
		
		if($winnerTeam === null){
			LangManager::broadcast("bedwars-indeterminate");
		}else{
			$reward = strval(0);
			$winners = $this->getTeamPlayers($winnerTeam);
			foreach($this->playersSave as $player){
				if(!in_array($player, $winners)){
					$balance = Main::getInstance()->myMoney($player);
					$steal = $balance * 0.2 / 100;
					if($steal >= 1){
						$reward = bcadd($reward, (string) $steal);
						Main::getInstance()->reduceMoney($player, $steal);
					}
				}
			}
			foreach($winners as $winner){
				Main::getInstance()->addMoney($winner, $reward / count($winners));
			}
			LangManager::broadcast("bedwars-win", ucfirst($winnerTeam), implode(", ", $winners), $reward > 0x7fffffff ? $reward : number_format((int) $reward));
		}
		
		$this->playersSave = [];
		$this->blueTeam = [];
		$this->redTeam = [];
		$this->teamBeds[self::TEAM_BLUE] = [];
		$this->teamBeds[self::TEAM_RED] = [];
		$this->invulnerable = [];
		$this->gameTime = self::GAME_TIME;
		$this->gameStatus = self::GAME_STATUS_INACTIVE;
		
		$world = $this->getWorldName();
		$level = $this->getLevel();
		Server::getInstance()->unloadLevel($level);
		if(trim($world) !== ""){ //Safety check
		    shell_exec("rm -rf \"" . addslashes(Server::getInstance()->getDataPath() . "worlds/" . $world) . "\"");
		}
	}
	
}