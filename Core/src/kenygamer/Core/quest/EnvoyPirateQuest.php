<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class EnvoyPirateQuest extends Quest{
	protected $money = 1000000000; //1000M
	protected $tokens = 10;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Find 15 envoys");
	}
	
	/**
	 * Called when a player opens the chest of a spawned envoy.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player]++;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 15;
	}
	
}