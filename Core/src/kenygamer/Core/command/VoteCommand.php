<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class VoteCommand extends BaseCommand{
	private static $instance = null; //Ignore this mk
	
	public function __construct(){
		parent::__construct(
			"vote",
			"Checks to see if you've voted yet.",
			"/vote",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
		self::$instance = $this;
		$apis = $this->getPlugin()->getConfig()->get("vote-apis");
		[$this->api1, $this->api2] = $apis;
	}
	
	public static function voteCallback(string $list, string $name) : void{
		if(isset(self::$instance->getPlugin()->voteTasks[$name][$list])){
			$task = self::$instance->getPlugin()->voteTasks[$name][$list];
			unset(self::$instance->getPlugin()->voteTasks[$name][$list]);
			$player = self::$instance->getPlugin()->getServer()->getPlayerExact($name);
			if($player !== null){
				$result = null;
				try{
					$jsonBlob = self::$instance->getPlugin()->getHttpGetResult($task)[0] ?? "";
					$result = @json_decode((string) $jsonBlob, true, 512, JSON_THROW_ON_ERROR) ?? [];
				}catch(\Exception $e){
					var_dump($jsonBlob);
					self::$instance->getPlugin()->getLogger()->error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
				}
				if(isset($result["voted"]) && is_bool($result["voted"])){
					if($result["voted"]){
						self::$instance->getPlugin()->votes->setNested(mb_strtolower($player->getName()) . "." . $list, time());
						self::$instance->getPlugin()->voteparty->set("votes", self::$instance->getPlugin()->voteparty->get("votes", 0) + 1);
						$player->sendMessage("vote");
						$player->getInventory()->addItem(ItemUtils::get("voter_gem"));
						LangManager::broadcast("vote-broadcast", $player->getName());
						Main::getInstance()->registerEntry($player, Main::ENTRY_VOTEPOINTS, 1);
					}else{
						$player->sendMessage("vote-notvoted");
					}
				}else{
					\var_dump($result); //:yikes:
				}
			}
		}
	}
	
	protected function onExecute($sender, array $args) : bool{
		$votes = $this->getPlugin()->votes->get(mb_strtolower($sender->getName()), []);
		//date("Y-m-d", $votes["List1"]) === date("Y-m-d", time());
		$vote1 = isset($votes["List1"]) ? time() - $votes["List1"] <= 86400 : false;
		$vote2 = isset($votes["List2"]) ? time() - $votes["List2"] <= 86400 : false;
		if(isset($this->voteCooldown[$sender->getName()]) && time() - $this->voteCooldown[$sender->getName()] < 15){
			$sender->sendMessage("in-cooldown");
			return true;
		}
		if($vote1 && $vote2){
			$sender->sendMessage("vote-voted");
			return true;
		}
		$name = $sender->getName();
		if(isset($this->getPlugin()->voteTasks[$name]) && !empty($this->getPlugin()->voteTasks[$name])){
			return true;
		}
		if(!$this->getPlugin()->testSlot($sender, 1)){
			return true;
		}
		$this->getPlugin()->voteTasks[$name] = [];
		
		$this->voteCooldown[$sender->getName()] = time();
		if(!$vote1){
			$this->getPlugin()->voteTasks[$name]["List1"] = $this->getPlugin()->makeHttpGetRequest($this->api1, [
				"object" => "votes",
				"element" => "claim",
				"key" => $this->getPlugin()->getConfig()->get("list-api-key"),
				"username" => $name
			], 0, 1, true, 1, [self::class, "voteCallback", "List1", $name]);
		}
		if(!$vote2){
			$this->getPlugin()->voteTasks[$name]["List2"] = $this->getPlugin()->makeHttpGetRequest(str_replace(["{username}", "{key}"], [$name, $this->getPlugin()->getConfig()->get("list-2-api-key")], $this->api2), [], 0, 1, true, 1, [self::class, "voteCallback", "List2", $name]);
		}
		return true;
	}
	
}