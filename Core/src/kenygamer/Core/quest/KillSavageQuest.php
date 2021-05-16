<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class KillSavageQuest extends Quest{
	protected $money = 30000000; //30M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Kill 30 times without dying");
	}
	
	/**
	 * Called when a kill/death is registered.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		//death = -
		//kill = +
		if($value <= 0){
			$this->data[$player] = 0;
		}else{
			$this->data[$player]++;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 30;
	}
	
}