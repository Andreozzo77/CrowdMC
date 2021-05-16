<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;

class GmspcCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"gmspc",
			"Change your gamemode",
			"/gmspc",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		switch($sender->getGamemode()){
            case Player::SPECTATOR:
                $sender->sendMessage("gmspc-off");
                $sender->setGamemode(Player::SURVIVAL);
                break;
			default:
                $sender->sendMessage("gmspc-on");
				$sender->setGamemode(Player::SPECTATOR);
                break;
        }
        return true;
    }
	
}