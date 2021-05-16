<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;
use kenygamer\Core\item\FireworksItem;
use xenialdan\BossBar\BossBar;

class GiveawayTask extends Task{
	
	/**
	 * Giveaway system with restart bar. Rewards configurable by command.
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$players = $plugin->getServer()->getOnlinePlayers();
		/** @var string */
	    $it = ItemUtils::getDescription(Main::$giveawayStatus[5]);
		
		if(count($players) >= Main::$giveawayStatus[6] && Main::$giveawayStatus[0] && !Main::$giveawayStatus[1]){
			Main::$giveawayStatus[1] = true;
			foreach($players as $player){
			    $player->teleport($player->getServer()->getDefaultLevel()->getSafeSpawn());
				Main::$giveawayStatus[2][] = $player->getName();
			}
			LangManager::broadcast("giveaway-result", count(Main::$giveawayStatus[2]), implode(", ", Main::$giveawayStatus[2]));
		}elseif(Main::$giveawayStatus[0] && Main::$giveawayStatus[1]){
			foreach($players as $player){
				$plugin->getPlayerBossBar($player)->setSubtitle(LangManager::translate("giveaway-bar2", count(Main::$giveawayStatus[3]), Main::$giveawayStatus[6]));
				$plugin->getPlayerBossBar($player)->setPercentage(count($players) / Main::$giveawayStatus[6]);
			}
			if(Main::$giveawayStatus[6] === count(Main::$giveawayStatus[3])){
				Main::$giveawayStatus[0] = 0;
				$plugin->resetBossBar();
			}
			foreach(Main::$giveawayStatus[2] as $player){
				if(in_array($player, Main::$giveawayStatus[3])){
					continue; 
				}
				$pl = $plugin->getServer()->getPlayerExact($player);
				if($pl === null || !$plugin->testSlot($pl, count(Main::$giveawayStatus[5]))){
					continue; 
				}
				foreach(Main::$giveawayStatus[5] as $item){
					$pl->getInventory()->addItem($item);
				}
				$item = new FireworksItem();
				$item->setCustomName(TextFormat::colorize("&r&d&lGiveaway Fireworks"));
				$item->setLore([
					TextFormat::colorize("&r&dGiveaway Date: &f" . date("F dS, Y"))
				]);
				$item->setCount(3);
				$pl->getInventory()->addItem($item);
				$plugin->getServer()->dispatchCommand($pl, "hub");
				$pl->addTitle(LangManager::translate("giveaway-result-title-1", $pl), LangManager::translate("giveaway-result-title-2", $pl, $it), 150, 150, 150);
				Main::$giveawayStatus[3][] = $player;
			}
		}elseif(Main::$giveawayStatus[0]){
			$online = strval(count($players));
			foreach($players as $player){
				$plugin->getPlayerBossBar($player)->setSubtitle(LangManager::translate("giveaway-bar1", $online, Main::$giveawayStatus[6]));
				$plugin->getPlayerBossBar($player)->setPercentage(count($players) / Main::$giveawayStatus[6]);
			}
		}
	}
	
}