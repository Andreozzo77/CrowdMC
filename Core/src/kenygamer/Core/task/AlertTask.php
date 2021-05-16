<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;

use pocketmine\utils\Config;  
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\RelayThread;
use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\util\ItemUtils;
use LegacyCore\Core;
use LegacyCore\Tasks\RestartTask;

class AlertTask extends Task{
	/** @var int */
	private $subtitleIndex = 0;
	/** @var int */
	private $index = 0;
	/** @var string[] */
	private $lastTitle = [];
	/** @var string */
	private $tip = "";
	
	private static $MAIN_TIP_COLOR = TextFormat::WHITE;
	private static $FULL_TIP = " TPS: -- (--%) | Ping: ---ms | --/--";
	
	private static $COMPASS_LENGTH = 76;
	private static $COMPASS_ROSE = ["|NW|", "|N|", "|NE|", "|E|", "|SE|", "|S|", "|SW|", "|W|"];
	private static $COMPASS_ROSE_COLORS = [
		TextFormat::LIGHT_PURPLE => "|NW|",
		TextFormat::RED => "|N|",
		TextFormat::GOLD => "|NE|",
		TextFormat::YELLOW => "|E|",
		TextFormat::GREEN => "|SE|",
		TextFormat::WHITE => "|S|",
		TextFormat::DARK_AQUA => "|SW|",
		TextFormat::AQUA => "|W|"
	];
	private static $COMPASS_FILL = "|";
	private static $COMPASS_ROSE_STR = "";
	
	public function __construct(){
		$spacing = str_repeat(self::$COMPASS_FILL, intval(round(self::$COMPASS_LENGTH / 2)));
		self::$COMPASS_ROSE_STR = "";
		foreach(self::$COMPASS_ROSE as $direction){
    		self::$COMPASS_ROSE_STR .= $direction . $spacing;
		}
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$tick = $currentTick - 1;
		$plugin = Main::getInstance();
		
		$len = strlen(self::$FULL_TIP);
		if($this->index > $len){
			$this->index = 0;
		}
		$tip_ = "";
		if($this->index > 0){
			$tip_ = substr(self::$FULL_TIP, -$this->index);
		}
        $tip_ .= substr(self::$FULL_TIP, 0, $len - $this->index);
		if(($currentTick - 1) % 3 === 0){
        	$this->index++;
		}
		
		$players = $plugin->getServer()->getOnlinePlayers();
		
		$online = count($players);
		$max = $plugin->getServer()->getMaxPlayers();
		$tps = (string) ceil($plugin->getServer()->getTicksPerSecondAverage());
		$load = (string) $plugin->getServerLoadAverage();
		
		if($tps < 16){ //15 ... 0
			$tpsColor = TextFormat::GREEN;
		}elseif($tps < 18){ //17-16
			$tpsColor = TextFormat::AQUA;
		}else{ //20-19-18
			$tpsColor = TextFormat::GREEN;
		}
		
		//Chat messages
		if($tick % 5 === 0){
			$cfg = new Config($plugin->getServer()->getDataPath() . "relay_queue.js", Config::JSON);
			$all = [];
			foreach($cfg->getAll() as $needle){
				list($format, $msg, $recipients, $type) = $needle;
				if($type === RelayThread::RELAY_THREAD_OUT){
					/*There will always be only 1 recipient in a one-dimensional array.
					The message will be always empty since the format has already been processed and localized if applicable.*/
					
					if(count($recipients) === 0){ //Incoming messages
						$recipients = array_map(function(Player $player) : string{
							return $player->getName();
						}, $players);
						$recipients[] = "CONSOLE";
						
						
						$recipients = array_combine($recipients, array_fill(0, count($recipients), LangManager::LANG_DEFAULT));
					}
					foreach($recipients as $recipient => $lang){
						if($recipient === "CONSOLE"){
							$plugin->getServer()->getLogger()->info($format);
						}else{
							foreach($players as $player){
								if($player->getName() === $recipient){
									$player->sendMessage($format);
								}
							}
						}
					}
					
				}else{
					$all[] = $needle;
				}
			}
			$cfg->setAll($all);
			$cfg->save();
		}
		
		foreach($players as $player){
			//Popups
			if($tick % 7 === 0){
				if(isset(MiscListener2::$items[$player->getName()])){
		    		$items = MiscListener2::$items[$player->getName()];
					if(!empty($items)){
		        		$arr = array_shift($items);
		        		list($item, $inventory, $usage) = $arr;
		        		//Stack up
		        		$count = $item->getCount();
		        		foreach($items as $data){
		        			list($item2) = $data;
		        			if($item2->equals($item)){
		        				$count += $item2->getCount();
		        				foreach($items as $i => $data_){
		        					list($item_) = $data_;
		        					if(spl_object_hash($item_) === spl_object_hash($item2)){
		        						unset($items[$i]);
		        						break;
		        					}
								}
							}
		        		}
		        		switch($inventory){
		        			case 0:
		        				$key = "normalinv-additem";
		        				break;
			        		case 1:
			        		   $key = "backupinv-additem";
			        		   break;
			        		default:
			        		   $key = "normalinv-additem";
			        	}
			            $player->sendPopup(LangManager::translate($key, $player, ItemUtils::getDescription($item->setCount($count)), round($usage * 100)));
			        }
			        MiscListener2::$items[$player->getName()] = $items;
				}
			}

			//HUD
			if($tick % 7 === 0){
				$ping = (string) $player->getPing();
				if($ping >= 170){
					$pingColor = TextFormat::YELLOW;
				}elseif($ping >= 60){
					$pingColor = TextFormat::AQUA;
				}else{
					$pingColor = TextFormat::GREEN;
				}
				
				$tip = $tip_;
				$tip = str_replace("--/--", $online . "/" . $max, $tip);
				$tip = str_replace("---ms", $pingColor . $ping . "ms" . self::$MAIN_TIP_COLOR, $tip);
				$tip = str_replace("--%)", $tpsColor . $load . "%%)" . self::$MAIN_TIP_COLOR, $tip);
			    $tip = str_replace("S: --", "S: " . $tpsColor . $tps, $tip);
				$tip = str_replace("-", "", $tip);
				$tip = TextFormat::RESET . self::$MAIN_TIP_COLOR . TextFormat::colorize($tip);
				$tip = str_pad($tip, strlen(self::$FULL_TIP), " ", STR_PAD_RIGHT);
				if($plugin->getSetting($player, Main::SETTING_HUD)){
					if(Core::$snapshot === ""){
						$player->sendTip(strval($player->getFloorX()) . ", " . strval($player->getFloorY()) . ", " . strval($player->getFloorZ()) . ", " . strval($player->getYaw()));
					}else{
						$player->sendTip($tip);
					}
				}
			}
			
			//Time
			if($tick % 20 === 0){
				if(!$plugin->getSetting($player, Main::SETTING_TIME)){
					continue;
				}
				$padding = str_repeat(TextFormat::EOL, $plugin->getSetting($player, Main::SETTING_COMPASS) ? 15 : 16);
				$player->sendPopup(LangManager::translate("time-format", $plugin->timeArray["day"], $plugin->timeArray["hour"], $plugin->timeArray["minute"], $plugin->timeArray["meridiem"] . $padding));
			}
			
			//Compass
			if($tick % 10 === 0){
				if(!$plugin->getSetting($player, Main::SETTING_COMPASS)){
					continue;
				} 
				$factor = strlen(self::$COMPASS_ROSE_STR) / 360;
				$degrees = $plugin->clampDegrees($player->getYaw());
				$substr = intval(round($factor * $degrees)) - self::$COMPASS_LENGTH;
				$title = substr(self::$COMPASS_ROSE_STR, $substr, self::$COMPASS_LENGTH);
				$compass = self::$COMPASS_ROSE_STR;
				if($substr < 0){
	    			$substr = strlen(self::$COMPASS_ROSE_STR) - abs($substr);
	    			foreach(self::$COMPASS_ROSE as $direction){
	        			$compass .= $direction . str_repeat(self::$COMPASS_FILL, intval(round(self::$COMPASS_LENGTH / 2)));
					}
				}
				$title = substr($compass, $substr, self::$COMPASS_LENGTH);
				$title = TextFormat::GRAY . $title;
				foreach(self::$COMPASS_ROSE_COLORS as $color => $direction){
					$direction = str_replace("|", "", $direction);
					$title = str_replace(self::$COMPASS_FILL . $direction . self::$COMPASS_FILL, TextFormat::DARK_GRAY . self::$COMPASS_FILL . TextFormat::BOLD . $color . $direction . TextFormat::RESET . TextFormat::DARK_GRAY . self::$COMPASS_FILL . TextFormat::GRAY, $title);
				}
				if(!(isset($this->lastTitle[$player->getName()]) && $this->lastTitle[$player->getName()] === $title)){
					$bar = $plugin->getPlayerBossBar($player);
					$bar->setTitle($title);
				}
				$this->lastTitle[$player->getName()] = $title;
			}
			//Compass
			if($tick % 20 === 0){
				if(!$plugin->getSetting($player, Main::SETTING_COMPASS)){
					continue;
				} 
				$bar = $plugin->getPlayerBossBar($player);
				$subtitles = explode("@", LangManager::translate("compass-bar-subtitles"));
				$bar->setSubtitle(LangManager::patternize($subtitles[$this->subtitleIndex], LangManager::PATTERN_CHRISTMAS));
				$bar->setPercentage((1 / count($subtitles) * ($this->subtitleIndex + 1)));
				if(++$this->subtitleIndex >= count($subtitles)){
					$this->subtitleIndex = 0;
				}
			}
        }
	}
	
}