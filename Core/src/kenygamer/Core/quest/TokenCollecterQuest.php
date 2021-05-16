<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class TokenCollecterQuest extends Quest{
	protected $money = 1000000000; //1000M
	protected $tokens = 10;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Grind 750 tokens");
	}
	
	/**
	 * Called when tokens are added by grinding.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player] += $value;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 750;
	}
	
}