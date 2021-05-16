<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\FileWriteTask;
use pocketmine\Player;

use kenygamer\Core\Main; 

class DonationsTask extends Task{
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		$file = $plugin->getConfig()->get("payments-db");
		if(!file_exists($file)){
			$plugin->touchFile($file);
		}
		$old = (array) @json_decode(file_get_contents($file), true);
		$payments = $old;
		$console = new ConsoleCommandSender();
		foreach($payments as $id => $payment){
			if($payment["completed"]){
				$name = "\"" . $payment["username"] . "\"";
				$player = $plugin->getServer()->getPlayerExact($payment["username"]);
				if($player instanceof Player && $player->isOnline()){
					$slots = $payment["slots"] ?? 0;
					if($plugin->testSlot($player, $slots)){
						foreach($payment["onlineCommands"] ?? [] as $cmd){
							$plugin->getServer()->dispatchCommand($console, str_replace("{name}", $name, $cmd));
						}
						unset($payments[$id]["onlineCommands"]);
					}
				}
				foreach($payment["instantCommands"] ?? [] as $cmd){
					$plugin->getServer()->dispatchCommand($console, str_replace("{name}", $name, $cmd));
				}
				unset($payments[$id]["instantCommands"]);
				if(empty($payments[$id]["onlineCommands"] ?? []) && empty($payments[$id]["instantCommands"] ?? [])){
					unset($payments[$id]);
				}
				if(isset($payment["amount"])){
					unset($payments[$id]["amount"]);
					//Micropayment fee (6.5% + 0.05) + currency conversion fee (3.5%)
					$amount = ($payment["amount"] * 90 / 100) - 0.05;
					$cfg = $plugin->getConfig()->get("links-api");
					$plugin->makeHttpGetRequest($cfg["url"], [
					    "serverID" => $cfg["server-id"],
					    "serverKey" => $cfg["server-key"],
					    "action" => "sendDiscordWebhook",
					    "url" => $plugin->getConfig()->getNested("discord-webhooks.donations"),
					    "message" => urlencode("EliteStar received a payment worth $" . number_format(floor($amount), 2) . " USD 💸")
					], 1, 1, true, 1, []);
				}
			}
		}
		if($payments !== $old){
			$plugin->getServer()->getAsyncPool()->submitTask(new FileWriteTask($file, json_encode($payments)));
		}
	}

}