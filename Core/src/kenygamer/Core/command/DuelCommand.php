<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\duel\DuelArena;
use kenygamer\Core\LangManager;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use kenygamer\Core\Main2;

class DuelCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"duel",
			"Buy an EXP boost for money",
			"/duel",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(Main2::getBedWarsManager()->dequeuePlayer($sender)){
			$sender->sendMessage("bedwars-quit");
		}
		if(isset($args[0])){
	    	$playerName = mb_strtolower($args[0]);
	    	$player = $this->getPlugin()->getServer()->getPlayer($playerName);
	    	if(!$player instanceof Player){
	    		$sender->sendMessage("duel-offline", $playerName);
	    		return true;
	    	}
	    	foreach($this->getPlugin()->duelRequests as $requester => $data){
	    		list($receiver, $duelType) = $data;
	    		if($receiver === $sender->getName() && $requester === $player->getName()){
	    			unset($this->getPlugin()->duelRequests[$requester]);
	    			$arena = $this->getPlugin()->findFreeArena($duelType);
	    			if($arena === null){
	    				foreach([$sender, $player] as $recipient){
	    					$recipient->sendMessage("duel-noarenas");
	    				}
	    				return true;
	    			}
	    			$sender->sendMessage("duel-request-accept");
	    			$duelName = $this->getPlugin()->getDuelName($duelType);
	    			foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $p){
	    				if($p->getName() !== $sender->getName() && $p->getName() !== $player->getName()){
							$p->sendMessage("duel-rematch", $duelName, $player->getName(), $sender->getName());
	    				}
	    			}
	    			//TODO: Rewrite starting a duel
	    		    switch($duelType){
	    		    	case Main::DUEL_TYPE_VANILLA:
	    		    	case Main::DUEL_TYPE_CUSTOM:
	    		    	case Main::DUEL_TYPE_SPLEEF:
	    		    	case Main::DUEL_TYPE_TNTRUN:
	    		    	    $arena->startDuel($this->getPlugin()->duelKits[$duelType], $duelType, true, $sender, $player);
	    		    	    break;
	    		    	default:
	    		    	    //$duelKit must be empty and $returnInventory false for a non Kit-PvP
	    		    	    $arena->startDuel([], $duelType, false, $sender, $player);
	    		    }
	    		    return true;
	    		}
	    	}
	    	if(!isset($args[1])){
	    		$sender->sendMessage("duel-rematch-noduel");
	    		return true;
	    	}
	    	try{
	    		$type = -1;
	    		switch($args[1]){ //known duels
	    		    case "n":
	    		    case "normal":
	    		        $type = Main::DUEL_TYPE_NORMAL;
	    		        break;
	    		    case "f":
	    		    case "friend":
	    		    case "friendly":
	    		        $type = Main::DUEL_TYPE_FRIENDLY;
	    		        break;
	    		    case "v":
	    		    case "vanilla":
	    		        $type = Main::DUEL_TYPE_VANILLA;
	    		        break;
	    		    case "c":
	    		    case "custom":
	    		        $type = Main::DUEL_TYPE_CUSTOM;
	    		        break;
	    		    case "s":
	    		    case "spleef":
	    		        $type = Main::DUEL_TYPE_SPLEEF;
	    		        break;
	    		    case "t":
	    		    case "tnt":
	    		    case "tntrun":
	    		        $type = Main::DUEL_TYPE_TNTRUN;
	    		        break;
	    		    default:
	    		        $type = intval($args[1]);
	    		}
	    		$duelName = $this->getPlugin()->getDuelName($type);
	    	}catch(\InvalidArgumentException $e){
	    	    $sender->sendMessage("duel-invalid");
	    	    return true;
	    	}
	    	$this->getPlugin()->duelRequests[$sender->getName()] = [$player->getName(), $type];
			$player->sendMessage("duel-request-recipient", $sender->getName(), $duelName);
			$sender->sendMessage("duel-request-accept", $player->getName());
	    	return true;
	    }
	    $form = new SimpleForm(function(Player $player, ?string $option){
    		switch($option){
    			case -3:
    			    $this->getPlugin()->quitQueue($player);
    			    break;
    			case -2:
    			    if($arena = $this->getPlugin()->getPlayerSpectating($player)){
    			    	$arena->removeSpectator($player);
    			    }
    	            break;
    	        case -1:
    	            $arenas = [];
    	            foreach($this->getPlugin()->duelArenas as $i => $arena){
    	            	if($arena->gameStatus !== DuelArena::GAME_STATUS_INACTIVE){
    	            		$players = $arena->getPlaying();
    	            		$arenas[$i] = [$arena->getName(), $players[0]->getName(), $players[1]->getName()];
    	            	}
    	            }
    	            if(empty($arenas)){
    	            	$player->sendMessage("duel-spectate-none");
    	            }else{
    	            	$form = new SimpleForm(function(Player $player, ?string $arena){
    	            		if($arena !== null){
    	            			if(isset($this->getPlugin()->duelArenas[$arena])){
    	            				$this->getPlugin()->duelArenas[$arena]->addSpectator($player);
    	            			}
    	            		}
    	            	});
    	            	$form->setTitle(LangManager::translate("duel-spectatetitle", $player));
    	            	$form->setContent(LangManager::translate("duel-spectatechoose", $player));
    	            	foreach($arenas as $i => $data){
    	            		$form->addButton(LangManager::translate("duel-arena", $player, $data[0], $data[1], $data[2]), 0, "textures/blocks/double_plant_grass_top", (string) $i);
    	            	}
    	            	$player->sendForm($form);
    	            }
    	            break;
    			default: //> -1
    			    if(isset($this->getPlugin()->duelInfo[$option])){
    			    	$info = $this->getPlugin()->duelInfo[$option];
    			    	$form = new ModalForm(function(Player $player, ?bool $join) use($option){
    			    		if(!$player->isOnline()) return;
    			    		
    			    		if($join){
    			    			$this->getPlugin()->quitQueue($player);
    			    			$this->getPlugin()->joinQueue($player, (int) $option);
    			    		}elseif($join !== null){
    			    			$player->chat("/duel");
    			    		}
    			    	});
    			    	$form->setTitle(LangManager::translate("duel-system", $player));
    			    	$form->setContent(LangManager::translate("duel-info", $player, $this->getPlugin()->getDuelName((int) $option), $info[0], $info[1] ?? ""));
    			    	$form->setButton1(LangManager::translate("duel-join", $player));
    			    	$form->setButton2(LangManager::translate("goback", $player));
    			    	$form->sendToPlayer($player);
    			    }
    		}
	    });
	    $form->setTitle(LangManager::translate("duel-system", $sender));
	    if($this->getPlugin()->inQueue($sender)){
	    	$form->setContent(LangManager::translate("duel-queued", $sender, "Duel"));
	    	$form->addButton(LangManager::translate("duel-leavequeue", $sender), 0, "textures/blocks/barrier", "-3");
	    }elseif($this->getPlugin()->getPlayerSpectating($sender)){
	    	$form->setContent(LangManager::translate("duel-spectating", $sender));
	    	$form->addButton(LangManager::translate("duel-quitsp", $sender), 0, "textures/blocks/barrier", "-2");
	    }else{
	    	
	    	$playingNormal = 0;
	    	$playingFriendly = 0;
	    	$playingVanilla = 0;
	    	$playingCustom = 0;
	    	$playingSpleef = 0;
	    	$playingTntrun = 0;
	    	foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player){
	    		$arena = $this->getPlugin()->getPlayerDuel($player);
	    		if($arena !== null){
	    			switch($arena->getDuelType()){
	    				case Main::DUEL_TYPE_NORMAL:
	    				    $playingNormal++;
	    				    break;
	    				case Main::DUEL_TYPE_FRIENDLY:
	    				    $playingFriendly++;
	    				    break;
	    				case Main::DUEL_TYPE_VANILLA:
	    				    $playingVanilla++;
	    				    break;
	    				case Main::DUEL_TYPE_CUSTOM:
	    				    $playingCustom++;
	    				    break;
	    				case Main::DUEL_TYPE_SPLEEF:
	    				    $playingSpleef++;
	    				    break;
	    				case Main::DUEL_TYPE_TNTRUN:
	    				    $playingTntrun++;
	    				    break;
	    			}
	    		}
	    	}
	    	if($playingNormal >= 2 || $playingFriendly >= 2 || $playingVanilla >= 2 || $playingCustom >= 2 || $playingSpleef >= 2 || $playingTntrun >= 2){
	    		$form->setContent(LangManager::translate("duel-selectsp", $sender));
	    		$form->addButton(LangManager::translate("duel-spectate", $sender), 0, "textures/items/ender_eye", "-1");
	        }else{
	    		$form->setContent(LangManager::translate("duel-select", $sender));
	    	}
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Normal Duel", $playingNormal, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_NORMAL])), 0, "textures/items/diamond_sword", (string) Main::DUEL_TYPE_NORMAL);
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Friendly Duel", $playingFriendly, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_FRIENDLY])), 0, "textures/items/diamond_sword", (string) Main::DUEL_TYPE_FRIENDLY);
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Vanilla Duel", $playingVanilla, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_VANILLA])), 0, "textures/items/apple_golden", (string) Main::DUEL_TYPE_VANILLA);
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Custom Duel", $playingCustom, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_CUSTOM])), 0, "textures/items/book_enchanted", (string) Main::DUEL_TYPE_CUSTOM);
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Spleef Duel", $playingSpleef, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_SPLEEF])), 0, "textures/items/diamond_shovel", (string) Main::DUEL_TYPE_SPLEEF);
	    	$form->addButton(LangManager::translate("duel-playing", $sender, "Tntrun Duel", $playingTntrun, count($this->getPlugin()->duelQueue[Main::DUEL_TYPE_TNTRUN])), 0, "textures/blocks/tnt_side", (string) Main::DUEL_TYPE_TNTRUN);
	    }
	    $sender->sendForm($form);
		return true;
	}
	
}