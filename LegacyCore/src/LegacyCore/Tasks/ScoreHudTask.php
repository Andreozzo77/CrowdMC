<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\item\Durable;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\FactionMap;
use specter\network\SpecterPlayer;

class ScoreHudTask extends Task{
	/** @var Core */
	private $plugin;
	/** @var array */
	private $packetQueue = [];
	/** @var array string => FactionMap */
	public $fmaps = [];
	
	private static $instance = null;
	/** @var array string => bool Disables the  */
	public static $mainHudOff = [];

	/**
     * ScoreHudTask constructor.
     * @param Core $plugin
     */
    public function __construct(Core $plugin){
        $this->plugin = $plugin;
        self::$instance = $this;
    }
    
    /**
     * @param Player $player
     */
    public function sendCombatScoreboard(Player $player) : void{
    	$plugin = Main::getInstance();
    	$loggerTime = $this->plugin->loggerTime[$player->getName()] ?? 0;
    	if($loggerTime <= 0){
    		unset($this->plugin->loggerTime[$player->getName()]);
    	}
    	$kills = $plugin->getEntry($player, Main::ENTRY_KILLS) ?? 0;
    	$deaths = $plugin->getEntry($player, Main::ENTRY_DEATHS) ?? 0;
    	$kdr = $plugin->getKDR($player->getName());
    	$streak = $plugin->getEntry($player, Main::ENTRY_KILL_STREAK) ?? 0;
    	$exp = $player->getCurrentTotalXp();
    	$item = $this->getHeldItem($player);
    	$enchants = $this->getEquippedEnchants($player);
    	
    	$this->rmScoreboard($player, "objektName");
    	$this->createScoreboard($player, LangManager::translate("core-scoreboard2-title", $player), "objektName");
    	$entries = explode(TextFormat::EOL, LangManager::translate("core-scoreboard2-entries", $player, $loggerTime, $kdr, $kills, $deaths, $streak, $exp, $item, count($enchants)));
    	for($i = 0; $i < count($entries); $i++){
    		$this->setScoreboardEntry($player, $i + 1, $entries[$i], "objektName");
        }
    }

	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
    	$players = $this->plugin->getServer()->getOnlinePlayers();
    	$plugin = Main::getInstance();
		$fp = $plugin->getPlugin("FactionsPro");
		
		foreach($players as $player){
			if($player instanceof SpecterPlayer){
				continue;
			}
			// Remove HUD
			$this->rmScoreboard($player, "objektName");
			
			// Quests
			$enchants = $this->getEquippedEnchants($player);
			$plugin->questManager->getQuest("beast_battler")->progress($player, count($enchants));
			
			if (isset($this->plugin->loggerTime[$player->getName()])){
				$this->sendCombatScoreboard($player); // Combat HUD
				continue;
			}
			
			$scoreboard = $plugin->getSetting($player, Main::SETTING_SCOREBOARD);
			if(isset(self::$mainHudOff[$player->getName()]) || $scoreboard === Main::SETTING_SCOREBOARD_NONE){
				continue; // No HUD
			}
			
			$faction = $fp->getPlayerFaction($player->getName());
			if(empty($faction)){
				$faction = "-";
			}
			
			if($scoreboard === Main::SETTING_SCOREBOARD_FACTION && $faction !== ""){
				if(isset($this->fmaps[$player->getName()])){
					$this->fmaps[$player->getName()]->sendMap(); // Faction HUD
				}
				continue;
			}
			
			$money = number_format((float) $plugin->myMoney($player->getName()) ?? 0);
			$exp = number_format((float) $player->getCurrentTotalXp());
			$kills = number_format($plugin->getEntry($player, Main::ENTRY_KILLS) ?? 0);
			$deaths = number_format($plugin->getEntry($player, Main::ENTRY_DEATHS) ?? 0);
			$kdr = $plugin->getKDR($player->getName()); // Reliable way to get KDR
			$streak = number_format($plugin->getEntry($player, Main::ENTRY_KILL_STREAK) ?? 0);
			$manager = $plugin->permissionManager;
			$rank = $manager->getPlayerGroup($player)->getName();
			$prefix = $manager->getPlayerPrefix($player);
			
			switch($scoreboard){
				case Main::SETTING_SCOREBOARD_OLD:
					$this->createScoreboard($player, LangManager::translate("old-scoreboard-title", $player), "objektName");
                	$entries = explode(TextFormat::EOL, LangManager::translate("old-scoreboard-entries", $player, $money, $exp, $kills, $deaths, $kdr, $streak, $rank, $prefix));
					break;
				case Main::SETTING_SCOREBOARD_REGULAR:
					$tokens = number_format($plugin->getTokens($player));
					$totalTimeOnline = $plugin->getInstance()->getTimeOnline($player->getName(), true);
					$timeOnline = $plugin->getTimeOnline($player->getName(), false);
					$currentTimeOnline = $totalTimeOnline - $timeOnline;
					$currentTimeOnline = $plugin->formatTime($plugin->getTimeEllapsed(time() - $currentTimeOnline), TextFormat::GRAY, TextFormat::GRAY);
					$pg = $plugin->getEntry($player, Main::ENTRY_PRESTIGE) ?? 0;
				    $tag = $manager->getPlayerSuffix($player);
				    if(empty($tag)){
				    	$tag = "-";
				    }
					$time = is_string($timezone = Main::getInstance()->getEntry($player, Main::ENTRY_TIMEZONE)) ? Main::getInstance()->getTimeOnTimezone($timezone) : time();
					$seasonReset = max(0, ceil((strtotime($plugin->getConfig()->get("season-reset")) - $time) / 86400));
			    	$direction = $plugin->getCompassDirection($player->getYaw());
					$item = $this->getHeldItem($player);
                	$this->createScoreboard($player, LangManager::translate("core-scoreboard-title", $player), "objektName");
                	$entries = explode(TextFormat::EOL, LangManager::translate("core-scoreboard-entries", $player, $money, $exp, $kdr, $kills, $deaths, $tokens, $streak, $prefix, $pg, $rank, $tag, $faction, $item, count($enchants), $currentTimeOnline, $player->hasPermission("playervaults.vault.2") ? "✔" : "✗", $seasonReset));
                	break;
				default: //Main::SETTING_SCOREBOARD_NONE
					break 2;
			}
			
			for($i = 0; $i < count($entries); $i++){
                $this->setScoreboardEntry($player, $i + 1, $entries[$i], "objektName");
			}
		}
		
		// Packet Queue
		foreach($this->packetQueue as $player => $packets){
			$p = $this->plugin->getServer()->getPlayerExact($player);
			if($p === null || !$p->isOnline()){
				unset($this->packetQueue[$player]);
				continue;
			}
			$pk = new BatchPacket();
			$pk->setCompressionLevel(7);
			foreach($packets as $packet){
				$pk->addPacket($packet);
			}
			$p->sendDataPacket($pk);
		}
	}
	
	/*
	 * API
	 */
	
	/**
	 * @return self
	 */
	public static function getInstance() : self{
		return self::$instance;
	}
	
	/**
	 * Returns a clean name of the item held to fit in scoreboard.
	 *
	 * @param Player $player
	 * @returh string
	 */
	public function getHeldItem(Player $player) : string{
		$i = $player->getInventory()->getItemInHand();
		$name = $i->getName() !== "Air" ? explode(TextFormat::EOL, TextFormat::clean($i->getName()))[0] : "NaN";
		$name = preg_replace("/[^a-zA-Z0-9\s]/", "", $name);
		
		$item = TextFormat::colorize("&r&e" . $i->getId() . ":" . ($i instanceof Durable ? 0 : $i->getDamage()) . " &7" . $name);
		preg_match_all("/§/", $item, $matches);
		$len = count($matches[0]) * 3;
		return substr($item, 0, $tot = 20 + $len);
	}
	
	/**
	 * Returns a list with all the enchants woren by player
	 * @param Player $player
	 * @return int[]
	 */
	public function getEquippedEnchants(Player $player) : array{
		$enchants = [];
		foreach($player->getArmorInventory()->getContents() as $armorPiece){
			foreach($armorPiece->getEnchantments() as $enchant){
				if(!in_array($enchant->getType()->getId(), $enchants)){
					$enchants[] = $enchant->getType()->getId();
				}
			}
		}
		$i = $player->getInventory()->getItemInHand();
		foreach($i->getEnchantments() as $enchant){
			if(!in_array($enchant->getType()->getId(), $enchants)){
				$enchants[] = $enchant->getType()->getId();
			}
		}
		return $enchants;
	}
	
	/**
	 * @param Player $player
	 * @param int $score
	 * @param string $msg
	 * @param string $objName
     */
	public function setScoreboardEntry(Player $player, int $score, string $msg, string $objName) {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $objName;
        $entry->type = 3;
        $entry->customName = TextFormat::colorize($msg) . " ";
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $packet = new SetScorePacket();
        $packet->type = 0;
        $packet->entries[$score] = $entry;
        $this->packetQueue[$player->getName()][] = $packet;
    }

	/**
	 * @param Player $player
	 * @param int $score
     */
    public function rmScoreboardEntry(Player $player, int $score) {
        $packet = new SetScorePacket();
        if (isset($packet->entries[$score])) {
            unset($packet->entries[$score]);
            $this->packetQueue[$player->getName()][] = $packet;
        }
    }

	/**
	 * @param Player $player
	 * @param string $title
	 * @param string $objName
	 * @param string $slot
	 * @param int $order
     */
    public function createScoreboard(Player $player, string $title, string $objName, string $slot = "sidebar", $order = 0) {
        $packet = new SetDisplayObjectivePacket();
        $packet->displaySlot = $slot;
        $packet->objectiveName = $objName;
        $packet->displayName = TextFormat::colorize($title);
        $packet->criteriaName = "dummy";
        $packet->sortOrder = $order;
        $this->packetQueue[$player->getName()][] = $packet;
    }

	/**
	 * @param Player $player
	 * @param string $objName
     */
    public function rmScoreboard(Player $player, string $objName) {
        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = $objName;
        $this->packetQueue[$player->getName()][] = $packet;
    }
	}