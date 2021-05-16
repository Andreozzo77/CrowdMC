<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\Server;
use pocketmine\math\Vector3;

use kenygamer\Core\listener\MiscListener2;

class SendEffectsTask extends Task{
	
	public function onRun(int $currentTick) : void{
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if(!$player->isOnline()){
				return;
			}
			$id = $player->getId();
			
			$queue = MiscListener2::$effects[$id] ?? [];
			if(count($queue) < 1){
				continue;
			}
			
			$batch = new BatchPacket();
			$count = 0;
			foreach($queue as $effId => $data){
				$pk = new MobEffectPacket();
				$pk->entityRuntimeId = $id;
				$pk->eventId = $data->eventId;
				$pk->effectId = $effId;
				if($pk->eventId !== MobEffectPacket::EVENT_REMOVE){
					$pk->amplifier = $data->amplifier;
					$pk->particles = $data->particles;
					$pk->duration = $data->duration;
				}
				$batch->addPacket($pk);
				$count++;
			}
			unset(MiscListener2::$effects[$id]);
			$batch->setCompressionLevel(7);
			$player->dataPacket($batch);
		}
	}
	
}