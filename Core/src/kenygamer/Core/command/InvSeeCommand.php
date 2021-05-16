<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use muqsit\invmenu\InvMenu;
use kenygamer\Core\LangManager;

class InvSeeCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"invsee",
			"View a player's inventory",
			"/invsee <player>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $clear = $sender->hasPermission("playertrades.invsee.clear");
        $menu->setListener(InvMenu::readonly());
        $menu->setName(LangManager::translate("invsee-title", $sender, $player->getName()));
        $menu->getInventory()->setContents(($before = $player->getInventory())->getContents());
        $menu->send($sender);
        return true;
	}
	
}