<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class BillionaireBanditQuest extends Quest{
	protected $money = 60000000; //60M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Open 10 chests in one raid");
	}
	
	/**
	 * Called when a raid is made.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if($value >= 10){
			$this->data[$player] = 1;
		}
	}
	
	protected function obtainProgress(string $player) : float{
		return (isset($this->data[$player]) ? 1 : 0);
	}
	
}