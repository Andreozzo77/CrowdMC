<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class UnsetGroupCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"unsetgroup",
			"Removes player from the group.",
			"/unsetgroup <player> <group>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$manager = $this->getPlugin()->permissionManager;
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		try{
			$manager->removePlayerFromGroup($player, $args[1]);
		}catch(\InvalidArgumentException $e){
			$sender->sendMessage("group-notingroup", $player->getName());
			return true;
		}
		$sender->sendMessage("rmgroup-success", $player->getName());
		return true;
	}
	
}