<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Server;
use kenygamer\Core\Main;

class UnlinkCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"unlink",
			"Unlink your account with Discord",
			"/unlink <code>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$this->getPlugin()->getLinks(function(array $links) use($sender, $args){
			foreach($links as $code => $data){
				if(isset($data["xboxUser"]) && $data["xboxUser"] === $sender->getName()){
					$money = $this->getPlugin()->getConfig()->get("link-money");
					if(!$sender->reduceMoney($money)){
						$sender->sendMessage("money-needed", $sender, $money);
						return;
					}
					$cfg = $this->getPlugin()->getConfig()->get("links-api");
					$this->getPlugin()->unlinkTasks[$sender->getName()] = $this->getPlugin()->makeHttpGetRequest($cfg["url"], [
	                    "serverID" => $cfg["server-id"],
	                    "serverKey" => $cfg["server-key"],
	                    "action" => "unlink",
	                    "xboxUser" => urlencode($sender->getName())
	                ], 0, 1, true, 1, [self::class, "onUnlink", $sender->getName()]);
	                $sender->sendMessage("unlink-unlinking");
	                return;
	            }
	        }
	        $sender->sendMessage("unlink-notlinked");
		});
		return true;
	}
	
	/**
	 * @param string $player
	 */
	public static function onUnlink(string $player) : void{
		if(!isset(Main::getInstance()->unlinkTasks[$player])){
            return;
        }
        $result = json_decode(Main::getInstance()->getHttpGetResult(Main::getInstance()->unlinkTasks[$player])[0], true);
        if(is_array($result) && !empty($result)){
            $player = Server::getInstance()->getPlayerExact($player);
            if($player === null){
                return;
            }
            if($result[1] === "SUCCESS_NO_DATA"){
            	$player->sendMessage("unlink-unlink");
            }
        }
	}
	
}