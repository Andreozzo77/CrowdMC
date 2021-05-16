<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use kenygamer\Core\command\TradeCommand;

class TradeTask extends Task{
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$command = TradeCommand::getInstance();
		foreach($command->trades as $trader => $client){
			$t = Server::getInstance()->getPlayerExact($trader);
			$c = Server::getInstance()->getPlayerExact($client);
			
			if(time() - ($command->time[$trader] ?? 0) >= TradeCommand::TRADE_TIMEOUT){
				$command->finishTrade($trader, "trade-finish-8");
				continue;
			}
			
			if($t && $c){
				if($t->distance($c) <= TradeCommand::TRADE_DISTANCE_MAX){
					continue;
				}
				$command->finishTrade($trader, "trade-finish-9");
			}
		}
	}
	
}