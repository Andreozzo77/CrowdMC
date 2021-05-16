<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\survey\Survey;
use kenygamer\Core\survey\SurveyManager;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\ModalForm;

class SurveyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"survey",
			"Vote on a survey that we are conducting",
			"/survey <stats/vote> <survey>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$survey = $this->getPlugin()->getSurveyManager()->getSurvey($args[1]);
		if(!($survey instanceof Survey)){
			$sender->sendMessage("survey-notfound");
			return true;
		}
		$formData = $survey->getFormData();
		$result = $survey->canVote($sender);
		switch($args[0]){
			case "vote":
			    if($result === Survey::CANVOTE_NOPERMISSION){
					$sender->sendMessage("survey-noperm");
			    	break;
			    }
			    if($result === Survey::CANVOTE_VOTED){
			    	$sender->sendMessage("survey-voted");
			    	break;
			    }
			    if($result === Survey::CANVOTE_EXPIRED){
			    	$sender->sendMessage("survey-expired");
			    	break;
			    }
			    $closure = function(Player $player, $data) use($survey){
			    	if(is_bool($data)){
			    		$survey->addVote($player, $data);
						$player->sendMessage("survey-vote", $survey->getName());
			    	}
			    };
			    $form = new ModalForm($closure);
			    $form->setTitle(TextFormat::colorize($formData["title"]));
			    $form->setContent(TextFormat::colorize($formData["content"]));
			    $form->setButton1(TextFormat::colorize($formData["button1"]));
			    $form->setButton2(TextFormat::colorize($formData["button2"]));
			    $sender->sendForm($form);
			    break;
			case "stats":
			    if($result === Survey::CANVOTE_NOPERMISSION){
			    	$sender->sendMessage("survey-noperm-stats");
			    	break;
			    }
			    if(!$survey->voteStatsEnabled()){
			    	$sender->sendMessage("survey-nostats");
			    	break;
			    }
			    $true = $survey->getVoteCount(true);
			    $false = $survey->getVoteCount(false);
			    $total = $true + $false;
			    if($total === 0){
			    	$sender->sendMessage("survey-novotes");
			    	break;
			    }
				$sender->sendMessage("survey-stats-head", $survey->getName());
				$sender->sendMessage("survey-stats-option", $formData["button1"], $true, number_format($true / $total * 100));
				$sender->sendMessage("survey-stats-option", $formData["button2"], $false, number_format($false / $total * 100));
			    $sender->sendMessage("survey-stats-overview", $total);
			    $msg .= LangManager::translate("survey-stats-option", $sender, $formData["button1"], $true, $true / $total * 100);
			    $msg .= LangManager::translate("survey-stats-option", $sender, $formData["button2"], $false, $false / $total * 100);
			    $msg .= LangManager::translate("survey-stats-overview", $sender, number_format($total));
				break;
		}
	    return true;
	}
	
}