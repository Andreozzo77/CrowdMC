<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;

class ListUPermsCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"listuperms",
			"Shows a list of all permissions from a player",
			"/listuperms <player> [page]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = $args[0];
		$permissions = $this->getPlugin()->permissionManager->getPlayerPermissions($player = $this->getPlugin()->getServer()->getOfflinePlayer($player));
		$page = $args[1] ?? 1;
		if(!count($permissions)){
			$sender->sendMessage("listuperms-none");
			return true;
		}
		$pages = ceil(count($permissions) / 5);
		$page = $page > $pages ? $pages : ($page < 1 ? 1 : $page); //Clamp
		$start = ($page - 1) * 5;
		$permissions = array_slice($permissions, $start, 5);
		$msg[] = LangManager::translate("listuperms-list", $sender, $player->getName(), $page, $pages);
		foreach($permissions as $permission){
			$msg[] = LangManager::translate("listuperms-permission", $sender, $permission);
		}
		$sender->sendMessage(implode("\n", $msg));
		return true;
	}
	
}