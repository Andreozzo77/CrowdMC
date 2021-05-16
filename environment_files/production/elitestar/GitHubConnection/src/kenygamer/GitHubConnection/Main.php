<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

final class Main extends PluginBase{
	
	public function onEnable() : void{
		$errors = false;
		
		//Check required extensions
		$exts = ["git", "zip", "phar"];
		$missingExts = [];
        foreach($exts as $ext){
        	if(empty(shell_exec("command -v " . $ext))){
        		$missingExts[] = $ext;
        	}
        }
        if(count($missingExts) > 0){
        	$this->getLogger()->critical("These extensions are required but not found: " . implode(", ", $missingExts));
        	$errors = true;
        }
        
        if(posix_getuid() !== 0){
        	$this->getLogger()->critical("You must run PocketMine-MP as root/sudo");
        	$errors = true;
        }
        if($errors){
        	$this->getServer()->getPluginManager()->disablePlugin($this);
        }else{
        	$config = (new Config($this->getServer()->getDataPath() . "GitHubConnection.js", Config::JSON))->getAll();
        $this->getScheduler()->scheduleRepeatingTask(new CheckCommitTask($config["repositories"] ?? []), intval($config["check_rate"] ?? 5) * 20);
        }
	}
	
}