<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class GodlyGamblerQuest extends Quest{
	protected $money = 350000000; //350M
	protected $tokens = 4;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Win 5 consecutive casino games");
	}
	
	/**
	 * Called when a player wins or losses a bet.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player]) || $value < 1){
			$this->data[$player] = 0;
		}
		$this->data[$player]++;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 5;
	}
	
}