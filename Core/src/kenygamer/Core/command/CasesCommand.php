<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\LangManager;

class CasesCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"cases",
			"View case list",
			"/cases",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$form = new SimpleForm(function(Player $player, ?string $case){
	        if($case !== null){
	            $this->getPlugin()->getServer()->dispatchCommand($player, "case " . $case);
	        }
	    });
	    $form->setTitle(LangManager::translate("cases-title", $sender));
	    $form->setContent(LangManager::translate("cases-desc", $sender));
		$cases = $this->getPlugin()->cases->getAll();
		if(empty($cases)){
			$sender->sendMessage("cases-none");
			return true;
		}
	    foreach($cases as $id => $case){
	        $openedSince = $this->getPlugin()->formatTime($this->getPlugin()->getTimeEllapsed($case["timeOpened"]));
	        $form->addButton(LangManager::translate("cases-case", $sender, $id, $case["player"], $openedSince), -1, "", (string) $id);
	    }
	    $sender->sendForm($form);
		return true;
	}
	
}