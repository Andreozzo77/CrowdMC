<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class AncientPlayerQuest extends Quest{
	protected $money = 2000000000; //2000M
	protected $tokens = 20;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Play 3 days");
	}
	
	/**
	 * Called when a player quits.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player] += $value;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / (86400 * 3);
	}
	
}