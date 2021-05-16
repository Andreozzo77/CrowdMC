<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;

class BanListCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"banlist",
			"View all players banned from this server",
			"/banlist <player/address>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!isset($args[0])) {
			return false;
		}
		if(count(explode(":", $args[0])) === 4){
			$sender->sendMessage(LangManager::translate("ban-ip-title", $sender, $args[0]));
		}else{
			$sender->sendMessage(LangManager::translate("ban-username-title", $sender, $player->getName()));
		}
		foreach($this->getServer()->getIPBans()->getEntries() as $entry){
			if($entry->getName() === $args[0]){
				$sender->sendMessage(LangManager::translate("ban-info", $entry->getCreated()->format(self::DATE_FORMAT), ($entry->getExpires() ? $entry->getExpires()->format(self::DATE_FORMAT) : "Forever"), $entry->getReason(), $entry->getSource());
			}
		}
		$$sender->sendMessage(LangManager::translate("ban-footer", $sender));
		return true;
	}

}