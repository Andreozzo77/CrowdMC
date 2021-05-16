<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;

class ResyncCommand extends BaseCommand{
	/** @var int[] */
	private $cooldown = [];
	
	public function __construct(){
		parent::__construct(
			"resync",
			"Resync your faction/rank with Discord",
			"/resync [faction/rank]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		echo "beep";
		if(isset($this->cooldown[$sender->getName()]) && time() - $this->cooldown[$sender->getName()] < $this->getPlugin()->getConfig()->get("resync-cooldown")){
			$sender->sendMessage("in-cooldown");
			return true;
		}
		$this->cooldown[$sender->getName()] = time();
		$what = array_shift($args);
		switch($what){
			case "rank":
				$this->getPlugin()->getPlugin("FactionsPro")->factionInfoUpdate($sender->getName());
				break;
			case "faction":
				$this->getPlugin()->updateDiscordEntry($sender->getName(), "3", $this->getPlugin()->permissionManager->getPlayerGroup($sender)->getName());
				break;
			default:
				$this->getPlugin()->getPlugin("FactionsPro")->factionInfoUpdate($sender->getName());
				$this->getPlugin()->updateDiscordEntry($sender->getName(), "3", $this->getPlugin()->permissionManager->getPlayerGroup($sender)->getName());
		}
		return true;
	}
}