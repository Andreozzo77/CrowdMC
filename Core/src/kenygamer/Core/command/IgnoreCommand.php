<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use kenygamer\Core\LangManager;

class IgnoreCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"ignore",
			"Manage your ignore list",
			"/ignore <add/remove/list>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$ignore = $this->getPlugin()->ignore->get($sender->getName(), []);
		switch($subcmd = $args[0]){
	        case "add":
	            $player = $args[1];
	            $p = $this->getPlugin()->getServer()->getPlayer($player);
	            if($p === null){
	            	$sender->sendMessage("player-notfound");
	            	return true;
	            }
	            if(!in_array(mb_strtolower($p->getName()), $ignore)){
	            	$ignore[] = mb_strtolower($p->getName());
	            	$this->getPlugin()->ignore->set($sender->getName(), $ignore);
	            	$sender->sendMessage("ignore-added", $p->getName());
	            	return true;
	            }
	            $sender->sendMessage("ignore-addedalready", $p->getName());
	            break;
	        case "remove":
	            $player = mb_strtolower($args[1]);
	            if(!in_array($player, $ignore)){
	            	$sender->sendMessage("ignore-removenotfound", $player);
	            	break;
	            }
	            unset($ignore[array_search($player, $ignore)]);
	            $this->getPlugin()->ignore->set($sender->getName(), $ignore);
	            $sender->sendMessage("ignore-removed", $player);
	            break;
	        case "list":
	            if(empty($ignore)){
	               $sender->sendMessage("ignore-none");
	               break;
	            }
	            $sender->sendMessage("ignore-list", implode(", ", $ignore));
	            break;
	    }
	    return true;
	}
	
}