<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;

class AliasCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"alias",
			"View the aliases used by a player",
			"/alias <player>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$player = mb_strtolower($args[0]);
		if(!$this->getPlugin()->playerTrack->exists($player)){
			$sender->sendMessage("player-notfound");
	    	return true;
	    }
	    $data = $this->getPlugin()->playerTrack->get($player);
	    $aliases = ["ips" => [], "cids" => []];
	    foreach($this->getPlugin()->playerTrack->getAll() as $p => $entries){
	    	foreach($entries["ips"] as $ip){
	    		if(in_array($ip, $data["ips"])){
	    			$aliases["ips"][] = $p . ":" . (!$sender->isOp() ? preg_replace("/[0-9]+/", "x", $ip) : $ip);
	    		}
	    	}
	    	foreach($entries["cids"] as $cid){
	    		if(in_array($cid, $data["cids"])){
	    			$aliases["cids"][] = $p . ":" . (!$sender->isOp() ? preg_replace("/[^\W]/", "x", $cid) : $cid);
	    		}
	    	}
	    }
	    if(($c = count($aliases, COUNT_RECURSIVE) - 2) > 0){
	    	$msg = LangManager::translate("alias-title", $sender, $c, $player);
	    	if(!empty($aliases["ips"])){
	    		$msg .= "\n" . LangManager::translate("alias-ips", count($aliases["ips"]));
	    		foreach($aliases["ips"] as $i => $result){
	    			list($pl, $entry) = explode(":", $result);
	    			$msg .= $entry . " ({$pl})" . ($i !== count($aliases["ips"]) - 1 ? ", " : "");
	    		}
	    	}
	    	if(!empty($aliases["cids"])){
	    		$msg .= "\n" . LangManager::translate("alias-cids", count($aliases["cids"]));
	    		foreach($aliases["cids"] as $i => $result){
	    			list($pl, $entry) = explode(":", $result);
	    			$msg .= $entry . " ({$pl})" . ($i !== count($aliases["cids"]) - 1 ? ", " : "");
	    		}
	    	}
			$sender->sendMessage($msg);
	    }else{
			$sender->sendMessage("alias-none", $player);
	    }
		return true;
	}
	
}