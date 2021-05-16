<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\task\MaintenanceTask;

class MaintenanceCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"maintenance",
			"Toggle server maintenance",
			"/maintenance <on/off> [reason]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(!isset($args[0])){ //NOPE
        	$oldStatus = $this->getPlugin()->maintenanceMode;
        }else{
        	$newStatus = $args[0];
        	$oldStatus = $newStatus === "on" ? false : true;
        }
        if($oldStatus){
        	$this->getPlugin()->maintenanceMode = false;
        	$this->getPlugin()->getMaintenanceConfig()->set("manual-status", false);
        	$this->getPlugin()->getMaintenanceConfig()->save();
			$sender->sendMessage("maintenance-status", "off");
        	return true;
        }
        $reason = $this->getPlugin()->getMaintenanceReason();
        if(isset($args[0])){
        	array_shift($args);
        	$reason = str_replace("{LINE}", PHP_EOL, implode(" ", $args));
        }
        $this->getPlugin()->getMaintenanceConfig()->set("manual-status", true);
        $this->getPlugin()->getMaintenanceConfig()->set("reason", $reason);
        $this->getPlugin()->getMaintenanceConfig()->save();
        
        MaintenanceTask::resetMaintenanceSince();
		return true;
	}
	
}