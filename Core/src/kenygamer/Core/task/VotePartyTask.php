<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use pocketmine\utils\TextFormat;
use LegacyCore\Core;

class VotePartyTask extends Task{
	/** @var Item[] */
	private $items;
	/** @var int */
	private $timer = 0;
	
	public function __construct(){
		$plugin = Main::getInstance();
		$plugin->saveResource("voteparty_items.yml", true);
		$this->items = ItemUtils::parseItems((new Config($plugin->getDataFolder() . "voteparty_items.yml", Config::YAML))->getAll());
		if(count($this->items) < 1){
			$plugin->getLogger()->warning("[VoteParty] No items loaded");
			$plugin->getScheduler()->cancelTask($this->getTaskId());
		}
		$webhook = $plugin->getConfig()->getNested("discord-webhooks.voteparty");
		$this->api = str_replace("{url}", $webhook, $plugin->getConfig()->get("discord-webhook-api"));
	}
	
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$votes = $plugin->voteparty->get("votes", 0);
		$cost = $plugin->getConfig()->getNested("voteparty.votes");
		$start = $plugin->getConfig()->getNested("voteparty.minutes-to-start") * 60;
		
		if($votes >= $cost){
			$period = $this->getHandler()->getPeriod();
			$this->timer += $period;
			
			if($this->timer >= $start){
				$votes -= $cost;
				$plugin->voteparty->set("votes", $votes);
				LangManager::broadcast("voteparty-reward");
				foreach($plugin->getServer()->getOnlinePlayers() as $player){
					$item = $this->items[array_rand($this->items)];
					$player->getInventory()->addItem($item);
				}
			}else{
				$param = $plugin->formatTime($plugin->getTimeLeft(time() + ($start - $this->timer)));
				LangManager::broadcast("voteparty-broadcast2", $param);
				$msg = LangManager::translate("voteparty-broadcast2", $param);
				if(Core::$snapshot !== ""){
					$plugin->makeHttpGetRequest(str_replace([
						"{msg}",
						"{params}"
					], [
						urlencode(TextFormat::clean($msg)),
						""
					], $this->api), [], 0, 1, false, 1, []);
				}
			}
		}else{
			$msg = LangManager::translate("voteparty-broadcast", $votes, $cost);
			if(Core::$snapshot !== ""){
				$plugin->makeHttpGetRequest(str_replace([
					"{msg}",
					"{params}"
				], [
					urlencode(TextFormat::clean($msg)),
					""
				], $this->api), [], 0, 1, false, 1, []);
			}
			LangManager::broadcast("voteparty-broadcast", $votes, $cost);
		}
	}
	
}