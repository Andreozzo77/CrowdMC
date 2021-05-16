<?php

declare(strict_types=1);

namespace kenygamer\Core\text;

use pocketmine\scheduler\Task;
use pocketmine\Server;

use kenygamer\Core\LangManager;

class FloatingTextTask extends Task{
	/** @var array */
	private $lastUpdate = [];
	
	public function onRun(int $currentTick) : void{
		$now = microtime(true);
		$players = Server::getInstance()->getOnlinePlayers();
		foreach(FloatingText::getTexts() as $identifier => $text){
			if($text->isFlaggedForClose()){
				$text->close(true); //Safe close
				continue;
			}
			if(!$text->isDynamic()){
				$text->spawnToAll();
			}elseif(!isset($this->lastUpdate[$identifier]) || $now - $this->lastUpdate[$identifier] >= $text->getUpdateInterval()){
				$this->lastUpdate[$identifier] = $now;
				foreach($players as $player){
					$text->updateForAndSendTo($player);
				}
			}
		}
	}
	
}