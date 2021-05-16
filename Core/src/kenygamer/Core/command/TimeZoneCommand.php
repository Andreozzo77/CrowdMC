<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\CustomForm;

class TimezoneCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"timezone",
			"Change your timezone",
			"/timezone",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, LangManager::getInstance()->getCountryCode($sender->getAddress())); //Or \DateTimeZone::ALL + second argument null for global timezones
		$form = new CustomForm(function(Player $player, ?array $data) use($timezones){
			if($player->isOnline() && $data !== null){
				$timezone = $data[1];
				$this->getPlugin()->registerEntry($player, Main::ENTRY_TIMEZONE, $timezone);
				$sender->sendMessage(TextFormat::colorize("&aTimezone changed to &b" . $timezone . "&a!"));
			}
		});
		$form->setTitle(TextFormat::colorize("&l&fChange your timezone"));
		$form->addLabel(TextFormat::colorize("&7Change your timezone for things that require your time zone, like season reset."));
		$form->addDropdown("Time Zone", $timezones, is_string($timezone = $this->getPlugin()->getEntry($sender, Main::ENTRY_TIMEZONE)) ? array_search($timezone, $timezones) : null);
		$sender->sendForm($form);
		return true;
	}
}