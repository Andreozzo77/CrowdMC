<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\permission\PermissionManager;

class SetSuffixCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"setsuffix",
			"Changes the suffix of a player",
			"/setsuffix <player> <suffix>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		$suffix = $args[1];
		$change¥ = $this->getPlugin()->permissionManager->setPlayerSuffix($player, $suffix);
		$sender->sendMessage("setsuffix-success", $player, $suffix);
		return true;
	}
	
}