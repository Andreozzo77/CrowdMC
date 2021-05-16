<?php

declare(strict_types=1);

namespace kenygamer\Core\duel;

use pocketmine\Player;
use pocketmine\math\AxisAlignedBB;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\level\sound\ClickSound;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;

use xenialdan\BossBar\BossBar;
use LegacyCore\Tasks\ScoreHudTask;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\RestorableInventoryManager;

/**
 * Class for handling most of the duels core including game and arena functionality wise
 *
 * @class DuelArena
 */
final class DuelArena{
    
    //TODO: split $players into $player1 and $player2 for less confusion. Current checks rely on confusing foreach loops
    //otherwise players are accessed indexically which makes it very unpractical.
    
    /** @var AxisAlignedBB */
    private $area;
    /** @var string */
    private $name;
    /** @var Position */
    private $spawnpoint1, $spawnpoint2;
    /** @var Level */
    private $level;
    /** @var int[] */
    private $duelTypes;
    
    /** @var Player[] */
    private $playing = [];
    /** @var Player[] */
    private $spectating = [];
    /** @var int Used for countdown before the game starts */
    private $countdown = 10;
    /** @var BossBar|null */
    private $bossbar = null;
    /** @var int|null The Unix timestamp of when the duel started */
    private $startTime = null;
    /** @var bool */
    private $returnInventory = false;
    /** @var Item[] */
    private $duelKit = [];
    /** @var int */
    private $duelType = -1;
    /** @var int Set to inactive by DuelListener when player is killed, also accessed by DuelTask for free arena checking */
    public $gameStatus = self::GAME_STATUS_INACTIVE;
    
    public const GAME_STATUS_INACTIVE = -1;
    public const GAME_STATUS_COUNTDOWN = 0;
    public const GAME_STATUS_ACTIVE = 1;
    
    public const DUEL_TIME = 241;
    
    /** @var bool An unavailable arena should not be joinable from the waiting queue */
    public static $isAvailable = false;
    
    /**
     * DuelArena constructor.
     *
     * @param string $name The name with which this arena identifies. Must be unique
     * @param string $pos1 First position of the arena
     * @param string $pos2 Second position of the arena
     * @param string $spawnpoint1 First player spawn point
     * @param string $spawnpoint2 Second player spawn point
     * @param string $level The level name in which the arena is at
     * @param int[] $duelTypes An array of constants with the duel types that can be played in this arena
     */
    public function __construct(string $name, string $pos1, string $pos2, string $spawnpoint1, string $spawnpoint2, string $level, array $duelTypes){
        $this->name = $name;
        list($pos1X, $pos1Y, $pos1Z) = explode(":", $pos1);
        list($pos2X, $pos2Y, $pos2Z) = explode(":", $pos2);
        $this->area = new AxisAlignedBB((float) min($pos1X, $pos2X), (float) min($pos1Y, $pos2Y), (float) min($pos1Z, $pos2Z), (float) max($pos1X, $pos2X), (float) max($pos1Y, $pos2Y), (float) max($pos1Z, $pos2Z));
        $this->level = Server::getInstance()->getLevelByName($level);
        if(!Server::getInstance()->isLevelLoaded($level)){
            if(!Server::getInstance()->isLevelGenerated($level)){
                Server::getInstance()->getLogger()->error("[Duels] World " . $level . " does not exist");
                return;
            }else{
                Server::getInstance()->loadLevel($level);
            }
        }
        $this->level = Server::getInstance()->getLevelByName($level);
        if(!$this->level instanceof Level){
            Server::getInstance()->getLogger()->error("[Duels] World " . $level . " could not be loaded");
            return;
        }else{
            list($x, $y, $z, $yaw) = explode(":", $spawnpoint1);
            $this->spawnpoint1 = new Location((float) $x, (float) $y, (float) $z, (float) $yaw, 0, $this->level);
            list($x, $y, $z, $yaw) = explode(":", $spawnpoint2);
            $this->spawnpoint2 = new Location((float) $x, (float) $y, (float) $z, (float) $yaw, 0, $this->level);
        }
        $this->duelTypes = $duelTypes;
        self::$isAvailable = true;
    }
    
    /**
     * Ticks the arena, duel being currently held, etc...
     */
    public function tickArena() : void{
    	/** @var \kenygamer\Core\Main */
    	$Core = Server::getInstance()->getPluginManager()->getPlugin("Core");
    	
        switch($this->gameStatus){
            case self::GAME_STATUS_INACTIVE:
                if(!$this->checkAllPlayers()){
                	break;
                }
                if(count($this->playing) === 2 && $this->playing[0]->isOnline() && $this->playing[1]->isOnline()){
                    $this->gameStatus = self::GAME_STATUS_COUNTDOWN;
                    
                    //Teleport to the arena
                    $this->playing[0]->teleport($this->getSpawnpoint1());
                    $this->playing[1]->teleport($this->getSpawnpoint2());
                    
                    //Save the previous player inventory and set to the duel's kit (if KitPvP)
                    if($this->isKitPvp()){
                    	foreach($this->playing as $player){
                    		Main::getInstance()->scheduleDelayedCallbackTask([Main::getInstance(), "closeWindow"], 11, $player, ContainerIds::UI);
                    		RestorableInventoryManager::getInstance()->saveInventory($player);
                    		if($player->getInventory() !== null){
                    			$player->getInventory()->setContents($this->duelKit);
                    		}
                    		if($player->getArmorInventory() !== null){
                    			$player->getArmorInventory()->setContents([]);
                    		}
                        }
                    }
                    
                    LangManager::send("duel-preparation", $this->playing[0], $this->playing[1]->getName());
                    LangManager::send("duel-preparation", $this->playing[1], $this->playing[0]->getName());
                    
                    foreach($this->playing as $player){
                    	if(!$this->isKitPvp() && $this->getDuelType() !== Main::DUEL_TYPE_FRIENDLY){
                    		LangManager::send("duel-logoff", $player);
                    	}
                    	//$player->setFlying(true);
                    	$player->setImmobile(true);
                    }
                }
                break;
            case self::GAME_STATUS_COUNTDOWN:
                if($this->checkAllPlayers()){
					foreach($this->playing as $player){
						$player->getLevel()->addSound(new ClickSound($player->asVector3()));
					}
					
                    if(--$this->countdown === 0){
                        $this->countdown = self::DUEL_TIME;
                        $this->gameStatus = self::GAME_STATUS_ACTIVE;
                        $this->startTime = time();
                           
                        $this->bossbar = new BossBar();
                        foreach($this->playing as $player){
                        	//$player->setFlying(false);
                        	$player->setImmobile(false);
                        	Main::getInstance()->getPlayerBossBar($player)->removePlayer($player);
                            $this->bossbar->addPlayer($player);
                            
                            $opponent = "";
                            foreach($this->playing as $p){
                            	if($p->getName() !== $player->getName()){
                            		$opponent = $p->getName();
                            	}
                            }
                            $player->sendMessage("duel-started-player", $opponent);
                            $player->addTitle(LangManager::translate("duel-started-2", $player), LangManager::translate("go", $player), 9, 9, 9);
                            ScoreHudTask::$mainHudOff[$player->getName()] = true;
                            ScoreHudTask::getInstance()->rmScoreboard($player, "objektName");
                        }
                    }elseif(in_array($this->countdown, array_keys($color = [
                            9 => "&9", 8 => "&9", 7 => "&9", 6 => "&9", 5 => "&a",
                            4 => "&a", 3 => "&a", 2 => "&e", 1 => "&c"
                        ]))){
                        foreach($this->playing as $player){
                            $player->addTitle(TextFormat::colorize($color[$this->countdown] . strval($this->countdown)), "", 3, 3, 3);
                        }
                    }
                }else{
                    $this->gameStatus = self::GAME_STATUS_INACTIVE;
                }
                break;
            case self::GAME_STATUS_ACTIVE:
                if($this->checkAllPlayers()){
                	$duelName = $Core->getDuelName($this->getDuelType());
                    if(time() >= $this->startTime + self::DUEL_TIME){
                        foreach($this->playing as $player){
                        	LangManager::send("duel-nowinner-player", $player);
                        }
                        LangManager::broadcast("duel-nowinner-broadcast", $this->playing[0]->getName(), $this->playing[1]->getName(), $duelName);
                        goto SetGameInactive;
                    }
                    /** @var int Seconds left for the duel to timeout */
                    $diff = ($this->startTime + self::DUEL_TIME) - time(); 
                    //Cant localize fk
                    if($this->bossbar !== null){
                    	$this->bossbar->setTitle("Duel time: " . $Core->formatTime($Core->getTimeLeft($this->startTime + self::DUEL_TIME), TextFormat::WHITE, TextFormat::WHITE));
                    	$this->bossbar->setPercentage((1 / self::DUEL_TIME) * $diff);
                    }
                    $utils = ScoreHudTask::getInstance();
                    foreach($this->playing as $player){
                    	$utils->sendCombatScoreboard($player);
                    	if(count($this->spectating) > 0){
                    		$player->sendPopup(LangManager::translate("duel-spectatorpopup", $player, count($this->spectating)));
                    	}
                    	if($this->duelType === Main::DUEL_TYPE_SPLEEF xor $this->duelType === Main::DUEL_TYPE_TNTRUN){
                    		$player->setFood($player->getMaxFood());
                    	}
                    	if($this->duelType === Main::DUEL_TYPE_TNTRUN){
                    		$player->getInventory()->removeItem(ItemFactory::get(Item::FLINT_AND_STEEL));
                    		$maxAfk = 3;
                    		if((isset(DuelListener::$lastStep[$player->getName()]) && time() - DuelListener::$lastStep[$player->getName()][1] >= $maxAfk) || (!isset(DuelListener::$lastStep[$player->getName()]) && self::DUEL_TIME - $diff >= $maxAfk)){
                    			$player->teleport($player->subtract(0, 3, 0));
                    		}
                    	}
                    }
                    foreach($this->spectating as $spectator){
                    	$spectator->sendPopup(LangManager::translate("duel-spectatingpopup", $spectator, $duelName));
                    }
                }else{
                    SetGameInactive: {
                        $this->gameStatus = self::GAME_STATUS_INACTIVE;
                    }
                }
                break;
        }
    }
    
    //TODO: remove exposing all duel fields, make a method to begin every single duel type starting off two players
    //TODO: remove depending on this shitty config to use the combat logger
    
    /**
     * The correct way to begin a duel.
     *
     * @param Item[] $duelKit The kit contents, if none specified current inventory will be kept
     * @param bool $returnInventory Return the player inventory when the duel ends
     * @param int $duelType
     * @param Player ...$players
     */
    public function startDuel(array $duelKit, int $duelType, bool $returnInventory, Player ...$players){
    	$this->resetProperties();
        $this->duelKit = $duelKit;
        $this->duelType = $duelType;
        $this->returnInventory = $returnInventory;
        foreach($players as $player){
            $this->addPlayer($player);
        }
    }
    
    /**
     * Helper function for resetting the arena's class dynamic properties back to default
     * so that a new duel can be further handled.
     */
    private function resetProperties() : void{
    	$this->playing = [];
    	$this->spectating = [];
    	$this->countdown = 10;
    	$this->bossbar = null;
    	$this->startTime = null;
    	$this->duelType = -1;
    	$this->duelKit = [];
    }
        
    
    /**
     * Internal method. Teleports players that go outside the arena, finishes a duel.
     * Does the same process if the game status is inactive when a duel was previously running
     *
     * @return bool
     */
    private function checkAllPlayers() : bool{
        if(!isset($this->playing[0]) || !isset($this->playing[1])){
        	return false; //unused return
        }
        $inactive = $this->gameStatus === self::GAME_STATUS_INACTIVE;
        if((!($this->playing[0]->isOnline() && $this->playing[1]->isOnline()) || $inactive) && $this->startTime !== null){ //Finishes the game whether if player went offline or the game timed out
            if(!$inactive){
            	//Removed this redundant code. Quit is now handled in the event listener.
            }
            foreach($this->playing as $player){
                $this->removePlayer($player);
            }
            foreach($this->spectating as $player){
                $this->removeSpectator($player);
            }
            if($this->duelType === Main::DUEL_TYPE_SPLEEF xor $this->duelType === Main::DUEL_TYPE_TNTRUN){
                DuelListener::fillBrokenBlocks(spl_object_hash($this), $this->duelType);
            }
            
            return false;
        }
        if($inactive){
        	return true;
        }
        foreach($this->playing + $this->spectating as $i => $player){
            if(!$this->area->isVectorInside($player->asVector3()) || $player->getLevel()->getFolderName() !== $this->level->getFolderName()){
            	LangManager::send("duel-outsidearena", $player);
                if($this->isPlaying($player) !== false){
                    $spawn = "getSpawnpoint" . strval($i + 1);
                    $player->teleport($this->$spawn());
                }else{
                    $player->teleport($this->getAreaTopMiddle());
                }
            }
        }
        return true;
    }
    
    
    /**
     * Returns the arena name
     *
     * @return string
     */
    public function getName() : string{
    	return $this->name;
    }
    
    /**
     * Returns whether this is a kit-pvp duel or not.
     *
     * @return bool
     */
    public function isKitPvp() : bool{
        return $this->returnInventory && !empty($this->duelKit);
    }
    
    /**
     * Returns the first spawnpoint
     *
     * @return Position
     */
    public function getSpawnpoint1() : Position{
        return $this->spawnpoint1;
    }
    
    /**
     * Returns the second spawnpoint
     *
     * @return Position
     */
    public function getSpawnpoint2() : Position{
        return $this->spawnpoint2;
    }
    
    /**
     * Returns the level in which the arena is
     *
     * @return Level
     */
    public function getLevel() : Level{
        return $this->level;
    }
    
    /**
     * Returns the bounding box of the arena
     *
     * @return AxisAlignedBB
     */
    public function getArea() : AxisAlignedBB{
        return $this->area;
    }
    
    /**
     * Returns the top middle of the bounding box for this arena
     *
     * @return Position
     */
    public function getAreaTopMiddle() : Position{
        return new Position(($this->area->minX + $this->area->maxX) / 2, $this->area->maxY - 2, ($this->area->minZ + $this->area->maxZ) / 2, $this->level);
    }
    
    /**
     * Returns the duel types that are allowed in this arena
     *
     * @return int[]
     */
    public function getDuelTypes() : array{
        return $this->duelTypes;
    }
    
    /**
     * Get the duel type being played live
     *
     * @return int|null
     */
    public function getDuelType() : ?int{
        return $this->duelType;
    }
        
    /**
     * Adds a player to the game. If the arena is ticked, the game should start when there are two online players
     *
     * @param Player $player
     * @return bool
     */
    public function addPlayer(Player $player){
        if($this->isPlaying($player) === false && $this->isSpectating($player) === false){
            $this->playing[] = $player;
            $player->setFood($player->getMaxFood());
            $player->setGamemode(Player::SURVIVAL);
            $player->setAllowFlight(false);
            //$player->setFlying(false);
            $player->setImmobile(true);
            LangManager::send("duel-playingarena", $player, $this->name);
            return true;
        }
        return false;
    }
    
    /**
     * Removes the player from the duel game
     *
     * @param Player $player
     * @return bool
     */
    public function removePlayer(Player $player){
        if(($i = $this->isPlaying($player)) !== false){
        		
            unset($this->playing[$i]);
            if($player->isOnline()){
            	$player->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());
            }
            $player->setHealth($player->getMaxHealth());
            $player->removeAllEffects();
            //$player->setFlying(false);
            $player->setImmobile(false);
            if($this->isKitpvp()){
            	RestorableInventoryManager::getInstance()->returnInventory($player);
            }elseif(!$this->isKitPvp()){
                //DuelListener does this
            }else{
                //DuelListener does this
            }
            
            unset(ScoreHudTask::$mainHudOff[$player->getName()]);
            if($this->bossbar !== null){
            	$this->bossbar->removePlayer($player);
            }
            Main::getInstance()->getPlayerBossBar($player)->addPlayer($player);
            
            if($player->isOnline()){
            	LangManager::send("duel-exittedarena", $player, $this->name);
            }
            return true;
        }
        return false;
    }
    
    /**
     * Get the players in the duel
     *
     * @return Player[]
     */
    public function getPlaying() : array{
        return $this->playing;
    }
    
    /**
     * Get the spectators in the duel
     *
     * @return Player[]
     */
    public function getSpectating() : array{
        return $this->spectating;
    }
    
    /**
     * Return if the player is playing a duel.
     *
     * NOTE: This and @see self::isSpectating() return value MUST be strictly checked all time (===)
     * Loosely check will lead to unexpected behavior.
     *
     * @param Player $player
     *
     * @return int|bool The index the player represents in the array, or false if not playing
     */
    public function isPlaying(Player $player){
        foreach($this->playing as $i => $p){
            if($p->getName() === $player->getName()){
                return $i;
            }
        }
        return false;
    }
    
    /**
     * Adds a spectator.
     *
     * @param Player $player
     * @return bool
     */
    public function addSpectator(Player $player){
        if($this->isPlaying($player) === false && $this->isSpectating($player) === false){
            $player->teleport($this->getAreaTopMiddle());
            $this->spectating[] = $player;
            $player->setGamemode(Player::SPECTATOR);
            LangManager::send("duel-spectatingarena", $player, $this->name);
            foreach($this->playing as $p){
            	LangManager::send("duel-spectatingarena-player", $p, $player->getName());
            }
            return true;
        }
        return false;
    }
    
    /**
     * Removes a spectator.
     *
     * @param Player $player
     * @return bool
     */
    public function removeSpectator(Player $player){
        if(($i = $this->isSpectating($player)) !== false){
        	if($player->isOnline()){
        		$player->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());
        	}
            unset($this->spectating[$i]);
            $player->setGamemode(Player::SURVIVAL);
            LangManager::send("duel-notspectatingarena", $player, $this->name);
            foreach($this->playing as $p){
                LangManager::send("duel-notspectatingarena-player", $p, $player->getName());
            }
            return true;
        }
        return false;
    }
    
    /**
     * Return if the player is spectating a duel.
     *
     * NOTE: This and @see self::isPlaying() return value MUST be strictly checked all time (===)
     * Loosely check will lead to unexpected behavior.
     *
     * @param Player $player
     *
     * @return int|bool The index the spectator represents in the array, or false if not spectating
     */
    public function isSpectating(Player $player){
        foreach($this->spectating as $i => $p){
            if($p->getName() === $player->getName()){
                return $i;
            }
        }
        return false;
    }
    
}