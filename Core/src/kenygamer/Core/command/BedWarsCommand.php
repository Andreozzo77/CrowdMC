<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;
use kenygamer\Core\bedwars\BedWarsManager;
use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;

class BedWarsCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"bedwars",
			"Enqueue to a BedWars game",
			"/bedwars",
			["bw"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$this->getPlugin()->quitQueue($sender);
		
		$action = array_shift($args);
	    $manager  = Main2::getBedWarsManager();
	    if($manager === null){
	    	return true;
	    }
	    if($action !== null && $action === "quit"){
	    	if($manager->removeSpectator($sender)){
	    	    return true;
	    	}elseif($manager->dequeuePlayer($sender)){
	    		$sender->sendMessage("bedwars-dequeued");
	    		return true;
	    	}
	    	$sender->sendMessage("bedwars-notqueued");
	    	return true;
	    }
	    if($manager->removeSpectator($sender)){ //Spectator can use commands
	    	return true;
	    }
	    $form = new SimpleForm(function(Player $player, ?string $data) use($manager){
	    	if(is_string($data)){
	    		if($manager && $manager->enqueuePlayer($player, (int) $data)){
	    			$player->sendMessage("bedwars-enqueued", BedWarsManager::getGameModeString((int) $data)); //string vs int comparison
	    		}else{
	    			$player->sendMessage("bedwars-queued");
	    		}
	    	}
	    });
	    $playingNormal = $playingCustom = 0;
	    foreach($manager->getArenas() as $arena){
	    	switch($arena->getGameMode()){
	    		case Main2::BEDWARS_MODE_NORMAL:
	    		    $playingNormal += count($arena->getPlayers());
	    		    break;
	    		case Main2::BEDWARS_MODE_CUSTOM:
	    		    $playingCustom += count($arena->getPlayers());
	    		    break;
	    	}
	    }
	    $queuedNormal = $queuedCustom = 0;
	    foreach($manager->getQueue() as $mode => $players){
	    	switch($mode){
	    		case Main2::BEDWARS_MODE_NORMAL:
	    		    $queuedNormal += count($players);
	    		    break;
	    		case Main2::BEDWARS_MODE_CUSTOM:
	    		    $queuedCustom += count($players);
	    		    break;
	    	}
	    }
	    $form->setTitle(LangManager::translate("bedwars"));
	    $form->setContent(LangManager::translate("bedwars-select-select"));
	    $form->addButton(LangManager::translate("bedwars-select-type", BedWarsManager::getGameModeString(Main2::BEDWARS_MODE_NORMAL), $playingNormal, $queuedNormal), 0, "textures/items/diamond_sword", (string) Main2::BEDWARS_MODE_NORMAL);
	    $form->addButton(LangManager::translate("bedwars-select-type", BedWarsManager::getGameModeString(Main2::BEDWARS_MODE_CUSTOM), $playingCustom, $queuedCustom), 0, "textures/items/book_enchanted", (string) Main2::BEDWARS_MODE_CUSTOM);
	    $sender->sendForm($form);
		return true;
	}
	
}