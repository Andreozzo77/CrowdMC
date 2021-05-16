<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;

class SetUPermCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"setuperm",
			"Adds a permission to the player",
			"/setuperm <player> <permission>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$manager = Main::getInstance()->permissionManager;
		$player = Main::getInstance()->getServer()->getOfflinePlayer($args[0]);
		$permission = $args[1];
		if($manager->addPlayerPermission($player, $permission)){
			$sender->sendMessage("setuperm-success", $permission, $player->getName());
			return true;
		}
		$sender->sendMessage("setuperm-error", $player->getName(), $permission);
		return true;
	}
	
}