<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class SalesmenQuest extends Quest{
	protected $money = 15000000; //15M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Sell 5 auctions");
	}
	
	/**
	 * Called when an auction is sold.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player]++;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 5;
	}
	
}