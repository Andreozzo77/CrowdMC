<?php

namespace FactionsPro;

/*
 * 
 * v1.3.0 To Do List
 * [X] Separate into Command, Listener, and Main files
 * [X] Implement commands (plot claim, plot del)
 * [X] Get plots to work
 * [X] Add plot to config
 * [X] Add faction description /f desc <faction>
 * [X] Only leaders can edit motd, only members can check
 * [X] More beautiful looking (and working) config
 * 
 * 
 */

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\block\Snow;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\Item;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class FactionMain extends PluginBase implements Listener {
	private const STR_MONEY_EQUIV = 1000000;
	
    public $db;
    public $prefs;
    public $war_req = [];
    public $wars = [];
    public $war_players = [];
    public $antispam;
    public $purechat;
    public $factionChatActive = [];
    public $allyChatActive = [];
    public $factionBank;
    public $factionSpawners;

    public function onEnable() {

        @mkdir($this->getDataFolder());

        $this->factionBank = new Config($this->getDataFolder() . "faction_bank.js", Config::JSON);
        $this->factionSpawners = new Config($this->getDataFolder() . "faction_spawners.js", Config::JSON);
        
        if (!file_exists($this->getDataFolder() . "BannedNames.txt")) {
            $file = fopen($this->getDataFolder() . "BannedNames.txt", "w");
            $txt = "Admin:admin:Staff:staff:Owner:owner:Builder:builder:Op:OP:op";
            fwrite($file, $txt);
        }


        $this->getServer()->getPluginManager()->registerEvents(new FactionListener($this), $this);

        $this->antispam = $this->getServer()->getPluginManager()->getPlugin("AntiSpamPro");
        if ($this->antispam) {
            $this->getLogger()->info("AntiSpamPro Integration Enabled");
        }
        $this->purechat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
        if ($this->purechat) {
            $this->getLogger()->info("PureChat Integration Enabled");
        }

        $this->fCommand = new FactionCommands($this);

        $this->prefs = new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array(
            "MaxFactionNameLength" => 15,
            "MaxPlayersPerFaction" => 30,
            "OnlyLeadersAndOfficersCanInvite" => true,
            "OfficersCanClaim" => false,
            "PlotSize" => 25,
            "PlayersNeededInFactionToClaimAPlot" => 5,
            "PowerNeededToClaimAPlot" => 1000,
            "PowerNeededToSetOrUpdateAHome" => 250,
            "PowerGainedPerPlayerInFaction" => 50,
            "PowerGainedPerKillingAnEnemy" => 10,
            "PowerGainedPerAlly" => 100,
            "AllyLimitPerFaction" => 5,
            "TheDefaultPowerEveryFactionStartsWith" => 0,
            "EnableOverClaim" => true,
            "ClaimWorlds" => [],
            "AllowChat" => true,
            "AllowFactionPvp" => false,
            "AllowAlliedPvp" => false
        ));
        $this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliance (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, requestedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motdrcv (player TEXT PRIMARY KEY, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motd (faction TEXT PRIMARY KEY, message TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots(faction TEXT PRIMARY KEY, x1 INT, z1 INT, x2 INT, z2 INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT, world TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS strength(faction TEXT PRIMARY KEY, power INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS allies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS enemies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliescountlimit(faction TEXT PRIMARY KEY, count INT);");
    }
	
    public function topFactionsReward() : array{
    	$list = [];
		
    	$rewards = [
    	    5000000000, 4000000000, 3000000000, 2000000000, 1000000000
    	];
		
    	$factions = $this->getTopFactions(count($money));
    	$result = $this->db->query("SELECT * from master");
    	foreach($factions as $i => $f){
    		$faction = $f[0];
    		$money = $rewards[$i] / $this->getNumberOfPlayers($faction);
    		while($resultArray = $result->fetchArray(SQLITE3_ASSOC)){
    			if($resultArray["faction"] === $faction){
    				Main::getInstance()->addMoney($resultArray["player"], $money);
    				$list[] = $resultArray["player"];
    			}
    		}
    	}
    	return $list;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) :bool {
        return $this->fCommand->onCommand($sender, $command, $label, $args);
    }

    public function setEnemies($faction1, $faction2) {
        $stmt = $this->db->prepare("INSERT INTO enemies (faction1, faction2) VALUES (:faction1, :faction2);");
        $stmt->bindValue(":faction1", $faction1);
        $stmt->bindValue(":faction2", $faction2);
        $stmt->execute();
    }

    public function areEnemies($faction1, $faction2) {
        $result = $this->db->query("SELECT ID FROM enemies WHERE faction1 = '$faction1' AND faction2 = '$faction2';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        if (empty($resultArr) == false) {
            return true;
        }
    }

    public function isInFaction($player) {
        $result = $this->db->query("SELECT player FROM master WHERE player='$player';");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }

    public function getFaction($player) {
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["faction"];
    }

    public function setFactionPower($faction, $power) {
        if ($power < 0) {
            $power = 0;
        }
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":power", $power);
        $stmt->execute();
    }

    public function setAllies($faction1, $faction2) {
        $stmt = $this->db->prepare("INSERT INTO allies (faction1, faction2) VALUES (:faction1, :faction2);");
        $stmt->bindValue(":faction1", $faction1);
        $stmt->bindValue(":faction2", $faction2);
        $stmt->execute();
    }

    public function areAllies($faction1, $faction2) {
        $result = $this->db->query("SELECT ID FROM allies WHERE faction1 = '$faction1' AND faction2 = '$faction2';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        if (empty($resultArr) == false) {
            return true;
        }
    }

    public function updateAllies($faction) {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO alliescountlimit(faction, count) VALUES (:faction, :count);");
        $stmt->bindValue(":faction", $faction);
        $result = $this->db->query("SELECT ID FROM allies WHERE faction1='$faction';");
        $i = 0;
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $i = $i + 1;
        }
        $stmt->bindValue(":count", (int) $i);
        $stmt->execute();
    }

    public function getAlliesCount($faction) {

        $result = $this->db->query("SELECT count FROM alliescountlimit WHERE faction = '$faction';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        return (int) $resultArr["count"];
    }

    public function getAlliesLimit() {
        return (int) $this->prefs->get("AllyLimitPerFaction");
    }

    public function deleteAllies($faction1, $faction2) {
        $stmt = $this->db->prepare("DELETE FROM allies WHERE faction1 = '$faction1' AND faction2 = '$faction2';");
        $stmt->execute();
    }

    /**
     * Returns the power of the faction
     *
     * @param string			$faction
     * @param bool				$all
     */
    public function getFactionPower($faction, bool $all = true){
        $result = $this->db->query("SELECT power FROM strength WHERE faction = '$faction';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        
        $power = (int) $resultArr["power"];
        
        if(!$all){
        	return $power;
        }
        foreach($this->factionSpawners->get($faction, []) as $loc => $str){
        	$power += $str;
        }
        return $power + ($this->factionBank->get($faction, 0) / self::STR_MONEY_EQUIV);
    }
    
    /**
     * Add perma STR. This is NOT for dynamic STR such as the one provided 
     * by faction spawners or faction bank.
     *
     * @param string			$faction
     * @param int				$power
     */
    public function addFactionPower($faction, $power) {
        if ($this->getFactionPower($faction) + $power < 0) {
            $power = $this->getFactionPower($faction);
        }
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":power", $this->getFactionPower($faction, false) + $power);
        $stmt->execute();
    }
    
    /**
     * Safely transfer any amount of power from one faction to another.
     *
     * @param string $sourceFaction
     * @param string $targetFactionn
     * @param int $power
     *
     * @return int Power transferred
     */
    public function transferPower($sourceFaction, $targetFaction, $power) : int{
    	$power1 = $this->getFactionPower($sourceFaction);
    	$power2 = $this->getFactionPower($targetFaction);
    	if($power1 - $power < 0){
    		$power = $power1;
    	}
    	$this->subtractFactionPower($sourceFaction, $power);
    	$this->addFactionPower($targetFaction, $power);
    	return $power;
    }

    /**
     * Safely subtract any amount of power from the faction.
     *
     * @param string $faction
     * @param int $power
     */
    public function subtractFactionPower($faction, $power) {
    	$stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        
        $allPower = $this->getFactionPower($faction, true);
        $normalPower = $this->getFactionPower($faction, false);
        $reservePower = $allPower - $normalPower;
    
        if($normalPower - $power < 0){
        	
        	$stmt->bindValue(":power", 0);
        	
        	$remainder = $normalPower - $power;
        	
        	while($remainder > 0){ //Reserve power found
        	
        	    //Deduct from bank
        		$bankStr = $this->factionBank->get($faction, 0) / self::STR_MONEY_EQUIV; //Money->STR
        		if($bankStr > 0){ //If we have STR in bank
        			$resultStr = $bankStr - $remainder;
        			if($resultStr > 0){ //Remainder is less than bank
        			    $this->factionBank->set($faction, $resultStr * self::STR_MONEY_EQUIV);
        			    break;
        			}
        			//Remainder is greater than bank
        			$this->factionBank->set($faction, 0);
        			$remainder -= $remainder - $bankStr;
        			continue;
        		}
        		
        		//Deduct from spawners. Sense of loop starts here
        		
        		$spawners = $this->factionSpawners->get($faction, []);
        		$strValues = array_values($spawners);
        		
        		$closest = -1;
        		foreach($strValues as $i => $value){
        			//If old difference > new difference, assign/reassign a closest match
        			if($closest === -1 || abs($remainder - $strValues[$closest]) > abs($remainder - $value)){
        				$closest = $i;
        			}
        		}
        		
        		assert($closest > -1);
        		
        	    $location = array_keys($spawners)[$closest];
        	    list($x, $y, $z, $world) = explode(":", $location);
        	    $level = Server::getInstance()->getLevelByName($world);
        	    if($level instanceof Level){
        	    	$level->setBlockIdAt($x, $y, $z, 0);
        	    	$remainder -= $spawners[$location];
        	    	unset($spawners[$location]);
        	    	$this->factionSpawners->set($faction, $spawners);
        	    }
				echo "[FACTION WHILE]\n";
				var_dump(compact("normalPower", "power", "remainder", "closest", "bankStr", "strValues", "spawners"));
        	}
        }else{
        	$stmt->bindValue(":power", $this->getFactionPower($faction, false) - $power);
        }
        $stmt->execute();
    }
   
    /**
     * @param string $fac
     * @return string[]
     */
    public function getFactionPlayers(string $fac) : array{
    	$members = [];
    	$result = $this->db->query("SELECT player FROM master WHERE faction='$fac';");
    	while($resultArr = $result->fetchArray(SQLITE3_ASSOC)){
    		$members[] = $resultArr["player"];
    	}
    	return $members;
    }
	
	public function getPlayerRank($player) : string{
		if($this->isInFaction($player)){
        	if($this->isOfficer($player)){
                return "*";
            }
            if($this->isLeader($player)){
                return "**";
            }
		}
		return "";
	}

    public function isLeader($player) {
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Leader";
    }

    public function isOfficer($player) {
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Officer";
    }

    public function isMember($player) {
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Member";
    }

    public function getPlayersInFactionByRank($s, $faction, $rank) {

        if ($rank != "Leader") {
            $rankname = $rank . 's';
        } else {
            $rankname = $rank;
        }
        $team = "";
        $result = $this->db->query("SELECT player FROM master WHERE faction='$faction' AND rank='$rank';");
        $row = array();
        $i = 0;

        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $row[$i]['player'] = $resultArr['player'];
            if ($this->getServer()->getPlayerExact($row[$i]['player']) instanceof Player) {
                $team .= TextFormat::AQUA . $row[$i]['player'] . TextFormat::GREEN . "[ON]" . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            } else {
                $team .= TextFormat::AQUA . $row[$i]['player'] . TextFormat::RED . "[OFF]" . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            }
            $i = $i + 1;
        }

        $s->sendMessage($this->formatMessage("~ *<$rankname> of |$faction|* ~", true));
        $s->sendMessage($team);
    }

    public function getAllAllies($s, $faction) {

        $team = "";
        $result = $this->db->query("SELECT faction2 FROM allies WHERE faction1='$faction';");
        $row = array();
        $i = 0;
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $row[$i]['faction2'] = $resultArr['faction2'];
            $team .= TextFormat::ITALIC . TextFormat::RED . $row[$i]['faction2'] . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            $i = $i + 1;
        }

        $s->sendMessage($this->formatMessage("~ Allies of *$faction* ~", true));
        $s->sendMessage($team);
    }
    
    public function getTopFactions(int $count = 10) : array{
    	$result = $this->db->query("SELECT * FROM strength;");
    	$factions = [];
    	while($resultArray = $result->fetchArray(SQLITE3_ASSOC)){
    		$factions[] = [$resultArray["faction"], $this->getFactionPower($resultArray["faction"])];
    	}
    	usort($factions, function($A, $B){
    		return $A[1] <= $B[1];
    	});
    	array_splice($factions, $count - 1, -1);
    	return $factions;
    }

    public function sendListOfTop10FactionsTo($s) {
        $tf = "";
        $i = 0;
        $s->sendMessage($this->formatMessage("~ Top 10 strongest factions ~", true));
        foreach($this->getTopFactions(10) as $faction){
            $j = $i + 1;
            $cf = $faction[0];
            $pf = $faction[1];
            $df = $this->getNumberOfPlayers($cf);
            $s->sendMessage(TextFormat::GOLD . "$j -> " . TextFormat::GREEN . "$cf" . TextFormat::GOLD . " with " . TextFormat::RED . "$pf STR" . TextFormat::GOLD . " and " . TextFormat::LIGHT_PURPLE . "$df PLAYERS" . TextFormat::RESET);
            $i = $i + 1;
        }
    }

    public function getPlayerFaction($player) {
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["faction"];
    }

    public function getLeader($faction) {
        $leader = $this->db->query("SELECT player FROM master WHERE faction='$faction' AND rank='Leader';");
        $leaderArray = $leader->fetchArray(SQLITE3_ASSOC);
        return $leaderArray['player'];
    }

    public function factionExists($faction) {
        $result = $this->db->query("SELECT player FROM master WHERE faction='$faction';");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }

    public function sameFaction($player1, $player2) {
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player1';");
        $player1Faction = $faction->fetchArray(SQLITE3_ASSOC);
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player2';");
        $player2Faction = $faction->fetchArray(SQLITE3_ASSOC);
        return $player1Faction["faction"] == $player2Faction["faction"];
    }

    public function getNumberOfPlayers($faction) {
        $query = $this->db->query("SELECT COUNT(player) as count FROM master WHERE faction='$faction';");
        $number = $query->fetchArray();
        return $number['count'];
    }

    public function isFactionFull($faction) {
        return $this->getNumberOfPlayers($faction) >= $this->prefs->get("MaxPlayersPerFaction");
    }

    public function isNameBanned($name) {
        $bannedNames = file_get_contents($this->getDataFolder() . "BannedNames.txt");
        $isbanned = false;
        if (isset($name) && $this->antispam && $this->antispam->getProfanityFilter()->hasProfanity($name)) $isbanned = true;

        return (strpos(mb_strtolower($bannedNames), mb_strtolower($name)) > 0 || $isbanned);
    }

    public function newPlot($faction, $x1, $z1, $x2, $z2) {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO plots (faction, x1, z1, x2, z2) VALUES (:faction, :x1, :z1, :x2, :z2);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":x1", $x1);
        $stmt->bindValue(":z1", $z1);
        $stmt->bindValue(":x2", $x2);
        $stmt->bindValue(":z2", $z2);
        $result = $stmt->execute();
    }

    public function drawPlot($sender, $faction, $x, $y, $z, $level, $size) {
        $arm = ($size - 1) / 2;
        $block = new Snow();
        if ($this->cornerIsInPlot($x + $arm, $z + $arm, $x - $arm, $z - $arm)) {
            $claimedBy = $this->factionFromPoint($x, $z);
            $power_claimedBy = $this->getFactionPower($claimedBy);
            $power_sender = $this->getFactionPower($faction);

            if ($this->prefs->get("EnableOverClaim")) {
                if ($power_sender < $power_claimedBy) {
                	LangManager::send("FactionsPro-overclaim-otherstr", $sender, $power_claimedBy);
                } else {
                	LangManager::send("FactionsPro-overclaim-available", $sender);
                }
                return false;
            } else {
                LangManager::send("FactionsPro-overclaim-off", $sender);
                return false;
            }
        }
        $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
        $level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
        $this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
        return true;
    }

    public function isInPlot($player) {
        $x = $player->getFloorX();
        $z = $player->getFloorZ();
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }

    public function factionFromPoint($x, $z) {
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return $array["faction"];
    }

    public function inOwnPlot($player) {
        $playerName = $player->getName();
        $x = $player->getFloorX();
        $z = $player->getFloorZ();
        return $this->getPlayerFaction($playerName) == $this->factionFromPoint($x, $z);
    }

    public function pointIsInPlot($x, $z) {
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return !empty($array);
    }

    public function cornerIsInPlot($x1, $z1, $x2, $z2) {
        return($this->pointIsInPlot($x1, $z1) || $this->pointIsInPlot($x1, $z2) || $this->pointIsInPlot($x2, $z1) || $this->pointIsInPlot($x2, $z2));
    }

    public function formatMessage($string, $confirm = false) {
        if ($confirm) {
            return TextFormat::GREEN . "$string";
        } else {
            return TextFormat::YELLOW . "$string";
        }
    }

    public function motdWaiting($player) {
        $stmt = $this->db->query("SELECT player FROM motdrcv WHERE player='$player';");
        $array = $stmt->fetchArray(SQLITE3_ASSOC);
        return !empty($array);
    }

    public function getMOTDTime($player) {
        $stmt = $this->db->query("SELECT timestamp FROM motdrcv WHERE player='$player';");
        $array = $stmt->fetchArray(SQLITE3_ASSOC);
        return $array['timestamp'];
    }

    public function setMOTD($faction, $player, $msg) {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO motd (faction, message) VALUES (:faction, :message);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":message", $msg);
        $result = $stmt->execute();

        $this->db->query("DELETE FROM motdrcv WHERE player='$player';");
    }

    public function updateTag($playername) {
        $p = $this->getServer()->getPlayer($playername);
        if($p !== null){
			$p->setNametag(Main::getInstance()->permissionManager->getNametag($p));
        }
    }
    
    public function factionInfoUpdate($player) : void{
    	$f = $this->getPlayerFaction($player);
    	$rank = "Member";
        if($this->isOfficer($player)){
        	$rank = "Officer";
        }elseif($this->isLeader($player)){
        	$rank = "Leader";
        }
        $f = !empty($f) ? ($f . " [" . $rank . "]") : "";
        Main::getInstance()->updateDiscordEntry($player, "faction", $f);
    }

    public function onDisable() {
        $this->db->close();
        $this->factionBank->save();
        $this->factionSpawners->save();
    }

}
