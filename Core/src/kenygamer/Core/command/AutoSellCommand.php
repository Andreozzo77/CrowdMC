<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use LegacyCore\Commands\Sell;

class AutoSellCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"autosell",
			"Manage your auto sell settings",
			"/autosell <add/remove/list/toggle>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $autosell = $this->getPlugin()->autosell->getNested($sender->getName() . ".items", []);
	    switch(mb_strtolower($action = $args[0])){
	    	case "add":
	    	    $item = $args[1];
	    	    if(strpos($item, ":") === false){
	    	    	$item .= ":0";
	    	    }
	    	    if(!isset(Sell::$market[$item])){
	    	    	$sender->sendMessage("autosell-notsellable");
	    	    	break;
	    	    }
	    	    if(in_array($item, $autosell)){
	    	    	$sender->sendMessage("autosell-iteminlist");
	    	    	break;
	    	    }
	    	    array_push($autosell, $item);
	    	    $this->getPlugin()->autosell->setNested($sender->getName() . ".items", $autosell);
	    	    $sender->sendMessage("autosell-added", $item);
	    	    break;
	    	case "remove":
	    	    $item = $args[1];
	    	    if(strpos($item, ":") === false){
	    	    	$item .= ":0";
	    	    }
	    	    if(!in_array($item, $autosell)){
					$sender->sendMessage("autosell-itemnotlisted");
	    	    	break;
	    	    }
	    	    unset($autosell[array_search($item, $autosell)]);
	    	    $this->getPlugin()->autosell->setNested($sender->getName() . ".items", $autosell);
	    	    $sender->sendMessage("autosell-removed", $item);
	    	    break;
	    	case "list":
	    	    if(empty($autosell)){
	    	    	$sender->sendMessage("autosell-list-none");
	    	    	break;
	    	    }
				$sender->sendMessage("autosell-list", implode(", ", $autosell));
	    	    break;
	    	case "toggle":
	    	    $enabled = $this->getPlugin()->autosell->getNested($sender->getName() . ".enable", false);
	    	    if(!$enabled){
	    	    	$this->getPlugin()->autosell->setNested($sender->getName() . ".enable", true);
	    	    	$new = "on";
	    	    }else{
	    	    	$this->getPlugin()->autosell->setNested($sender->getName() . ".enable", false);
	    	    	$new = "off";
	    	    }
				$sender->sendMessage("autosell-toggled", $new);
	    	    break;
		}
		return true;
	}
	
}