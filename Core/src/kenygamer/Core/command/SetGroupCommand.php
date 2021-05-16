<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Group;
use kenygamer\Core\Main;
use pocketmine\Player;

class SetGroupCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"setgroup",
			"Sets group for the player.",
			"/setgroup <player> <group> [needGroup]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		
		$secretKey = array_shift($args);
		if($secretKey !== Main::SECRET_KEY){
			array_unshift($args, $secretKey);
			$secretKey = null;
		}
		$manager = $this->getPlugin()->permissionManager;
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		
		$groupWanted = $args[1];
		$groupWantedObj = $manager->getGroup($groupWanted);
		if(!$groupWantedObj){
			$sender->sendMessage("group-notexist", $groupWanted);
			return true;
		}
		
		$playerGroup = $staffGroup = null;
		$groups = $manager->getPlayerGroups($player);
		
		if(!in_array($groupWanted, $this->getPlugin()->ranks)){
			$sender->sendMessage("group-notlisted", $groupWanted);
			return true;
		}
		
		$needGroup = $args[2] ?? "";
		if($needGroup !== ""){
			$hasNeededGroup = false;
		}else{
			$hasNeededGroup = true;
		}
		
		foreach($groups as $group){
			if(($position = $this->getPlugin()->rankCompare($group->getName(), Main::STAFF_RANK)) < 0){
				if(!is_string($playerGroup)){
					$playerGroup = $group;
				}
			}elseif($position >= 0){
				if(!is_string($staffGroup)){
					$staffGroup = $group;
				}
			}
			
			if($group->getName() === $groupWanted){
				$sender->sendMessage("setgroup-error", $player->getName(), $groupWanted);
				return true;
			}
			if($group->getName() === $needGroup){
				$hasNeededGroup = true;
			}
		}
		
		if(!$hasNeededGroup){
			$sender->sendMessage("group-notingroup", $player->getName());
			return true;
		}
		
		if(!in_array($groupWantedObj->getName(), ["Member", "JuniorYT", "YouTuber", "Trial", "Moderator", "Admin", "HeadAdmin", "Owner"])){
			if($secretKey === null){
				return false;
			}
		}
		$isStaffGroup = $this->getPlugin()->rankCompare($groupWantedObj->getName(), Main::STAFF_RANK) >= 0;
		if($isStaffGroup){
			if($staffGroup instanceof Group){
				$manager->removePlayerFromGroup($player, $staffGroup);
			}
		}else{
			if($playerGroup instanceof Group){
				$manager->removePlayerFromGroup($player, $playerGroup);
			}
		}
		$manager->addPlayerToGroup($player, $groupWantedObj);
		$sender->sendMessage("setgroup-success", $player->getName());
		return true;
	}
	
}