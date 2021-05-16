<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;

class HelpMeCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"helpme",
			"View a lot of helpful info",
			"/helpme [page]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$registeredCommands = $this->getPlugin()->getServer()->getCommandMap()->getCommands();
		$commands = array_keys($this->getPlugin()->getConfig()->get("commands", []));
	    
	    $commandList = [];
	    foreach($commands as $command){
	    	foreach($registeredCommands as $cmd){
	    		if(($cmd->getName() === $command xor in_array($command, $cmd->getAliases())) && $cmd->testPermissionSilent($sender)){
	    			$commandList[$cmd->getName()] = $cmd->getDescription();
	    			break;
	    		}
	    	}
	    }
	    ksort($commandList, SORT_NATURAL | SORT_FLAG_CASE);
		
		$showContent = [];
		if(!($sender instanceof Player)){
	    	$wrap = wordwrap(LangManager::translate("helpme-page1-content", $sender), 60, "\n");
	    	foreach(explode("\n", $wrap) as $line){
	    		$showContent[] = $line;
	    	}
		}
	    foreach($commandList as $cmd => $desc){
	    	$showContent[] = LangManager::translate("helpme-command", $sender, $cmd, $desc);
		}
	    $page = isset($args[0]) ? max(1, intval($args[0])) : 1;
		
	    $start = ($page - 1) * 5;
	    $totalPages = ceil(count($showContent) / 5);
	    $showContent = array_slice($showContent, $start, 5);
	    
		if($page === 1 && $sender instanceof Player){
			$form = new SimpleForm(function(Player $player, ?int $continue){
				if($continue !== null && $player->isOnline()){
					$player->chat("/helpme 2");
				}
			});
			$form->setTitle(LangManager::translate("season-name", $sender));
			$form->setContent(LangManager::translate("helpme-page", $sender, $page, $totalPages) . "\n" . LangManager::translate("helpme-page1-content", $sender) . "\n" . ($page < $totalPages ? LangManager::translate("helpme-footer", $sender, $page + 1) : ""));
			$form->addButton(LangManager::translate("continue", $sender));
			$sender->sendForm($form);
			return true;
		}
		
	    if(empty($showContent)){
	    	$msg[] = LangManager::translate("helpme-pagenotfound", $sender, $page);
	    }else{
	    	$msg[] = LangManager::translate("helpme-page", $sender, $page, $totalPages);
	    	foreach($showContent as $line){
	    		$msg[] = $line;
	    	}
	    	if($page < $totalPages){
	    		$msg[] = LangManager::translate("helpme-footer", $sender, $page + 1);
	    	}
	    }
		$sender->sendMessage(implode("\n", $msg));
		return true;
	}
	
}