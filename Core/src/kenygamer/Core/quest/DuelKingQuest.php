<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class DuelKingQuest extends Quest{
	protected $money = 100000000; //100M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Win 4 times every kind of duel");
	}
	
	/**
	 * Called when a duel is won.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		try{
			$this->plugin->getDuelName($sub);
		}catch(\InvalidArgumentException $e){
			return;
		}
		if(!isset($this->data[$player][$sub])){
			$this->data[$player][$sub] = 0;
		}
		if($this->data[$player][$sub] < 4){
			$this->data[$player][$sub]++;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = 0;
		foreach($this->data[$player] ?? [] as $sub => $value){
			$progress += $value;
		}
		return $progress / 16;
	}
	
}