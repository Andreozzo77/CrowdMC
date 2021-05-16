<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\land\Land;

class LandCommand extends BaseCommand{
	private const A_DAY = 60 * 60 * 24;
	private const A_WEEK = self::A_DAY * 7;
	
	/** @var int[] */
	private $lastLookup = [];
	
	public function __construct(){
		parent::__construct(
			"land",
			"Manage your lands",
			"/land <auto/list/sethome/home/deny/info/tp/addhelper/rmhelper/sell/help>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		static $PLAYER_SUBCOMMANDS = [
			"auto" => ["", "Find a free land."],
	        "list" => ["", "Show lands you own or are invited to."],
	        "sethome" => ["[landID]", "Set your main land."],
	        "home" => ["", "Teleport to your main land."],
	        "deny" => ["<landID> <player>", "Prevent certain players from getting in your land. (Toggle)"],
	        "info" => ["[landID]", "Retrieve information about a land."],
	        "tp" => ["<landID>", "Teleport to an owned land."],
	        "addhelper" => ["<landID> <player>", "Add a helper to your land."],
	        "rmhelper" => ["<landID> <player>", "Remove a helper from your land."],
	        "pay" => ["<landID>", "Pay a due rent."],
	        "dispose" => ["<landID>", "Dispose your land."],
	        "help" => ["", "View list of commands."]
	    ];
		static $OP_SUBCOMMANDS = [
			"pos1" => ["", "Set position 1."],
			"pos2" => ["", "Set position 2."],
			"pos3" => ["", "Set position 3."],
			"create" => ["<price>", "Create a land."],
			"delete" => ["<landID>", "Deletes a land."],
			"transfer" => ["<landID> <owner>", "Transfers the land ownership."]
		];
		$subcommands = $PLAYER_SUBCOMMANDS;
		if($sender->isOp()){
			$subcommands = $subcommands + $OP_SUBCOMMANDS;
		}
	    $action = array_shift($args);
	    switch($action){
			case "pos1":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
				$this->getPlugin()->landPos1[$sender->getName()] = true;
				$sender->sendMessage("pos1-block");
				break;
			case "pos2":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
				$this->getPlugin()->landPos2[$sender->getName()] = true;
				$sender->sendMessage("pos2-block");
				break;
			case "pos3":
				if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
				$this->getPlugin()->landPos3[$sender->getName()] = true;
				$sender->sendMessage("pos3-block");
				break;
			case "create":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
			    }
				foreach(["pos1", "pos2", "pos3"] as $pos){
					$property = "land" . ucfirst($pos); 
					if(!isset($this->getPlugin()->{$property}[$sender->getName()]) || !is_array($$pos = $this->getPlugin()->{$property}[$sender->getName()])){
						$sender->sendMessage("pos-setall");
						break 2;
					}
				}
				$price = (int) round($args[0]);
			    $id = $this->getPlugin()->landManager->createLand($pos1, $pos2, $sender->getLevel()->getFolderName(), $pos3, $price);
				$sender->sendMessage("land-create", $id, number_format($price));
			    break;
			case "delete":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
				$id = intval($args[0]);
			    $land = $this->getPlugin()->landManager->getLand($id);
	    	    if(!($land instanceof Land)){
	    	    	$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    $this->getPlugin()->landManager->deleteLand($id);
				$sender->sendMessage("land-delete", $id);
	    	    break;
	    	case "transfer":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
	    	    $id = intval($args[0]);
	    	    $land = $this->getPlugin()->landManager->getLand($id);
	    	    if(!($land instanceof Land)){
					$sender->sendMessage("land-notexist");
					break;
	    	    }
	    	    if(trim($newOwner = mb_strtolower($args[1])) === ""){
					$sender->sendMessage("land-invaliduser");
					break;
				}
	    	    if($land->owner === $newOwner){
					$sender->sendMessage("username-other", $sender);
	    	    	break;
	    	    }
	    	    $land->owner = mb_strtolower($newOwner);
	    	    $owner = $this->getPlugin()->getServer()->getPlayerExact($newOwner);
	    	    if($owner !== null){
					$owner->sendMessage("land-transfer-owner", $land->id);
	    	    }
				$sender->sendMessage("land-transfer", $land->id, $newOwner);
	    	    break;
	    	case "sell":
			    if(!$sender->isOp()){
					$sender->sendMessage("onlyop");
					break;
				}
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!($land instanceof Land)){
					$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    if($land->owner === ""){
					$sender->sendMessage("land-sell-failed", $sender, $land->id);
					break;
				}
	    	    $land->owner = "";
	    	    $land->lastPayment = -1;
				$sender->sendMessage("land-sell");
	    	    break;
	    	case "auto":
	    	    $lands = $this->getPlugin()->landManager->getAll();
	    	    foreach($lands as $land){
	    	    	if(!$land->isOwned() && (count($lands) === 1 ? true : ($land->id !== ($this->lastLookup[$sender->getName()] ?? -1)))){
	    	    		$sender->teleport(Position::fromObject($land->getCenter(), $this->getPlugin()->getServer()->getLevelByName($land->world)));
	    	    		$this->lastLookup[$sender->getName()] = $land->id;
	    	    		$sender->sendMessage("land-auto", $land->id);
	    	    		break 2;
	    	    	}
	    	    }
	    	    $sender->sendMessage("land-auto-failed");
	    	    break;
	    	case "list":
	    	    foreach($this->getPlugin()->landManager->getAll() as $key => $l){
	    	    	if($l->isHelper(mb_strtolower($sender->getName()))){
	    	    		if($l->owner === mb_strtolower($sender->getName())){
	    	    			if(time() - $l->lastPayment >= self::A_WEEK){
	    	    				$due = "(due)";
	    	    			}else{
	    	    				$due = "(due in " . (ceil((self::A_WEEK - (time() - $l->lastPayment)) / self::A_DAY)) . " hours)";
	    	    			}
	    	    			$land[] = ["owner " . $due, $l->id];
	    	    		}else{
	    	    			$land[] = ["helper", $l->id];
	    	    		}
	    	    	}
	    	    }
	    	    if(!isset($land)){
	    	    	$sender->sendMessage("land-list-none");
	    	    	break;
	    	    }
	    	    $list = "";
	    	    foreach($land as $l){
	    	    	$list .= TextFormat::WHITE . "#" . $l[1] . " as " . $l[0] . TextFormat::AQUA . ($l === end($land) ? "" : ", ");
	    	    }
				$sender->sendMessage("land-list", $sender, count($land), $list);
	    	    break;
	    	case "sethome":
	    	    if(!$land = $this->getPlugin()->landManager->getLand($args[0] ?? -1)){
	    	    	$land2 = $this->getPlugin()->landManager->getLand2($sender);
	    	    	if(!($land2 instanceof Land)){
	    	    		$sender->sendMessage("land-notexist");
	    	    		break;
	    	    	}
	    	    	$land = $land2;
	    	    }
	    	    if($land->isHelper(mb_strtolower($sender->getName()))){
	    	    	$this->getPlugin()->landManager->homeland->set(mb_strtolower($sender->getName()), $land->id);
	    	    	$this->getPlugin()->landManager->homeland->save();
					$sender->sendMessage("land-sethome", $land->id);
	    	    }else{
					$sender->sendMessage("land-notinvitee", $land->owner);
	    	    }
	    	    break;
	    	case "home":
	    	    foreach($this->getPlugin()->landManager->homeland->getAll() as $player => $home){
	    	    	if($player === mb_strtolower($sender->getName()) && (!$land = $this->getPlugin()->landManager->getLand($home) || !$land->isHelper(mb_strtolower($sender->getName())))){
	    	    		$this->getPlugin()->landManager->homeland->remove($player);
	    	    		$this->getPlugin()->landManager->homeland->save();
	    	    		$sender->sendMessage("land-notexist");
	    	    		break 2;
	    	    	}
	    	    }
	    	    if(!$this->getPlugin()->landManager->homeland->exists(mb_strtolower($sender->getName()))){
	    	    	$sender->sendMessage("land-home-failed");
	    	    	break;
	    	    }
	    	    $home = $this->getPlugin()->landManager->homeland->get(mb_strtolower($sender->getName()));
	    	    $land = $this->getPlugin()->landManager->getLand($home);
	    	    $sender->teleport(Position::fromObject($land->getCenter(), $this->getPlugin()->getServer()->getLevelByName($land->world)));
	    	    $sender->sendMessage("land-home");
	    	    break;
	    	case "deny":
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!($land instanceof Land)){
	    	    	$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    if($land->owner !== mb_strtolower($sender->getName())){
	    	    	$sender->sendMessage("land-notowner");
	    	    	break;
	    	    }
	    	    $player = mb_strtolower($args[1]);
	    	    if(!Player::isValidUsername($player)){
	    	    	$sender->sendMessage("land-invaliduser");
	    	    	break;
	    	    }
	    	    if(!$this->getPlugin()->getServer()->getOfflinePlayer($player)->hasPlayedBefore()){
	    	    	$sender->sendMessage("player-notfound");
	    	    	break;
	    	    }
	    	    $toggle = $land->toggleDeny($player);
	    	    if($toggle){
					$sender->sendMessage("land-deny-added", $player, $land->id);
	    	    }else{
	    	    	$sender->sendMessage("land-deny-removed", $player, $land->id);
	    	    }
	    	    break;
	    	case "info":
	    	    $land = $this->getPlugin()->landManager->getLand($args[0] ?? -1);
	    	    $here = $this->getPlugin()->landManager->getLand2($sender);
	    	    if(!($land instanceof Land) && !$here){
	    	    	$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    if(!$land && $here){
	    	    	$land = $here;
	    	    }
	    	    	
	    	    $msg[] = LangManager::translate("land-info", $sender, $land->id);
	    	    if(!$land->isOwned()){
	    	    	$msg[] = LangManager::translate("land-info-2", $sender, "Not owned", number_format($land->price) . "\$");
	    	    }else{
	    	    	$msg[] = LangManager::translate("land-info-2", $sender, "Owned", $land->owner);
	    	    	foreach(["helpers", "denied"] as $info){
	    	    		if(!empty($$info = $land->$info)){
	    	    			$msg[] = LangManager::translate("land-info-2", $sender, ucfirst($info), count($$info) . " (" . implode(", ", $$info) . ")");
	    	    		}
	    	    	}
	    	    }
	    	    $msg[] = LangManager::translate("land-info-2", $sender, "Size", $land->getSize() . "m^2");
				$sender->sendMessage(implode("\n", $msg));
	    	    break;
	    	case "tp":
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!$land instanceof Land){
	    	    	$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    
    	    	if(!$land->isHelper($sender->getName()) && !$sender->isOp()){
    	    		$sender->sendMessage("land-notinvitee", $sender);
    	    		break;
    	    	}
    	    	$sender->teleport(Position::fromObject($land->getCenter(), $this->getPlugin()->getServer()->getLevelByName($land->world)));
    	    	$sender->sendMessage("land-tp", $land->id);
    	    	break;
	    	case "addhelper":
	            $helper = mb_strtolower($args[1]);
	            $land = $this->getPlugin()->landManager->getLand($args[0]);
	            
	    	    if(!($land instanceof Land)){
	    	    	$sender->sendMessage("land-notfound");
	    	    	break;
	    	    }
	    	    if($land->owner !== mb_strtolower($sender->getName())){
	    	    	$sender->sendMessage("land-notowner");
	    	    	break;
	    	    }
	    	    if(!Player::isValidUsername($helper)){
	    	    	$sender->sendMessage("land-invaliduser");
	    	    	break;
	    	    }
	    	    if(!$this->getPlugin()->getServer()->getOfflinePlayer($helper)->hasPlayedBefore()){
	    	    	$sender->sendMessage("player-notfound");
	    	    	break;
	    	    }
	    	    if($land->isHelper($helper)){
					$sender->sendMessage("land-addhelper-failed", $helper, $land->id);
	    	    	break;
	    	    }
	    	    $land->addHelper($helper);
	    	    $pl = $this->getPlugin()->getServer()->getPlayerExact($helper);
	    	    if($pl !== null){
					$pl->sendMessage("land-addhelper-helper", $land->id);
	    	    }
				$sender->sendMessage("land-addhelper", $helper, $land->id);
	    	    break;
	    	case "rmhelper":
	    	    $helper = mb_strtolower($args[1]);
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!($land instanceof Land)){
					$sender->sendMessage("land-notexist");
    	    	    break;
    	    	}
    	    	if($land->owner !== mb_strtolower($sender->getName())){
    	    		$sender->sendMessage("land-notowner");
    	    		break;
    	    	}
    	    	
    	    	$ret = $land->removeHelper($helper);
    	    	if(!$ret){
					$sender->sendMessage("land-rmhelper-failed", $helper, $land->id);
    	    	}else{
					$sender->sendMessage("land-rmhelper", $helper, $land->id);
    	    	}
    	    	break;
	    	case "pay":
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!($land instanceof Land)){
	    	    	$sender->sendMessage("land-notexist");
	    	    	break;
	    	    }
	    	    if($land->owner !== mb_strtolower($sender->getName())){
	    	    	$sender->sendMessage("land-notowner");
	    	    	break;
	    	    }
	    	    $week = 3600 * 24 * 7;
	    	    $expiry = $this->getPlugin()->getConfig()->getNested("land.due-deadline") * 3600;
	    	    if(time() - $land->lastPayment >= $week){
	    	    	if(Main::getInstance()->reduceMoney($land->owner, $land->price)){
	    	        	$land->lastPayment += $week;
						$sender->sendMessage("land-pay", $land->id);
	    	        	break;
	    	        }
					$sender->sendMessage("money-needed", number_format($land->price));
	    	        break;
	    	    }
				$sender->sendMessage("land-pay-notdue", ceil(($week - (time() - $land->lastPayment)) / 3600));
	    	    break;
	    	case "dispose":
	    	    $land = $this->getPlugin()->landManager->getLand($args[0]);
	    	    if(!($land instanceof Land)){
					$sender->sendMessage("land-notexist", $sender);
					break;
				}
    	    	if($land->owner !== mb_strtolower($sender->getName())){
    	    		$sender->sendMessage("land-notowner");
    	    		break;
    	    	}
    	    	$this->getPlugin()->landManager->disposeLand($land);
    	    	$sender->sendMessage("land-dispose", $land->id);
    	    	break;
    		case "help":
    	    	$msg[] = LangManager::translate("land-help", $sender);
            	foreach($subcommands as $subcommand => $usage){
            	    $msg[] = LangManager::translate("land-help-subcmd", $sender, $subcommand, (empty($usage[0]) ? "" : (" " . $usage[0])), $usage[1]);
				}
				$sender->sendMessage(implode("\n", $msg));
				break;
		}
		return true;
	}
	
}