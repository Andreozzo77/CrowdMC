<?php

declare(strict_types=1);

namespace kenygamer\Core\command;


class WhitelistCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"wlist", //whitelist conflicts with defaults WhitelistCommand
			"Manages the list of players allowed to use this server",
			"/wlist",
			["wl"],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(count($args) < 1){
			$sender->sendMessage("cmd-usage", "/wlist <player>");
			return true;
		}
		$player = array_shift($args);
		if($this->getPlugin()->whitelist->exists($player)){
			$this->getPlugin()->whitelist->remove($player);
			$sender->sendMessage("whitelist-remove", $player);
		}else{
			$this->getPlugin()->whitelist->set($player, true);
			$sender->sendMessage("whitelist-add", $player);
		}
		return true;
	}
	
}