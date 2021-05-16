<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

class VehicleCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"vehicle",
			"Respawn your vehicle.",
			"/vehicle",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$vehicle = $this->getPlugin()->getVehicle($sender);
		if($vehicle !== null){
			$vehicle->removePlayer($sender);
			if(!$vehicle->isClosed()){
				$vehicle->flagForDespawn();
			}
		}
		$this->getPlugin()->vehicleFactory->spawnVehicle("BasicCar", $sender->getLevel(), $sender->asVector3(), $sender->getRawUniqueId());
		$sender->sendMessage("vehicle-respawn");
		return true;
	}
	
}