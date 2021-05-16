<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class SerialKillerQuest extends Quest{
	protected $money = 250000000; //250M
	protected $tokens = 3;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Get 250 kills");
	}
	
	/**
	 * Called when a kill is registered.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player]++;
	}
	
	protected function obtainProgress(string $player) : float{
		return ($this->data[$player] ?? 0) / 250;
	}
	
}