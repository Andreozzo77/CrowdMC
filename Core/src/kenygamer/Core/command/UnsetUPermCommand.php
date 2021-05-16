<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;

class UnsetUPermCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"unsetuperm",
			"Removes a permission from the player",
			"/unsetuperm <player> <permission>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$manager = Main::getInstance()->permissionManager;
		$player = Main::getInstance()->getServer()->getOfflinePlayer($args[0]);
		$permission = $args[1];
		if($manager->removePlayerPermission($player, $permission)){
			$sender->sendMessage("unsetuperm-success", $permission, $player->getName());
			return true;
		}
		$sender->sendMessage("unsetuperm-error", $player->getName(), $permission);
		return true;
	}
	
}