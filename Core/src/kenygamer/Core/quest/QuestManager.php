<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use pocketmine\utils\Config;
use kenygamer\Core\Main;

final class QuestManager{
	private $plugin;
	/** @var Config */
	private $config;
	/** @var Quest[] */
	private $quests = [];
	
	public function __construct($plugin){
		$this->plugin = $plugin;
		$this->config = new Config($plugin->getDataFolder() . "quests.yml", Config::YAML);
		$this->loadQuests();
	}
	
	private function loadQuests() : void{
		$this->quests = [];
		$quests = [
		    "Duel King" => DuelKingQuest::class,
		    "Blacksmith" => BlacksmithQuest::class,
		    "Salesmen" => SalesmenQuest::class,
		    "Rich Rookie" => RichRookieQuest::class,
		    "Billionaire Baller" => BillionaireBallerQuest::class,
		    "Serial Killer" => SerialKillerQuest::class,
		    "Billionaire Bandit" => BillionaireBanditQuest::class,
		    "Kill Savage" => KillSavageQuest::class,
		    "Online 4ever" => Online4everQuest::class,
		    "Ancient Player" => AncientPlayerQuest::class,
		    "The Athlete" => TheAthleteQuest::class,
		    "Godly Gambler" => GodlyGamblerQuest::class,
		    "Ultimate Upgrader" => UltimateUpgraderQuest::class,
		    "Envoy Pirate" => EnvoyPirateQuest::class,
		    "Beast Battler" => BeastBattlerQuest::class,
		    "Token Collecter" => TokenCollecterQuest::class,
		    "Maniac Miner" => ManiacMinerQuest::class,
		    "Builder" => BuilderQuest::class,
		    "Harvester" => HarvesterQuest::class,
		    "Tree Cutter" => TreeCutterQuest::class,
		    "Boss Slayer" => BossSlayerQuest::class
		];
		foreach($quests as $name => $quest){
			$this->quests[] = new $quest($name, $this->plugin, $this->config->get(str_replace(" ", "_", mb_strtolower($name)), []));
		}
	}
	
	public function saveQuestData() : void{
		if($this->config !== null){
			foreach($this->quests as $quest){
				$quest->onSave();
				$this->config->set(mb_strtolower(str_replace(" ", "_", mb_strtolower($quest->getName()))), $quest->getData());
			}
			$this->config->save();
		}
	}
	
	public function getQuest(string $name) : ?Quest{
		foreach($this->quests as $quest){
			if(str_replace(" ", "_", mb_strtolower($quest->getName())) === $name){
				return $quest;
			}
		}
		return null;
	}
	
	public function getQuests() : array{
		return $this->quests;
	}
	
}