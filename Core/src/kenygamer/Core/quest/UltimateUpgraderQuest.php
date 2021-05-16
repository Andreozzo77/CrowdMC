<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class UltimateUpgraderQuest extends Quest{
	protected $money = 300000000; //300M
	protected $tokens = 3;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Max 2 enchants of every rarity");
	}
	
	/**
	 * Called when a player maxes an enchant.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player][$sub])){
			$this->data[$player][$sub] = 0;
		}
		if($this->data[$player][$sub] < 2){
			$this->data[$player][$sub]++;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = 0;
		foreach($this->data[$player] ?? [] as $sub => $value){
			$progress += $value;
		}
		return $progress / 8;
	}
	
}