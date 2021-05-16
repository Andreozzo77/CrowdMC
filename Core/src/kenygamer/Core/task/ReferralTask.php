<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use pocketmine\Server;
use pocketmine\scheduler\Task;

class ReferralTask extends Task{
	
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if(isset(MiscListener::$referred_playing[$player->getName()]) && isset(MiscListener::$last_move[$player->getName()])){
				if(time() - MiscListener::$last_move[$player->getName()] >= 30){
					$player->addTitle(LangManager::translate("referral-title", $player), LangManager::translate("referral-afk", $player), 30, 30, 30);
				}else{
					if(++MiscListener::$referred_playing[$player->getName()][1] >= 60 * 15){
						$player->getInventory()->addItem(ItemUtils::get("bank_note(1500000)"));
						$player->addTitle(LangManager::translate("referral-title", $player), LangManager::translate("referral-reward", $player), 30, 30, 30);
						$referredBy = MiscListener::$referred_playing[$player->getName()][0];
						$referrals = $plugin->referrals->get($referredBy, []);
						$referrals[$player->getName()] = [
							"time" => time(),
							"claimed" => false,
							"username" => $player->getName(),
							"ip" => $player->getAddress(),
							"xuid" => $player->getXuid()
						];
						$plugin->referrals->set($referredBy, $referrals);
						$plugin->referrals->save();
						unset(MiscListener::$referred_playing[$player->getName()]);
						unset(MiscListener::$last_move[$player->getName()]);
					}
				}
			}
		}
	}
	
}