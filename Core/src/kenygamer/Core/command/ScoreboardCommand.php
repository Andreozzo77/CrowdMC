<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

final class ScoreboardCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"scoreboard",
			"Change your scoreboard settings",
			"/scoreboard",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$form = new CustomForm(function(Player $player, ?array $data){
			if(!$player->isOnline()){
				return;
			}
			if($data === null){
				$player->chat("/settings");
				return;
			}
			$value = $data[0] ?? null;
			try{
				$this->getPlugin()->setSetting($player, Main::SETTING_SCOREBOARD, $value);
			}catch(\Throwable $e){
				$this->getPlugin()->getLogger()->error($e->getMessage());
			}
			$player->chat("/" . $this->getName());
		});
		$form->setTitle(LangManager::translate("settings-scoreboard-title", $sender));
		$form->addDropdown(LangManager::translate("settings-scoreboard-desc", $sender), [
			LangManager::translate("settings-scoreboard-none", $sender),
			LangManager::translate("settings-scoreboard-old", $sender),
			LangManager::translate("settings-scoreboard-regular", $sender),
			LangManager::translate("settings-scoreboard-faction", $sender)
		], $this->getPlugin()->getSetting($sender, Main::SETTING_SCOREBOARD));
		$sender->sendForm($form);
		return true;
	}
		
}