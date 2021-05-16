<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

class StaffCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"staff",
			"Send a message to staff",
			"/staff [msg]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!$this->getPlugin()->isStaff($sender)){
			$msg[] = LangManager::translate("staff-tag", $sender);
			foreach($this->getPlugin()->getConfig()->getNested("staff.list") as $player){
				$msg[] = LangManager::translate("staff-format", $player, LangManager::translate($this->getPlugin()->getServer()->getOfflinePlayer($player) instanceof Player ? "online" : "offline"));
			}
			$sender->sendMessage(implode(TextFormat::EOL, $msg));
			return true;
		}
		if(!isset($args[0])){
			$sender->sendMessage("cmd-usage", $this->getUsage());
			return false;
		}
		$recipients = $this->getPlugin()->getServer()->getOnlinePlayers();
		if($sender instanceof ConsoleCommandSender){
			$recipients[] = $sender;
		}
        foreach($recipients as $player){
            if($this->getPlugin()->isStaff($player) || $player instanceof ConsoleCommandSender){
                $player->sendMessage("staff-chat", $sender->getName(), implode(" ", $args));
            }
        }
        return true;
	}
	
}