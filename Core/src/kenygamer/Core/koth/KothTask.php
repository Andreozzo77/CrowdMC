<?php

declare(strict_types=1);

namespace kenygamer\Core\koth;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use pocketmine\math\AxisAlignedBB;
use pocketmine\item\Item;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use xenialdan\BossBar\BossBar;
use LegacyCore\Tasks\ScoreHudTask;

class KothTask extends Task{
	public const GAME_STATUS_INACTIVE = 0;
	public const GAME_STATUS_COUNTDOWN = 1;
	public const GAME_STATUS_RUNNING = 2;
	
	/** @var int */
	private $status = self::GAME_STATUS_INACTIVE;
	/** @var Level|null */
	private $level = null;
	/** @var int */
	private $gameTick = 0;
	/** @var array string => int */
	private $respawnTime = [];
	/** @var array string => int */
	private $playerSpawns = [];
	/** @var array */
	private $captureProgress, $lastProgressMeasurement = [];
	/** @var BossBar|null */
	private $bossbar = null;
	/** @var bool */
	private $isEnabled = false;
	/** @var Player[] */
	private $players = [];
	/** @var int */
	private $gameDuration = 20 * 60 * 10;
	/** @var int Must not be greater to number of spawn points */
	private $playersRequired = 16;
	/** @var int */
	private $rivalFactionsRequired = 4;
	/** @var Location[] Max players determined by this */
	private $spawnPoints = [];
	/** @var AxisAlignedBB */
	private $hillPoint = null;
	/** @var AxisAlignedBB */
	private $arena = null;
	/** @var string */
	private $world = "koth";
	/** @var self|null */
	private static $instance = null;
	
	private const KING_TAG = TextFormat::YELLOW . "[King]";
	private const CAPPER_TAG = TextFormat::GREEN . "[Capper]";
	
	public function __construct(){
		$plugin = Main::getInstance();
		$this->level = $plugin->getServer()->getLevelByName($this->world);
		if(!($this->level instanceof Level)){
			if($plugin->getServer()->isLevelGenerated($this->world)){
				if(trim($this->world) !== "" && $plugin->getServer()->loadLevel($this->world)){
					$this->level = $plugin->getServer()->getLevelByName($this->world);
				}else{
					$plugin->getLogger()->error("[Koth] Cannot load world " . $this->world);
					return;
				}
			}else{
				$plugin->getLogger()->error("[Koth] World " . $this->world . " does not exist");
				return;
			}
		}
		self::$instance = $this;
		$this->spawnPoints = [
		    new Location(315, 193, 301, 270, 0, $this->level),
		    new Location(294, 196, 301, 270, 0, $this->level),
		    new Location(280, 194, 310, 270, 0, $this->level),
		    new Location(296, 198, 341, 236, 0, $this->level),
		    new Location(331, 195, 354, 125, 0, $this->level),
		    new Location(330, 197, 275, 0, 0, $this->level),
		    new Location(288, 194, 360, 230, 0, $this->level),
		    new Location(312, 193, 364, 182, 0, $this->level),
		    new Location(317, 192, 336, 208, 0, $this->level),
		    new Location(315, 197, 282, 312, 0, $this->level),
		    new Location(359, 216, 311, 90, 0, $this->level),
		    new Location(353, 214, 284, 48, 0, $this->level),
		    new Location(359, 223, 297, 100, 0, $this->level),
		    new Location(347, 218, 329, 126, 0, $this->level),
		    new Location(340, 218, 334, 132, 0, $this->level),
		    new Location(337, 203, 321, 135, 0, $this->level)
		];
		    
		        
		$this->hillPoint = new AxisAlignedBB(338, 198, 306, 344, 206, 312);
		$this->arena = new AxisAlignedBB(270, 188, 266, 372, 256, 368);
	}
	
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	/**
	 * Add a player to the Koth game.
	 *
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function addPlayer(Player $player) : bool{
		if(!in_array($player->getName(), $this->players)){
			$takenSpawns = array_values($this->playerSpawns);
			if(count($takenSpawns) === $this->spawnPoints){
				return false;
			}
			$spawnPoint = 0;
			while(in_array($spawnPoint, $takenSpawns)){
				$spawnPoint = array_rand($this->spawnPoints);
			}
			$this->playerSpawns[$player->getName()] = $spawnPoint;
			
			$this->respawn($player);
			$this->players[] = $player;
			$this->broadcastInGame("koth-join", $player->getName());
			$player->setGamemode(Player::SURVIVAL);
			$player->setFlying(false);
			$player->setAllowFlight(false);
			$player->setHealth($player->getMaxHealth());
			$player->setFood($player->getMaxFood());
			$player->setImmobile(true);
			$player->removeAllEffects();
			return true;
		}
		return false;
	}
	
	/**
	 * Removes a player from the Koth game.
	 *
	 * @param Player $player
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function removePlayer(Player $player, string $reason = "") : bool{
		if($this->isPlaying($player)){
			if($this->bossbar instanceof BossBar){
				$this->bossbar->removePlayer($player);
			}
			Main::getInstance()->getPlayerBossBar($player)->addPlayer($player);
			unset(ScoreHudTask::$mainHudOff[$player->getName()]);
			unset($this->captureProgress[$player->getName()]);
			$this->broadcastInGame(trim($reason) !== "" ? "koth-leave-reason" : "koth-leave", $player->getName(), $reason);
			unset($this->players[array_search($player->getName(), array_map(function(Player $player) : string{
				return $player->getName();
			}, $this->players))]);
			unset($this->playerSpawns[$player->getName()]);
			$player->setHealth($player->getMaxHealth());
			$player->setFood($player->getMaxFood());
			$player->teleport($player->getServer()->getDefaultLevel()->getSafeSpawn());
			$player->setImmobile(false);
			return true;
		}
		return false;
	}
	
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		
		foreach($this->respawnTime as $player => $sec){
			if(--$this->respawnTime[$player] <= 0){
				unset($this->respawnTime[$player]);
			}
		}
		$check = $this->checkPlayers();
		switch($this->status){
			case self::GAME_STATUS_INACTIVE:
				if($check[0] > 0){
					$this->gameTick = 0;
					$this->status = self::GAME_STATUS_COUNTDOWN;
				}else{
					$this->gameTick = -20;
					if(abs($this->gameTick) % (20 * 60) === 0){
						LangManager::broadcast("koth-broadcast");
					}
					foreach($this->players as $player){
						switch($check[0]){
							case 0:
							    $player->sendPopup("\n\n\n" . LangManager::translate("koth-queue-popup", $player, $check[1][0], $check[1][1]));
							    break;
							case -1:
							    $player->sendPopup("\n\n\n" . LangManager::translate("koth-queue-popup2", $player, $check[1][0], $check[1][1]));
							    break;
						}
					}
				}
				break;
			case self::GAME_STATUS_COUNTDOWN:
			    if($check[0] < 1){
			    	$this->status = self::GAME_STATUS_INACTIVE;
			    	break;
			    }
				if($this->gameTick < (20 * 60)){
					$toSec = $this->gameTick / 20;
					$sec = [
					    60, 30, 15, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1 
					];
					if(in_array($left = 60 - $toSec, $sec)){
						foreach($this->players as $player){
							$player->addTitle(LangManager::translate("koth", $player), LangManager::translate("koth-starting", $player, $left), 9, 9, 9);
						}
					}
					foreach($this->players as $player){
						$player->sendPopup("\n\n\n" . LangManager::translate("koth-countdown-popup", $player, count($this->players), count($this->spawnPoints)));
					}
					$this->gameTick += 20;
				}else{
					$this->gameTick = 0;
					$this->status = self::GAME_STATUS_RUNNING;
					
					$this->bossbar = new BossBar();
					$fp = $plugin->getPlugin("FactionsPro");
					foreach($this->players as $player){
						if($fp->isInFaction($player->getName())){
							$fac = $fp->getPlayerFaction($player->getName());
							KothListener::$leaderboard[$fac] = [0, 0];
						}
							
						Main::getInstance()->getPlayerBossBar($player)->removePlayer($player);
						$this->bossbar->addPlayer($player);
						$player->setImmobile(false);
						ScoreHudTask::$mainHudOff[$player->getName()] = true;
						$player->addTitle(LangManager::translate("koth", $player), LangManager::translate("koth-start", $player));
					}
				}
				break;
			case self::GAME_STATUS_RUNNING:
			    $startTime = time() - ($this->gameTick / 20);
			    $endTime = $startTime + ($this->gameDuration / 20);
			    $secLeft = $endTime - $startTime;
				$this->bossbar->setTitle(TextFormat::colorize(LangManager::translate("koth") . " &ftime: " . $plugin->formatTime($plugin->getTimeLeft($endTime), TextFormat::WHITE, TextFormat::WHITE)));
				$this->bossbar->setPercentage(time() / $endTime);
				
				if($check[0] < 1 || $endTime <= time()){
			    	LangManager::broadcast("koth-nowinner");
			    	$this->endGame();
			    	break;
			    }
				
				$progress = [];
				foreach($this->players as $player){
					if(!$this->inBB($player, $this->arena)){
						$this->respawn($player);
					}
					if(!isset($this->captureProgress[$player->getName()])){
						$this->captureProgress[$player->getName()] = 0;
					}
					if($this->inBB($player, $this->hillPoint)){
						$this->captureProgress[$player->getName()] += $this->hasTag($player, self::KING_TAG) ? 2 : 1;
						if($this->captureProgress[$player->getName()] > 100){
							
							LangManager::broadcast("koth-winner", $player->getName());
							$lootbag = ItemUtils::get(Item::NETHER_STAR, "&l" . LangManager::translate("koth") . " &bLootbag", [
							    "&eTap anywhere in the ground to open.",
							    "&7When opened you will receive:",
							    "&7Winner: &e" . $player->getName()
							]);
							$nbt = $lootbag->getNamedTag();
							$nbt->setInt("KothLootbag", 1);
							$lootbag->setNamedTag($nbt);
							$player->getInventory()->addItem($lootbag);
							
							$leaderboard = KothListener::$leaderboard;
							uasort($leaderboard, function(array $a, array $b){
								return $b[0] - $b[1] < $a[0] - $a[1] ? -1 : 1;
							});
							
							//Money rewards - Part I.
							$factions = [];
							$fp = $plugin->getPlugin("FactionsPro");
							foreach($this->players as $player){
								if($fp->isInFaction($player->getName())){
									$faction = $fp->getPlayerFaction($player->getName());
									if(!in_array($faction, $factions)){
										$factions[] = $faction;
									}
								}
							}
							$moneyRewards = [];
							$baseMoney = 1000000000;
							$moneyRewards[] = $baseMoney;
							$money = $baseMoney / 4;
							for($i = 0; $i < count($factions) - 1; $i++){
								/*while($baseMoney - $money <= 0){
									$money /= 4;
								}*/
								$baseMoney -= $money;
								$moneyRewards[] = $baseMoney;
							}
							foreach($this->players as $player){
								$msg = "";
								if(!empty($leaderboard)){
									$msg .= "\n";
								}
								$i = 1;
								foreach($leaderboard as $fac => $score){
									$msg .= LangManager::translate("koth-results-2", $player, $fac, $score[0], $score[1], $moneyRewards[$i - 1]) . ($i !== count($leaderboard) ? "\n" : "");
									$i++;
								}
								LangManager::send("koth-results", $player, $msg);
							}
							//Money rewards - Part II.
							$result = $fp->db->query("SELECT * from master");
							foreach($factions as $i => $faction){
								$numPlayers = $fp->getNumberOfPlayers($faction);
								$money = $moneyRewards[$i] / $numPlayers;
								while($resultArr = $result->fetchArray(SQLITE3_ASSOC)){
									if($resultArr["faction"] === $faction){
										Main::getInstance()->addMoney($resultArr["player"], $money);
									}
								}
							}
							
							$this->isEnabled = false;
							$this->endGame();
							break 2;
							
						}else{
							$times = (int) floor($this->captureProgress[$player->getName()] / 5);
							$player->sendPopup("\n\n\n" . TextFormat::colorize(preg_replace("/&f█/", "&a█", str_repeat("&f█", 20), $times)));
						}
					}else{
						if(($pProgress = $this->captureProgress[$player->getName()]) > 0){
							if(($newProgress = $pProgress - 3) < 0){
								$newProgress = 0;
							}
							$this->captureProgress[$player->getName()] = $newProgress;
						}
					}
					$progress[$player->getName()] = $this->captureProgress[$player->getName()];
				}
				uasort($progress, function(int $a, int $b) : int{
					return $b < $a ? -1 : 1;
				});
				if($this->gameTick % (20 * 60) === 0){
					$this->lastProgressMeasurement = $progress;
				}
				$topProgress = array_slice($progress, 0, 5, true);
				foreach($this->players as $player){
					ScoreHudTask::getInstance()->rmScoreboard($player, "objektName");
					ScoreHudTask::getInstance()->createScoreboard($player, LangManager::translate("koth-scoreboard-title", $player), "objektName");
					$data = [];
					foreach($topProgress as $pplayer => $percent){
						//Assuming the arrays are sorted prior to
						$increase = array_search($pplayer, array_keys($this->lastProgressMeasurement)) - array_search($pplayer, array_keys($progress));
						if($increase >= 0){
							$data[] = "&a△" . $increase; //Rise
						}else{
							$data[] = "&c▽" . $increase; //Fall
						}
						$data[] = $pplayer;
						$data[] = $percent;
					}
					$entries = explode("\n", LangManager::translate("koth-scoreboard-entries", ...$data));
					for($i = 0; $i < count($topProgress); $i++){
						ScoreHudTask::getInstance()->setScoreboardEntry($player, $i + 1, $entries[$i], "objektName");
					}
				}
				reset($progress);
				$king = key($progress);
				//$progress = cappers
				/** @var Player $kingPlayer */
				$kingPlayer = $plugin->getServer()->getPlayerExact($king);
				
				if($progress[$king] > 10){
					unset($progress[$king]);
					if(!$this->hasTag($kingPlayer, self::KING_TAG)){
						$kingPlayer->setNameTag(self::KING_TAG . str_replace(self::CAPPER_TAG, "", $kingPlayer->getNameTag()));
						$this->broadcastInGame("koth-claim", $kingPlayer->getName());
					}
					foreach($progress as $capper => $percent){
						/** @var Player $capperPlayer */
						$capperPlayer = $plugin->getServer()->getPlayerExact($capper);
						if($this->hasTag($capperPlayer, self::KING_TAG)){
							$capperPlayer->setNameTag(self::CAPPER_TAG . str_replace(self::KING_TAG, "", $capperPlayer->getNameTag()));
						}
					}
				}else{
					if($this->hasTag($kingPlayer, self::KING_TAG)){
						$kingPlayer->setNameTag(self::CAPPER_TAG . str_replace(self::KING_TAG, "", $kingPlayer->getNameTag()));
					}
				}
				
				$this->gameTick += 20;
				break;
		}
	}
	
	public function checkPlayers() : array{
		$fp = Main::getInstance()->getPlugin("FactionsPro");
		if(count($this->players) < $this->playersRequired){
			return [0, [count($this->players), $this->playersRequired]];
		}
		$requiredRivals = $this->rivalFactionsRequired;
		$factions = [];
		foreach($this->players as $player){
			if($fp->isInFaction($player->getName())){
				$faction = $fp->getPlayerFaction($player->getName());
				if(!in_array($faction, $factions)){
					$factions[] = $faction;
				}
			}
		}
		foreach($factions as $fac){
			foreach($factions as $ffac){
				if($fac !== $ffac && !$fp->areAllies($fac, $ffac)){
					$requiredRivals -= 1;
				}
			}
		}
		if($requiredRivals > 0){
			return [-1, [$this->rivalFactionsRequired - $requiredRivals, $this->rivalFactionsRequired]];
		}
		return [1, [-1, -1]];
	}
	
	public function getPlayers() : array{
		return $this->players;
	}
	
	public function hasRespawned(Player $player) : bool{
		return !isset($this->respawnTime[$player->getName()]);
	}
	public function respawn(Player $player) : bool{
		if(isset($this->playerSpawns[$player->getName()])){
			$player->teleport($this->spawnPoints[$this->playerSpawns[$player->getName()]]);
			if($this->hasRespawned($player)){
				$this->respawnTime[$player->getName()] = 3;
			}
			return true;
		}
		return false;
	}
	
	public function isPlaying(Player $player) : bool{
		return in_array($player->getName(), array_map(function(Player $player) : string{
			return $player->getName();
		}, $this->players));
	}
	
	public function getStatus() : int{
		return $this->status;
	}
	
	/**
	 * @param bool $value
	 */
	public function setEnabled(bool $value) : void{
		$this->isEnabled = $value;
	}
	
	/**
	 * @return bool Whether this arena can receive new players or not
	 */
	public function isEnabled() : bool{
		return $this->isEnabled;
	}
	
	private function hasTag(Player $player, string $tag) : bool{
		return strpos($player->getNameTag(), $tag) !== false;
	}
	
	private function endGame() : void{
		foreach($this->players as $player){
			$player->addTitle(LangManager::translate("koth", $player), LangManager::translate("koth-end", $player));
			$this->removePlayer($player, "game ended");
		}
		KothListener::$leaderboard = [];
		$this->status = self::GAME_STATUS_INACTIVE;
		$this->bossbar = null;
	}
	
	public function broadcastInGame(string $key, ...$params) : void{
		foreach($this->players as $player){
			$player->sendMessage(LangManager::translate($key, ...$params));
		}
	}
	
	private function inBB(Player $player, AxisAlignedBB $bb) : bool{
		return $bb->isVectorInside($player->asVector3()) && $this->level !== null && $player->level !== null && $this->level->getFolderName() === $player->level->getFolderName();
	}
	
}