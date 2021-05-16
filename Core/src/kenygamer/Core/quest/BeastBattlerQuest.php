<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class BeastBattlerQuest extends Quest{
	protected $money = 50000000; //50M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Have 100 total CEs equipped");
	}
	
	/**
	 * Called when a scoreboard is sent.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		if(!($value < $this->data[$player])){
			$this->data[$player] = $value;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 100;
	}
	
}