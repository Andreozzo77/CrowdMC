<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use onebone\economyapi\EconomyAPI;
use kenygamer\Core\Main;

class BillionaireBallerQuest extends Quest{
	protected $money = 250000000; //250M
	protected $tokens = 3;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Get max money");
	}
	
	/**
	 * Called in player balance updates.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		if($value > $this->data[$player]){
			$this->data[$player] = $value;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		$money = isset($this->data[$player]) ? $this->data[$player] : 0;
		return $money / 0x7fffffff;
	}
	
}