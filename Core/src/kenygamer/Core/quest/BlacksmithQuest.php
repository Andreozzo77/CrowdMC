<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class BlacksmithQuest extends Quest{
	protected $money = 10000000; //10M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Successfully repair a broken key");
	}
	
	/**
	 * Called when a broken key is repaired.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		$this->data[$player] = 1;
	}
	
	protected function obtainProgress(string $player) : float{
		return isset($this->data[$player]) ? 1 : 0;
	}
	
}