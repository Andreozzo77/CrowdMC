<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\task\ReadFileTask;
use kenygamer\Core\LangManager;
use pocketmine\Player;

class ReadFileCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"readfile",
			"Read a file",
			"/readfile",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset($this->getPlugin()->readingFile[$sender->getName()])){
	    	$this->getPlugin()->getScheduler()->cancelTask($this->getPlugin()->readingFile[$sender->getName()]->getTaskId());
	    	$sender->sendMessage("exitted");
	    	return true;
	    }
	    $form = new CustomForm(function(Player $player, ?array $data){
	    	if(is_array($data)){
	    		list($path, $mode, $truncate, $stripLen) = $data;
	    		$truncate = (bool) $truncate;
	    		$stripLen = (int) $stripLen;
	    		$path = realpath($this->getPlugin()->getServer()->getDataPath() . $path);
	    		if($path === false || !is_file($path)){
					$player->sendMessage("readfile-invalidfile");
	    			return;
	    		}
	    		$info = pathinfo($path);
	    		$extensions = ["txt", "log"];
	    		if(!isset($info["extension"]) || !in_array($info["extension"], $extensions)){
					$player->sendMessage("readfile-bannedext", implode(", ", $extensions));
	    			return;
	    		}
	    		if(($pos = strpos($path, ".")) !== false && $pos !== (strlen($path) - strlen($info["extension"]) - 1)){
	    			$player->sendMessage("readfile-relative");
	    			return;
	    		}
	    		$this->getPlugin()->readingFile[$player->getName()] = $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ReadFileTask($player, $path, $mode, $truncate, $stripLen), 60);
	    	}
	    });
	    $form->setTitle(LangManager::translate("readfile-title", $sender));
	    $form->addInput(LangManager::translate("readfile-path", $sender));
	    $form->addDropdown(LangManager::translate("readfile-mode", $sender), ["Live", "View contain"]);
	    $form->addToggle(LangManager::translate("readfile-truncate", $sender));
	    $form->addInput(LangManager::translate("readfile-striplen", $sender));
	    $sender->sendForm($form);
		return true;
	}
	
}