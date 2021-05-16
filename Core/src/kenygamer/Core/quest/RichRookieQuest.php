<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class RichRookieQuest extends Quest{
	protected $money = 40000000; //40M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Have 1 billion money");
	}
	
	/**
	 * Called in player balance updates.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player] = $value;
	}
	
	protected function obtainProgress(string $player) : float{
		$money = isset($this->data[$player]) ? $this->data[$player] : 0;
		return $money / 1000000000;
	}
	
}