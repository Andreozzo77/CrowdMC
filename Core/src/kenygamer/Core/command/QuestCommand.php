<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;

class QuestCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"quest",
			"View your progress in quests",
			"/quest",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$quests = $this->getPlugin()->questManager->getQuests();
	    $form = new SimpleForm(function(Player $player, ?int $quest) use($quests){
	    	if($quest !== null){
	    		$quest = $quests[$quest];
	    		$form = new ModalForm(function(Player $player, ?bool $data){
	    			if($data !== null){
	    				if($data){
	    					$player->chat("/quest");
	    				}
	    			}
	    		});
	    		$form->setTitle(LangManager::translate("quest-title", $player, $quest->getName())); 
	    		$form->setContent(LangManager::translate("quest-info", $player, $quest->getDescription(), $quest->getTokens(), $quest->getMoney()));
	    		$form->setButton1(LangManager::translate("goback", $player));
	    		$form->setButton2(LangManager::translate("exit", $player));
	    		$player->sendForm($form);
	    	}
	    });
	    $completed = 0;
	    foreach($quests as $quest){
	    	$progress = $quest->getProgress($sender);
	    	if($quest->isCompleted($sender)){
	    		$completed++;
	    		$form->addButton(LangManager::translate("quest-completed", $sender, $quest->getName()));
	    	}else{
	    		$form->addButton(LangManager::translate("quest-progress", $sender, $quest->getName(), round($quest->getProgress($sender))));
	    	}
	    }
	    $form->setTitle(LangManager::translate("quest-titleprogress", $sender, $completed, count($quests)));
	    $form->setContent(LangManager::translate("quest-desc", $sender));
	    $sender->sendForm($form);
		return true;
	}
	
}