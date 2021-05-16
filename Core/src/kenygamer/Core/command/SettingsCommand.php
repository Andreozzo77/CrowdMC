<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use LegacyCore\Tasks\ScoreHudTask;
use jojoe77777\FormAPI\SimpleForm;

final class SettingsCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"settings",
			"Change your settings",
			"/settings",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($player, array $args) : bool{
		$this->getPlugin()->getPlugin("BlockPets")->getDatabase()->getPlayerPets($player->getName(), null, function(array $rows) use($player){
			$isVisible = false;
			foreach($rows as [
				"PetName" => $petName,
				"Visible" => $isVisible
			]){
				break;
			}
			$settings = $this->getPlugin()->settings->get($player->getName(), []);
			$form = new SimpleForm(function(Player $player, ?string $data) use($settings){
				if($player->isOnline()){
					if($data !== null){
						$data = (int) round($data);
						$oldValue = $this->getPlugin()->getSetting($player, $data);
						switch($data){
							case Main::SETTING_CHUNKBORDERS:
							case Main::SETTING_HUD:
							case Main::SETTING_TIME:
							case Main::SETTING_COMPASS:
								if($data === Main::SETTING_COMPASS){
									if($oldValue){
										$this->getPlugin()->getPlayerBossBar($player)->removePlayer($player);
									}else{
										$this->getPlugin()->getPlayerBossBar($player)->addPlayer($player);
									}
								}
								$this->getPlugin()->setSetting($player, $data, !$oldValue);
								break;
							case 1000:
								$player->chat("/togglepet");
								break;
							case Main::SETTING_SCOREBOARD:
								$player->chat("/scoreboard");
								return;
								break;
							default:
						}
						$player->chat("/" . $this->getName());
					}
				}
			});
			$form->setTitle(LangManager::translate("settings-title", $player));
			$form->setContent(LangManager::translate("settings-desc", $player));
			
			$status = (count($this->getPlugin()->getPlugin("BlockPets")->getPetsFrom($player)) > 0) ? "on" : "off";
			$form->addButton(LangManager::translate("settings-pet", $player, LangManager::translate($status, $player)), 1, "https://static.wikia.nocookie.net/minecraft_gamepedia/images/4/46/Begging_Tame_Wolf.png/revision/latest?cb=20201021020322&format=original", (string) 1000);
			
			$status = $this->getPlugin()->getSetting($player, Main::SETTING_CHUNKBORDERS) ? "on" : "off";
			$form->addButton(LangManager::translate("settings-chunkborders", $player, LangManager::translate($status, $player)), 1, "https://static.wikia.nocookie.net/minecraftpe/images/e/ec/Chunk.png/revision/latest?cb=20201005184401&path-prefix=es", (string) Main::SETTING_CHUNKBORDERS);
			
			$status = $this->getPlugin()->getSetting($player, Main::SETTING_HUD) ? "on" : "off";
			$form->addButton(LangManager::translate("settings-hud", $player, LangManager::translate($status, $player)), 1,  "https://static.wikia.nocookie.net/minecraft_gamepedia/images/8/88/Iron_Ore_JE2_BE2.png/revision/latest?cb=20190512022834", (string) Main::SETTING_HUD);
			
			$status = $this->getPlugin()->getSetting($player, Main::SETTING_TIME) ? "on" : "off";
			$form->addButton(LangManager::translate("settings-time", $player, LangManager::translate($status, $player)), 1, "https://static.wikia.nocookie.net/minecraft_gamepedia/images/c/c2/Clock_JE2_BE2.gif/revision/latest?cb=20201201120202", (string) Main::SETTING_TIME);
			
			$status = $this->getPlugin()->getSetting($player, Main::SETTING_SCOREBOARD) > 0 ? "on" : "off";
			$form->addButton(LangManager::translate("settings-scoreboard", $player, LangManager::translate($status, $player)), 1,  "https://static.wikia.nocookie.net/minecraft_gamepedia/images/b/b9/Gold_Ore_JE3_BE2.png/revision/latest?cb=20200224211658&format=original", (string) Main::SETTING_SCOREBOARD);
			
			$status = $this->getPlugin()->getSetting($player, Main::SETTING_COMPASS) ? "on" : "off";
			$form->addButton(LangManager::translate("settings-compass", $player, LangManager::translate($status, $player)), 1,  "https://static.wikia.nocookie.net/minecraft_gamepedia/images/b/b5/Diamond_Ore_JE3_BE3.png/revision/latest?cb=20200309195154&format=original", (string) Main::SETTING_COMPASS);
			$player->sendForm($form);
			
		});
		return true;
	}
	
}