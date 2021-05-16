<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\koth\KothTask;
use kenygamer\Core\LangManager;
use pocketmine\Player;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class KothCommand extends BaseCommand{
    public function __construct(){
    	parent::__construct(
    		"koth",
    		"KOTH Command",
    		"/koth [on/off]",
    		[],
    		BaseCommand::EXECUTOR_PLAYER,
    		"true"
    	);
    }

	protected function onExecute($sender, array $args) : bool{
		if(isset($args[0])){
	    	if(!$sender->isOp()){
	    		return false;
	    	}
	    	switch(mb_strtolower($args[0])){
	    		case "on":
	    		case "enable":
	    		    KothTask::getInstance()->setEnabled(true);
	    		    $sender->sendMessage("koth-enabled");
	    		    break;
	    		case "off":
	    		case "disable":
	    		    KothTask::getInstance()->setEnabled(false);
	    		    $sender->sendMessage("koth-disabled");
	    		    break;
	    		default:
	    		    return false;
	    	}
	    }
	    $fp = $this->getPlugin()->getPlugin("FactionsPro");
	    if(!$fp->isInFaction($sender->getName())){
	    	$sender->sendMessage("koth-cmd-no-faction");
	    	return true;
	    }
	    $fac = $fp->getPlayerFaction($sender->getName());
	    /** @var string[] */
	    $added = $this->getPlugin()->koth->get($fac, []);
	    $isLeader = $fp->isLeader($sender->getName());
	    if(KothTask::getInstance()->isEnabled()){
	    	if(KothTask::getInstance()->getStatus() === KothTask::GAME_STATUS_RUNNING){
	    		$sender->sendMessage("koth-cmd-running", $sender);
	    		return true;
	    	}
	    	if(!in_array($sender->getName(), $added) && !$isLeader){
	    		$sender->sendMessage("koth-cmd-notadded", $sender);
	    		return true;
	    	}
	    	$form = new SimpleForm(function(Player $player, ?int $opt){
	    		if(!is_int($opt)){
	    			return;
	    		}
	    	    if(KothTask::getInstance()->isPlaying($player)){
	    	    	if(KothTask::getInstance()->getStatus() !== KothTask::GAME_STATUS_RUNNING){
	    	    		KothTask::getInstance()->removePlayer($player);
	    	    	}else{
	    	    		$player->sendMessage("koth-cmd-running");
	    	    	}
	    	    }else{
	    	    	KothTask::getInstance()->addPlayer($player);
	    	    }
	    	});
	    	$allies = [];
	    	foreach(KothTask::getInstance()->getPlayers() as $player){
	    		if($fp->sameFaction($player->getName(), $sender->getName()) || ($fp->isInFaction($player->getName()) && $fp->areAllies($fp->getPlayerFaction($player->getName()), $fac))){
	    			$allies[] = $player->getName();
	    		}
	    	}
	    	$form->setTitle(LangManager::translate("koth", $sender) . " " . $fac);
	    	$added[] = $fp->getLeader($fac);
	    	$form->setContent(LangManager::translate("koth-cmd-content", $fp->getFactionPower($fac), implode(", ", $added), implode(", ", $allies)));
	    	if(KothTask::getInstance()->isPlaying($sender)){
	    		$form->addButton(LangManager::translate("koth-cmd-leave", $sender));
	    	}else{
	    		$form->addButton(LangManager::translate("koth-cmd-join", $sender));
	    	}
	    	$sender->sendForm($form);
	    	return true;
	    }
	    if(!$isLeader){
	    	LangManager::send("koth-cmd-onlyleader", $sender);
	    	return true;
	    }
	    /** @var string[] */
	    $members = $fp->getFactionPlayers($fac);
	    /** @var string */
	    $notadded = [];
	    foreach($members as $member){
	    	if(!in_array($member, $added)){
	    		$notadded[] = $member;
	    	}
	    }
	    $form = new CustomForm(function(Player $player, ?array $data) use($added, $notadded, $fp, $fac, $members){
	        if(is_array($data)){
	        	$koth = $this->getPlugin()->koth->get($fac, []);
	        	foreach($data as $key => $value){
	        		if(!is_string($key)){
	        			continue;
	        		}
	        		$member = $key;
	        		if(in_array($member, $notadded) && $value){
	        			$koth[] = $member;
	        			$player->sendMessage("koth-cmd-added", $fac, $member);
	        		}elseif(in_array($member, $added) && !$value){
	        		    unset($koth[array_search($member, $koth)]);
	        		    $player->sendMessage("koth-cmd-removed", $fac, $member);
	        		}
	        	}
	        	//Muck out ex members
	        	foreach($koth as $i => $member){
	        		if(!in_array($member, $members)){
	        			unset($koth[$i]);
	        		}
	        	}
	        	$this->getPlugin()->koth->set($fac, $koth);
	        }
	    });
	    $form->setTitle(LangManager::translate("koth", $sender));
	    $form->addLabel(LangManager::translate("koth-cmd-label1", $sender));
	    if(!(count($members) > 0)){
	    	$form->addLabel(LangManager::translate("koth-cmd-label2", $sender));
	    }else{
	    	$form->addLabel(LangManager::translate("koth-cmd-label3", $sender));
	    }
	    foreach($members as $member){
	    	if($member !== $sender->getName()){
	    		$form->addToggle($member, in_array($member, $added), $member);
	    	}
	    }
	    $sender->sendForm($form);
	    return true;
	}
	
}