<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Server;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class LinkCommand extends BaseCommand{
	public function __construct(){
		parent::__construct(
			"link",
			"Link your account with Discord",
			"/link <code>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$code = $args[0];
		$this->getPlugin()->getLinks(function(array $links) use($sender, $args, $code){
			if(!isset($links[$code])){
				$sender->sendMessage("link-expired");
				echo "[EXPIRED IN CACHE]\n";
				return;
			}
			if(isset($this->getPlugin()->linkTasks[$sender->getName()])){ //Rollback fix
				$sender->sendMessage("in-cooldown");
				return;
			}
			switch($links[$code][4] ?? null){
	        	case "EXPIRED":
					$sender->sendMessage("link-expired");
					echo "[CODE EXPIRED]" . PHP_EOL;
	                break;
	            case "PENDING":
					echo "[LINK PENDING]" . PHP_EOL;
	                $sender->sendMessage("link-linking");
	                $rank = $this->getPlugin()->permissionManager->getPlayerGroup($sender)->getName();
					$cfg = $this->getPlugin()->getConfig()->get("links-api");
	                $this->getPlugin()->linkTasks[$sender->getName()] = $this->getPlugin()->makeHttpGetRequest($cfg["url"], [
	                    "serverID" => $cfg["server-id"],
	                    "serverKey" => $cfg["server-key"],
	                    "action" => "verifyLink",
	                    "VerifyCode" => $code,
	                    "xboxUser" => urlencode($sender->getName()),
	                    "rank" => $rank
	                ], 0, 1, true, 1, [self::class, "onLink", $sender->getName()]);
					echo "[QUEUED HTTP] " . PHP_EOL;
	                break;
	            case "LINKED":
					echo "[ALREADY LINKED]" . PHP_EOL;
	                $sender->sendMessage("link-linked");
	                break;
	            default:
			}
		});
		return true;
	}
	
	/**
	 * @param string $player
	 */
	public static function onLink(string $player) : void{
		if(!isset(Main::getInstance()->linkTasks[$player])){
            return;
        }
        $result = json_decode(Main::getInstance()->getHttpGetResult(Main::getInstance()->linkTasks[$player])[0], true);
		unset(Main::getInstance()->linkTasks[$player]);
		
        if(is_array($result) && !empty($result)){
			var_dump($result);
            $player = Server::getInstance()->getPlayerExact($player);
            if($player === null){
                return;
            }
            if($result[1] === "ERR_ALRD_LINKED"){
                $player->sendMessage("link-linked");
                return;
            }
            $player->sendMessage("link-link");
			$player->addMoney(Main::getInstance()->getConfig()->get("link-money"));
            LangManager::broadcast("link-broadcast", $player->getName());
			Main::getInstance()->updateDiscordEntry($player->getName(), "3", Main::getInstance()->permissionManager->getPlayerGroup($player)->getName());
        }
	}
	
}