<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\level\level;
use pocketmine\level\Position;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\inventory\SaveableInventory;

class FactionCommands {
    public $plugin;

    public function __construct(FactionMain $pg){
        $this->plugin = $pg;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($sender instanceof Player){
            $playerName = $sender->getPlayer()->getName();
            if(mb_strtolower($command->getName()) === "f"){
                if(empty($args)){
                    $sender->sendMessage($this->plugin->formatMessage("&9Please use /f help for a list of commands"));
                    return true;
                }
                    ///////////////////////////////// RENAME /////////////////////////////////
                    
                    if($args[0] === "rename"){
                    	if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($sender->getName())){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!isset($args[1])){
                        	LangManager::send("factions-usage-rename", $sender);
                        	return true;
                        }
                        $new_name = $args[1];
                        if(!($this->alphanum($new_name))){
                            LangManager::send("alphanumeric", $sender);
                            return true;
                        }
                        if($this->plugin->isNameBanned($new_name)){
                            LangManager::send("factions-bannedname", $sender);
                            return true;
                        }
                        if($this->plugin->factionExists($new_name)){
                        	LangManager::send("factions-exists", $sender);
                            return true;
                        }
                        $old_name = $this->plugin->getPlayerFaction($sender->getName());
                        $result = $sender->reduceMoney($cost = 50000000);
                        var_dump($result);
                        if($result){
                        	$this->plugin->db->exec("UPDATE master SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->db->exec("UPDATE plots SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->db->exec("UPDATE allies SET faction1='$new_name' WHERE faction1='$old_name';");
                        	$this->plugin->db->exec("UPDATE allies SET faction2='$new_name' WHERE faction2='$old_name';");
                        	$this->plugin->db->exec("UPDATE plots SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->db->exec("UPDATE strength SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->db->exec("UPDATE motd SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->db->exec("UPDATE home SET faction='$new_name' WHERE faction='$old_name';");
                        	$this->plugin->updateAllies($new_name);
                        	$this->plugin->updateTag($sender->getName());
                        	$this->plugin->factionInfoUpdate($sender->getName());
                        	LangManager::send("factions-rename", $sender, $new_name);
                        }else{
                        	LangManager::send("money-needed", $sender, $cost);
                        }
                    }
                    
                    ///////////////////////////////// VAULT /////////////////////////////////
                    
                    if($args[0] === "vault"){
                    	if(!$this->plugin->isInFaction($sender->getName())){
                    		
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $f = $this->plugin->getPlayerFaction($sender->getName());
                        $str = $this->plugin->getFactionPower($f);
                        if($str < 500){
                        	LangManager::send("factions-vault-str", $sender); 
                        	return true;
                        }
                        
                        $vault = SaveableInventory::createInventory("fvault_" . $f);
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
						$readonly = !$this->plugin->isLeader($sender->getName()) && !$this->plugin->isOfficer($sender->getName());
						if($readonly){
                    		$menu->setListener(InvMenu::readonly());
						}
                        $menu->setName($f . "'s Vault");
                        $menu->getInventory()->setContents($vault->getContents());
                        $menu->send($sender);
                        $menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) use($readonly, $f, $vault){
                        	if(!$readonly){
                        		$vault->setContents($inventory->getContents());
                        	}
                        });
                        return true;
                    }
                    
                    ///////////////////////////////// NEAR /////////////////////////////////
                    
                    if($args[0] === "near"){
                    	if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $str = $this->plugin->getFactionPower($faction);
                        
                        $nearBases = [];
                        $result = $this->plugin->db->query("SELECT * from plots");
                        while($resultArray = $result->fetchArray(SQLITE3_ASSOC)){
                        	$loc = new Vector3(($resultArray["x1"] + $resultArray["x2"]) / 2, $sender->getY(), ($resultArray["z1"] + $resultArray["z2"]) / 2);
                        	if($loc->distance($sender) <= $str){
                        		$nearBases[$resultArray["faction"]] = $loc;
                        	}
                        }
                        if(empty($nearBases)){
                        	$msg = LangManager::translate("factions-near-none", $sender);
                        }else{
                        	$msg = LangManager::translate("factions-near-found", $sender, count($nearBases));
                        	foreach($nearBases as $faction => $loc){
                        		$xDist = $loc->getX() - $sender->getX();
                        		$zDist = $loc->getZ() - $sender->getZ();
                        		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
                        		if($yaw < 0){
                        			$yaw += 360.0;
                        		}
                        		$msg .= "\n" . LangManager::translate("factions-near-base", $sender, round($loc->distance($sender)), Main::getInstance()->getCompassDirection($yaw));
                        	}
                        }
                        $sender->sendMessage($msg);
                    }
                    
                    ///////////////////////////////// BANK /////////////////////////////////
                    
                    if($args[0] === "bank"){
                    	if(!isset($args[1]) || !in_array($action = mb_strtolower($args[1]), ["deposit", "withdraw", "reserve"])){
                    		LangManager::send("factions-bank-usage", $sender);
                    		return true;
                    	}
                    	if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        switch($action){
                        	case "deposit":
                        	    if(!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 1){
                        	    	LangManager::send("factions-bank-depositnone", $sender);
                        	    	return true;
                        	    }
                        	    $money = intval($args[2]);
                        	    if(!Main::getInstance()->reduceMoney($sender, $money)){
                        	    	LangManager::send("money-needed", $sender, $money);
                        	    	return true;
                        	    }
                        	    $this->plugin->factionBank->set($faction, $this->plugin->factionBank->get($faction, 0) + $money);
                        	    LangManager::send("factions-bank-deposit", $sender, $money);
                        	    return true;
                        	    break;
                        	case "withdraw":
                        	    if(!$this->plugin->isLeader($sender->getName())){
                        	    	LangManager::send("factions-onlyleader", $sender);
                        	    	return true;
                        	    }
                        	    if(!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 1){
                        	    	LangManager::send("factions-bank-withdrawnone", $sender);
                        	    	return true;
                        	    }
                        	    $money = intval($args[2]);
                        	    $factionBank = $this->plugin->factionBank->get($faction, 0);
                        	    if($money > $factionBank){
                        	    	LangManager::send("factions-bank-withdrawnomoney", $sender, $money);
                        	    	return true;
                        	    }
                        	    $this->plugin->factionBank->set($faction, $factionBank - $money);
                        	    Main::getInstance()->addMoney($sender, $money);
                        	    LangManager::send("factions-bank-withdraw", $sender, $money);
                        	    return true;
                        	    break;
                        	case "reserve":
                        	    LangManager::send("factions-bank-reserve", $sender, $faction, $this->plugin->factionBank->get($faction, 0));
                        	    return true;
                        	    break;
                        }
                    }

                    ///////////////////////////////// WAR /////////////////////////////////

                    if($args[0] == "war"){
                        if(!isset($args[1])){
                        	LangManager::translate("factions-war-usage", $sender);
                            return true;
                        }
                        if(mb_strtolower($args[1]) == "tp"){
                            foreach ($this->plugin->wars as $r => $f){
                                $fac = $this->plugin->getPlayerFaction($playerName);
                                if($r == $fac){
                                    $x = \kenygamer\Core\Main::mt_rand(0, $this->plugin->getNumberOfPlayers($fac) - 1);
                                    $tper = $this->plugin->war_players[$f][$x];
                                    $sender->teleport($this->plugin->getServer()->getPlayerByName($tper));
                                    return true;
                                }
                                if($f == $fac){
                                    $x = \kenygamer\Core\Main::mt_rand(0, $this->plugin->getNumberOfPlayers($fac) - 1);
                                    $tper = $this->plugin->war_players[$r][$x];
                                    $sender->teleport($this->plugin->getServer()->getPlayer($tper));
                                    return true;
                                }
                            }
                            LangManager::send("factions-mustbewar", $sender);
                            return true;
                        }
                        if(!($this->alphanum($args[1]))){
                            LangManager::send("alphanumeric", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-facnotfound", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!$this->plugin->areEnemies($this->plugin->getPlayerFaction($playerName), $args[1])){
                        	LangManager::send("factions-war-notenemy", $sender, $args[1]);
                            return true;
                        } else {
                            $factionName = $args[1];
                            $sFaction = $this->plugin->getPlayerFaction($playerName);
                            foreach ($this->plugin->war_req as $r => $f){
                                if($r == $args[1] && $f == $sFaction){
                                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $p){
                                        $task = new FactionWar($this->plugin, $r);
                                        $handler = $this->plugin->getScheduler()->scheduleDelayedTask($task, 20 * 60 * 2);
                                        $task->setHandler($handler);
                                        LangManager::send("factions-war", $p, $factionName, $sFaction);
                                        if($this->plugin->getPlayerFaction($p->getName()) == $sFaction){
                                            $this->plugin->war_players[$sFaction][] = $p->getName();
                                        }
                                        if($this->plugin->getPlayerFaction($p->getName()) == $factionName){
                                            $this->plugin->war_players[$factionName][] = $p->getName();
                                        }
                                    }
                                    $this->plugin->wars[$factionName] = $sFaction;
                                    unset($this->plugin->war_req[mb_strtolower($args[1])]);
                                    return true;
                                }
                            }
                            $this->plugin->war_req[$sFaction] = $factionName;
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p){
                                if($this->plugin->getPlayerFaction($p->getName()) == $factionName){
                                    if($this->plugin->getLeader($factionName) == $p->getName()){
                                        LangManager::send("factions-war-request", $p, $sFaction);
                                        LangManager::send("factions-war-requested", $p);
                                        return true;
                                    }
                                }
                            }
                            LangManager::send("factions-leaderoffline", $sender);
                            return true;
                        }
                    }

                    /////////////////////////////// CREATE ///////////////////////////////

                    if($args[0] == "create"){
                        if(!isset($args[1])){
                        	LangManager::send("factions-create-usage", $sender);
                            return true;
                        }
                        if(!($this->alphanum($args[1]))){
                            LangManager::send("alphanumeric", $sender);
                            return true;
                        }
                        if($this->plugin->isNameBanned($args[1])){
                            LangManager::send("factions-bannedname", $sender);
                            return true;
                        }
                        if($this->plugin->factionExists($args[1])){
                        	LangManager::send("factions-exists", $sender);
                            return true;
                        }
                        if(strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")){
                        	LangManager::send("factions-toolong", $sender);
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName())){
                        	LangManager::send("factions-create-leave", $sender);
                            return true;
                        } else {
                            $factionName = $args[1];
                            $rank = "Leader";
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", $playerName);
                            $stmt->bindValue(":faction", $factionName);
                            $stmt->bindValue(":rank", $rank);
                            $result = $stmt->execute();
                            $this->plugin->updateAllies($factionName);
                            $this->plugin->setFactionPower($factionName, $this->plugin->prefs->get("TheDefaultPowerEveryFactionStartsWith"));
                            $this->plugin->updateTag($sender->getName());
                            LangManager::send("factions-create", $sender);
                            $this->plugin->factionInfoUpdate($sender->getName());
                            return true;
                        }
                    }

                    /////////////////////////////// INVITE ///////////////////////////////

                    if($args[0] == "invite"){
                        if(!isset($args[1])){
                            LangManager::send("factions-invite-usage", $sender);
                            return true;
                        }
                        if($this->plugin->isFactionFull($this->plugin->getPlayerFaction($playerName))){
                            LangManager::send("factions-invite-full", $sender);
                            return true;
                        }
                        $invited = $this->plugin->getServer()->getPlayerExact($args[1]);
                        if(!($invited instanceof Player)){
                        	LangManager::send("player-notfound", $sender);
                            return true;
                        }
                        if($this->plugin->isInFaction($invited->getName()) == true){
                            LangManager::send("factions-invite-infac", $sender);
                            return true;
                        }
                        if($this->plugin->prefs->get("OnlyLeadersAndOfficersCanInvite")){
                            if(!($this->plugin->isOfficer($playerName) || $this->plugin->isLeader($playerName))){
                            	LangManager::send("factions-onlyofficers", $sender);
                                return true;
                            }
                        }
                        if($invited->getName() == $playerName){
                        	LangManager::send("factions-invite-other", $sender);
                            return true;
                        }

                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $invitedName = $invited->getName();
                        $rank = "Member";

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
                        $stmt->bindValue(":player", $invitedName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":invitedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                        LangManager::send("factions-invite", $sender, $invitedName);
                        LangManager::send("factions-invited", $invited, $factionName);
                    }

                    /////////////////////////////// LEADER ///////////////////////////////

                    if($args[0] == "leader"){
                        if(!isset($args[1])){
                            LangManager::send("factions-leader-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])){
                        	LangManager::send("factions-leader-add", $sender);
                            return true;
                        }
                        if(!($this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player)){
                            LangManager::send("player-notfound", $sender);
                            return true;
                        }
                        if($args[1] == $sender->getName()){
                            LangManager::send("factions-leader-other", $sender);
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $playerName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Leader");
                        $result = $stmt->execute();

						LangManager::send("factions-leader", $sender);
                        $leader = $this->plugin->getServer()->getPlayerExact($args[1]);
                        LangManager::send("factions-leader-given", $leader, $factionName);
                        $this->plugin->updateTag($sender->getName());
                        $this->plugin->updateTag($leader->getName());
                    }

                    /////////////////////////////// PROMOTE ///////////////////////////////

                    if($args[0] == "promote"){
                        if(!isset($args[1])){
                        	LangManager::send("factions-promote-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])){
                            LangManager::send("factions-othernofaction", $sender);
                            return true;
                        }
                        if($args[1] == $sender->getName()){
                            LangManager::send("factions-promote-other", $sender);
                            return true;
                        }

                        if($this->plugin->isOfficer($args[1])){
                            LangManager::send("factions-promote-failed", $sender);
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Officer");
                        $result = $stmt->execute();
                        $promotee = $this->plugin->getServer()->getPlayerExact($args[1]);
                        LangManager::send("factions-promote", $sender, $args[1]);

                        if($promotee instanceof Player){
                        	LangManager::send("factions-promoted", $promotee, $factionName);
                            $this->plugin->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                            return true;
                        }
                    }

                    /////////////////////////////// DEMOTE ///////////////////////////////

                    if($args[0] == "demote"){
                        if(!isset($args[1])){
                            LangManager::send("factions-demote-usage", $sender);
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if($this->plugin->isLeader($playerName) == false){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])){
                            LangManager::send("factions-othernofaction", $sender);
                            return true;
                        }

                        if($args[1] == $sender->getName()){
                            LangManager::send("factions-demote-other", $sender);
                            return true;
                        }
                        if(!$this->plugin->isOfficer($args[1])){
                            LangManager::send("factions-demote-failed", $sender);
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $demotee = $this->plugin->getServer()->getPlayerExact($args[1]);
                        LangManager::send("factions-demote", $sender, $args[1]);
                        if($demotee instanceof Player){
                        	LangManager::send("factions-demoted", $demotee, $factionName);
                            $this->plugin->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                            return true;
                        }
                    }

                    /////////////////////////////// KICK ///////////////////////////////

                    if($args[0] == "kick"){
                        if(!isset($args[1])){
                            LangManager::send("factions-kick-usage", $sender);
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if($this->plugin->isLeader($playerName) == false){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])){
                            LangManager::send("factions-othernofaction", $sender);
                            return true;
                        }
                        if($args[1] == $sender->getName()){
                            LangManager::send("factions-kick-failed", $sender);
                            return true;
                        }
                        $kicked = $this->plugin->getServer()->getPlayerExact($args[1]);
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $this->plugin->db->query("DELETE FROM master WHERE player='$args[1]';");
                        LangManager::send("factions-kick", $sender, $args[1]);
                        $this->plugin->subtractFactionPower($factionName, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                        $this->plugin->factionInfoUpdate($playerName);

                        if($kicked instanceof Player){
                        	LangManager::send("factions-kicked", $kicked, $factionName);
                            $this->plugin->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                            return true;
                        }
                    }



                    /////////////////////////////// CLAIM ///////////////////////////////

                    if(mb_strtolower($args[0]) == 'claim'){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!in_array($sender->getPlayer()->getLevel()->getName(), $this->plugin->prefs->get("ClaimWorlds"))){
                        	LangManager::send("factions-claim-worlds", $sender, implode(" ", $this->plugin->prefs->get("ClaimWorlds")));
                            return true;
                        }

                        if($this->plugin->inOwnPlot($sender)){
                        	LangManager::send("factions-claim-claimed", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
                        if($this->plugin->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")){

                            $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                            $this->plugin->getNumberOfPlayers($faction);
                            LangManager::send("factions-claim-pneeded", $sender, $needed_players);
                            $sender->sendMessage($this->plugin->formatMessage("You need $needed_players more players in your faction to claim a faction plot"));
                            return true;
                        }
                        if($this->plugin->getFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")){
                            $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                            $faction_power = $this->plugin->getFactionPower($faction);
                            LangManager::send("factions-claim-nostr", $sender, $needed_power, $faction_power);
                            return true;
                        }

                        $x = floor($sender->getX());
                        $y = floor($sender->getY());
                        $z = floor($sender->getZ());
                        if($this->plugin->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), $this->plugin->prefs->get("PlotSize")) == false){

                            return true;
                        }

                        $plot_size = $this->plugin->prefs->get("PlotSize");
                        $faction_power = $this->plugin->getFactionPower($faction);
                        LangManager::send("factions-claim", $sender);
                    }
                    if(mb_strtolower($args[0]) == 'plotinfo'){
                        $x = floor($sender->getX());
                        $y = floor($sender->getY());
                        $z = floor($sender->getZ());
                        if(!$this->plugin->isInPlot($sender)){
                        	LangManager::send("factions-plotinfo-free", $sender);
                            return true;
                        }

                        $fac = $this->plugin->factionFromPoint($x, $z);
                        $power = $this->plugin->getFactionPower($fac);
                        LangManager::send("factions-plotinfo-claimed", $sender, $fac, $power);
                    }
                    if(mb_strtolower($args[0]) == 'top'){
                        $this->plugin->sendListOfTop10FactionsTo($sender);
                    }
                    if(mb_strtolower($args[0]) == 'forcedelete'){
                        if(!isset($args[1])){
                            LangManager::send("factions-forcedelete-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if(!($sender->isOp())){
                            LangManager::send("onlyop", $sender);
                            return true;
                        }
                        $this->plugin->db->query("DELETE FROM master WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM allies WHERE faction1='$args[1]';");
                        $this->plugin->db->query("DELETE FROM allies WHERE faction2='$args[1]';");
                        $this->plugin->db->query("DELETE FROM strength WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM motd WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM home WHERE faction='$args[1]';");
                        LangManager::send("factions-forcedelete", $sender);
                    }
                    if(mb_strtolower($args[0]) == 'reward'){
                    	if(!$sender->isOp()){
                    		LangManager::send("cmd-noperm", $sender);
                    		return true;
                    	}
                    	$sender->sendMessage(implode(", ", $this->plugin->topFactionsReward()));
                    	return true;
                    }
                    if(mb_strtolower($args[0]) == 'addstrto'){
                        if(!isset($args[1]) or ! isset($args[2])){
                            LangManager::send("factions-addstrto-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if(!($sender->isOp()) && !$sender->hasPermission("f.command.addstrto.subtract")){
                            LangManager::send("onlyop", $sender);
                            return true;
                        }
                        if(!$sender->isOp() && $args[2] > -1){
                        	LangManager::send("factions-addstrto-limited", $sender);
                        	return true;
                        }
                        $this->plugin->addFactionPower($args[1], $args[2]);
                        LangManager::send("factions-addstrto", $sender, $args[2], $args[1]);
                    }
                    if(mb_strtolower($args[0]) == 'pf'){
                        if(!isset($args[1])){
                        	LangManager::send("factions-pf-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($args[1])){
                        	LangManager::send("factions-othernofaction", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($args[1]);
                        LangManager::send("factions-pf", $sender, $args[1], $faction);
                    }

                    if(mb_strtolower($args[0]) == 'overclaim'){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($playerName);
                        if($this->plugin->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")){

                            $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                            $this->plugin->getNumberOfPlayers($faction);
                            LangManager::send("factions-overclaim-pneeded", $sender, $needed_players);
                            return true;
                        }
                        if($this->plugin->getFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")){
                            $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                            $faction_power = $this->plugin->getFactionPower($faction);
                            LangManager::send("factions-overclaim-nostr", $sender, $needed_power, $faction_power);
                            return true;
                        }
                        $x = floor($sender->getX());
                        $y = floor($sender->getY());
                        $z = floor($sender->getZ());
                        if($this->plugin->prefs->get("EnableOverClaim")){
                            if($this->plugin->isInPlot($sender)){
                                $faction_victim = $this->plugin->factionFromPoint($x, $z);
                                $faction_victim_power = $this->plugin->getFactionPower($faction_victim);
                                $faction_ours = $this->plugin->getPlayerFaction($playerName);
                                $faction_ours_power = $this->plugin->getFactionPower($faction_ours);
                                if($this->plugin->inOwnPlot($sender)){
                                    LangManager::send("factions-overclaim-other", $sender);
                                    return true;
                                } else {
                                    if($faction_ours_power < $faction_victim_power){
                                        LangManager::send("factions-overclaim-str", $sender, $faction_victim);
                                        return true;
                                    } else {
                                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_ours';");
                                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_victim';");
                                        $arm = (($this->plugin->prefs->get("PlotSize")) - 1) / 2;
                                        $this->plugin->newPlot($faction_ours, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
                                        LangManager::send("factions-overclaim", $sender, $faction_victim);
                                        return true;
                                    }
                                }
                            } else {
                                LangManager::send("factions-overclaim-failed", $sender);
                                return true;
                            }
                        } else {
                            LangManager::send("factions-overclaim-off", $sender);
                            return true;
                        }
                    }


                    /////////////////////////////// UNCLAIM ///////////////////////////////

                    if(mb_strtolower($args[0]) == "unclaim"){
                        if(!$this->plugin->isInFaction($sender->getName())){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($sender->getName())){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                        LangManager::send("factions-unclaim", $sender);
                    }

                    /////////////////////////////// DESCRIPTION ///////////////////////////////

                    if(mb_strtolower($args[0]) == "desc"){
                        if($this->plugin->isInFaction($sender->getName()) == false){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if($this->plugin->isLeader($playerName) == false){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        LangManager::send("factions-desc", $sender);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO motdrcv (player, timestamp) VALUES (:player, :timestamp);");
                        $stmt->bindValue(":player", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                    }

                    /////////////////////////////// ACCEPT ///////////////////////////////

                    if(mb_strtolower($args[0]) == "accept"){
                        $lowercaseName = mb_strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if(empty($array) == true){
                        	LangManager::send("factions-notinvitee", $sender);
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if(($currentTime - $invitedTime) <= 60){ //This should be configurable
                            $faction = $array["faction"];
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", ($playerName));
                            $stmt->bindValue(":faction", $faction);
                            $stmt->bindValue(":rank", "Member");
                            $result = $stmt->execute();
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            LangManager::send("factions-accepted", $this->plugin->getServer()->getPlayerExact($array["invitedby"]), $sender->getName());
                            $this->plugin->addFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                            $this->plugin->updateTag($sender->getName());
                            $this->plugin->factionInfoUpdate($playerName);
                        } else {
                            LangManager::send("timedout", $sender);
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$playerName';");
                        }
                    }

                    /////////////////////////////// DENY ///////////////////////////////

                    if(mb_strtolower($args[0]) == "deny"){
                        $lowercaseName = mb_strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if(empty($array) == true){
                        	LangManager::send("factions-notinvitee", $sender);
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if(($currentTime - $invitedTime) <= 60){ //This should be configurable
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            LangManager::send("factions-deny", $sender);
                            $invitedBy = $this->plugin->getServer()->getPlayerExact($array["invitedby"]);
                            if($invitedBy !== null){
                            	LangManager::send("factions-deny-target", $invitedBy, $playerName);
                            }
                        } else {
                            LangManager::send("factions-timedout", $sender);
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                        }
                    }

                    /////////////////////////////// DELETE ///////////////////////////////

                    if(mb_strtolower($args[0]) == "del"){
                        if($this->plugin->isInFaction($playerName) == true){
                            if($this->plugin->isLeader($playerName)){
                                unset($this->plugin->factionChatActive[$playerName]);
								unset($this->plugin->allyChatActive[$playerName]);
                                $faction = $this->plugin->getPlayerFaction($playerName);
                                foreach($this->plugin->getFactionPlayers($faction) as $p){ 
                            	    $this->plugin->factionInfoUpdate($p);
                            	}
                                $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction1='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction2='$faction';");
                                $this->plugin->db->query("DELETE FROM strength WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM motd WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM home WHERE faction='$faction';");
                                $sender->sendMessage($this->plugin->formatMessage("Faction successfully disbanded and the faction plot was unclaimed", true));
                                $this->plugin->updateTag($sender->getName());
                            } else {
                                LangManager::send("factions-onlyleader", $sender);
                            }
                        } else {
                            LangManager::send("factions-nofaction", $sender);
                        }
                    }

                    /////////////////////////////// LEAVE ///////////////////////////////

                    if(mb_strtolower($args[0] == "leave")){
                        if($this->plugin->isLeader($playerName) == false){
                            unset($this->plugin->factionChatActive[$playerName]);
							unset($this->plugin->allyChatActive[$playerName]);
                            $remove = $sender->getPlayer()->getNameTag();
                            $faction = $this->plugin->getPlayerFaction($playerName);
                            $name = $sender->getName();
                            $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                            LangManager::send("factions-leave", $sender, $faction);
                            $this->plugin->subtractFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                            $this->plugin->updateTag($sender->getName());
                            $this->plugin->factionInfoUpdate($sender->getName());
                        } else {
                            LangManager::send("factions-leave-failed", $sender);
                        }
                    }

                    /////////////////////////////// SETHOME ///////////////////////////////

                    if(mb_strtolower($args[0] == "sethome")){
                    	if(!in_array($sender->getLevel()->getFolderName(), Main::SETPOINT_WORLDS)){
                    		LangManager::send("sethome-disallowed", $sender);
                    		return true;
                    	}
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($sender->getName());
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z, world) VALUES (:faction, :x, :y, :z, :world);");
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":x", $sender->getX());
                        $stmt->bindValue(":y", $sender->getY());
                        $stmt->bindValue(":z", $sender->getZ());
						$stmt->bindValue(":world", $sender->getLevel()->getFolderName());
                        $result = $stmt->execute();
                        LangManager::send("factions-sethome", $sender);
                    }

                    /////////////////////////////// UNSETHOME ///////////////////////////////

                    if(mb_strtolower($args[0] == "unsethome")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
                        LangManager::send("factions-unsethome", $sender);
                    }

                    /////////////////////////////// HOME ///////////////////////////////

                    if(mb_strtolower($args[0] == "home")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if(!empty($array) && isset($array["world"])){
                            $sender->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $this->plugin->getServer()->getLevelByName($array['world'])));
                            LangManager::send("factions-home", $sender);
                        } else {
                            LangManager::send("factions-home-failed", $sender);
                        }
                    }

                    /////////////////////////////// MEMBERS/OFFICERS/LEADER AND THEIR STATUSES ///////////////////////////////
                    if(mb_strtolower($args[0] == "ourmembers")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Member");
                    }
                    if(mb_strtolower($args[0] == "membersof")){
                        if(!isset($args[1])){
                            LangManager::send("factions-membersof-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Member");
                    }
                    if(mb_strtolower($args[0] == "ourofficers")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Officer");
                    }
                    if(mb_strtolower($args[0] == "officersof")){
                        if(!isset($args[1])){
                            $sender->sendMessage($this->plugin->formatMessage("Usage: /f officersof <faction>"));
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Officer");
                    }
                    if(mb_strtolower($args[0] == "ourleader")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Leader");
                    }
                    if(mb_strtolower($args[0] == "leaderof")){
                        if(!isset($args[1])){
                            $sender->sendMessage($this->plugin->formatMessage("Usage: /f leaderof <faction>"));
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Leader");
                    }

                    ////////////////////////////// ALLY SYSTEM ////////////////////////////////
                    if(mb_strtolower($args[0] == "enemy")){
                        if(!isset($args[1])){
                            LangManager::send("factions-enemy-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) == $args[1]){
                            LangManager::send("factions-enemy-other", $sender);
                            return true;
                        }
                        if($this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])){
                            LangManager::send("factions-enemy-failed", $sender, $args[1]);
                            return true;
                        }
                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->getLeader($args[1]));

                        if(!($leader instanceof Player)){
                            LangManager::send("factions-otherleaderoffline", $sender);
                            return true;
                        }
                        $this->plugin->setEnemies($fac, $args[1]);
                        LangManager::send("factions-enemy", $sender, $args[1]);
                        LangManager::send("factions-enemied", $leader, $fac);
                    }
                    if(mb_strtolower($args[0] == "ally")){
                        if(!isset($args[1])){
                            LangManager::send("factions-ally-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) == $args[1]){
                            LangManager::send("factions-ally-other", $sender);
                            return true;
                        }
                        if($this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])){
                        	LangManager::send("factions-ally-failed", $sender, $args[1]);
                            return true;
                        }
                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->getLeader($args[1]));
                        $this->plugin->updateAllies($fac);
                        $this->plugin->updateAllies($args[1]);

                        if(!($leader instanceof Player)){
                            LangManager::send("factions-otherleaderoffline", $sender);
                            return true;
                        }
                        if($this->plugin->getAlliesCount($args[1]) >= $this->plugin->getAlliesLimit()){
                            LangManager::send("factions-ally-otherlimit", $sender);
                            return true;
                        }
                        if($this->plugin->getAlliesCount($fac) >= $this->plugin->getAlliesLimit()){
                            LangManager::send("factions-ally-limit", $sender);
                            return true;
                        }
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO alliance (player, faction, requestedby, timestamp) VALUES (:player, :faction, :requestedby, :timestamp);");
                        $stmt->bindValue(":player", $leader->getName());
                        $stmt->bindValue(":faction", $args[1]);
                        $stmt->bindValue(":requestedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                        LangManager::send("factions-ally", $sender, $args[1]);
                        LangManager::send("factions-ally-request", $leader, $fac);
                    }
                    if(mb_strtolower($args[0] == "unally")){
                        if(!isset($args[1])){
                            LangManager::send("factions-unally-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($playerName) == $args[1]){
                            LangManager::send("factions-unally-other", $sender);
                            return true;
                        }
                        if(!$this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])){
                            LangManager::send("factions-unally-failed", $sender, $args[1]);
                            return true;
                        }

                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->getLeader($args[1]));
                        $this->plugin->deleteAllies($fac, $args[1]);
                        $this->plugin->deleteAllies($args[1], $fac);
                        $this->plugin->subtractFactionPower($fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                        $this->plugin->subtractFactionPower($args[1], $this->plugin->prefs->get("PowerGainedPerAlly"));
                        $this->plugin->updateAllies($fac);
                        $this->plugin->updateAllies($args[1]);
                        LangManager::send("factions-unally", $sender, $fac, $args[1]);
                        if($leader instanceof Player){
                        	LangManager::send("factions-unallied", $leader, $fac, $args[1]);
                        }
                    }
                    if(mb_strtolower($args[0] == "forceunclaim")){
                        if(!isset($args[1])){
                            LangManager::send("factions-forceunclaim-usage", $sender);
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        if(!($sender->isOp())){
                            LangManager::send("onlyop", $sender);
                            return true;
                        }
                        LangManager::send("factions-forceunclaim", $sender, $args[1]);
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
                    }

                    if(mb_strtolower($args[0] == "allies")){
                        if(!isset($args[1])){
                            if(!$this->plugin->isInFaction($playerName)){
                                LangManager::send("factions-nofaction", $sender);
                                return true;
                            }

                            $this->plugin->updateAllies($this->plugin->getPlayerFaction($playerName));
                            $this->plugin->getAllAllies($sender, $this->plugin->getPlayerFaction($playerName));
                        } else {
                            if(!$this->plugin->factionExists($args[1])){
                                LangManager::send("factions-invalidfac", $sender);
                                return true;
                            }
                            $this->plugin->updateAllies($args[1]);
                            $this->plugin->getAllAllies($sender, $args[1]);
                        }
                    }
                    if(mb_strtolower($args[0] == "allyok")){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $lowercaseName = mb_strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if(empty($array) == true){
                            LangManager::send("factions-noally", $sender);
                            return true;
                        }
                        $allyTime = $array["timestamp"];
                        $currentTime = time();
                        if(($currentTime - $allyTime) <= 60){ //This should be configurable
                            $requested_fac = $this->plugin->getPlayerFaction($array["requestedby"]);
                            $sender_fac = $this->plugin->getPlayerFaction($playerName);
                            $this->plugin->setAllies($requested_fac, $sender_fac);
                            $this->plugin->setAllies($sender_fac, $requested_fac);
                            $this->plugin->addFactionPower($sender_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                            $this->plugin->addFactionPower($requested_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                            $this->plugin->updateAllies($requested_fac);
                            $this->plugin->updateAllies($sender_fac);
                            LangManager::send("factions-ally", $sender, $requested_fac);
                            $this->plugin->getServer()->getPlayerExact($array["requestedby"])->sendMessage($this->plugin->formatMessage("$playerName from $sender_fac has ed the alliance!", true));
                        } else {
                            LangManager::send("timedout", $sender);
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                        }
                    }
                    if(mb_strtolower($args[0]) == "allyno"){
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        if(!$this->plugin->isLeader($playerName)){
                            LangManager::send("factions-onlyleader", $sender);
                            return true;
                        }
                        $lowercaseName = mb_strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if(empty($array) == true){
                            LangManager::send("factions-noally", $sender);
                            return true;
                        }
                        $allyTime = $array["timestamp"];
                        $currentTime = time();
                        if(($currentTime - $allyTime) <= 60){ //This should be configurable
                            $requested_fac = $this->plugin->getPlayerFaction($array["requestedby"]);
                            $sender_fac = $this->plugin->getPlayerFaction($playerName);
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                            LangManager::send("factions-allydeny", $sender);
                            $requestedBy = $this->plugin->getServer()->getPlayerExact($array["requestedby"]);
                            LangManager::send("factions-allydenied", $sender, $playerName, $sender_fac);
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("Request has timed out"));
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                        }
                    }

                    ////////////////////////////// CHAT ////////////////////////////////
                    if(mb_strtolower($args[0]) == "chat" or mb_strtolower($args[0]) == "c"){

                        if(!$this->plugin->prefs->get("AllowChat")){
                            LangManager::send("factions-chat-off", $sender);
                            return true;
                        }
                        
                        if($this->plugin->isInFaction($playerName)){
                            if(isset($this->plugin->factionChatActive[$playerName])){
                                unset($this->plugin->factionChatActive[$playerName]);
                                LangManager::send("factions-chat-disabled", $sender);
                                return true;
                            } else {
                                $this->plugin->factionChatActive[$playerName] = 1;
                                LangManager::send("factions-chat-enabled", $sender);
                                return true;
                            }
                        } else {
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                    }
                    if(mb_strtolower($args[0]) == "allychat" or mb_strtolower($args[0]) == "ac"){

                        if(!$this->plugin->prefs->get("AllowChat")){
                            LangManager::send("factions-chat-off", $sender);
                            return true;
                        }
                        
                        if($this->plugin->isInFaction($playerName)){
                            if(isset($this->plugin->allyChatActive[$playerName])){
                                unset($this->plugin->allyChatActive[$playerName]);
                                LangManager::send("factions-allychat-disabled", $sender);
                                return true;
                            } else {
                                $this->plugin->allyChatActive[$playerName] = 1;
                                LangManager::send("factions-allychat-enabled", $sender);
                                return true;
                            }
                        } else {
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                    }

                /////////////////////////////// INFO ///////////////////////////////

                if(mb_strtolower($args[0]) == 'info'){
                    if(isset($args[1])){
                        if(!(ctype_alnum($args[1])) or !($this->plugin->factionExists($args[1]))){
                            LangManager::send("factions-invalidfac", $sender);
                            return true;
                        }
                        $faction = $args[1];
                        $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        $power = $this->plugin->getFactionPower($faction);
                        $message = $array["message"];
                        $leader = $this->plugin->getLeader($faction);
                        $numPlayers = $this->plugin->getNumberOfPlayers($faction);
                        LangManager::send("factions-info", $sender, $faction, $leader, $numPlayers, $power, $message);
                    } else {
                        if(!$this->plugin->isInFaction($playerName)){
                            LangManager::send("factions-nofaction", $sender);
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction(($sender->getName()));
                        $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        $power = $this->plugin->getFactionPower($faction);
                        $message = $array["message"];
                        $leader = $this->plugin->getLeader($faction);
                        $numPlayers = $this->plugin->getNumberOfPlayers($faction);
                        LangManager::send("factions-info", $sender, $faction, $leader, $numPlayers, $power, $message);
                    }
                    return true;
                }
                if(mb_strtolower($args[0]) == "help"){
                    if(!isset($args[1]) || $args[1] == 1){
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 1 of 6" . TextFormat::RED . "\n/f bank\n/f accept\n/f allychat <on:off>\n/f overclaim [Takeover the plot of the requested faction]\n/f chat <on:off>\n/f claim\n/f create <name>\n/f del\n/f demote <player>\n/f deny");
                        return true;
                    }
                    if($args[1] == 2){
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 2 of 6" . TextFormat::RED . "\n/f home\n/f help <page>\n/f info\n/f info <faction>\n/f invite <player>\n/f kick <player>\n/f leader <player>\n/f leave");
                        return true;
                    }
                    if($args[1] == 3){
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 3 of 6" . TextFormat::RED . "\n/f sethome\n/f unclaim\n/f unsethome\n/f ourmembers - {Members + Statuses}\n/f ourofficers - {Officers + Statuses}\n/f ourleader - {Leader + Status}\n/f allies - {The allies of your faction");
                        return true;
                    }
                    if($args[1] == 4){
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 4 of 6" . TextFormat::RED . "\n/f enemy <faction>\n/f desc\n/f promote <player>\n/f ally <faction>\n/f unally <faction>\n/f allyok [Accept a request for alliance]\n/f allyno [Deny a request for alliance]\n/f allies <faction> - {The allies of your chosen faction}");
                        return true;
                    }
                    if($args[1] == 5){
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 5 of 6" . TextFormat::RED . "\n/f membersof <faction>\n/f officersof <faction>\n/f leaderof <faction>\n/f near\n/f rename\n/f say <send message to everyone in your faction>\n/f pf <player>\n/f top\n/f vault\n/f war <faction name:tp>");
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::GOLD . "FactionsPro Help Page 6 of 6 Only OP" . TextFormat::RED . "\n/f forceunclaim <faction> [Unclaim a faction plot by force]\n/f forcedelete <faction> [Delete a faction by force]\n/f addstrto <faction> <STR> [Add positive/negative STR to a faction]\n/f reward");
                        return true;
                    }
                }
                return true;
            }
        } else {
            LangManager::send("run-ingame", $sender);
        }
        return true;
    }

    public function alphanum($string){
        if(function_exists('ctype_alnum')){
            $return = ctype_alnum($string);
        }else{
            $return = preg_match('/^[a-z0-9]+$/i', $string) > 0;
        }
        return $return;
    }
}
