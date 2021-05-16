<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use kenygamer\Core\Main;

class TreeCutterQuest extends Quest{
	protected $money = 10000000; //10M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Cut 1000 tree logs");
	}
	
	/**
	 * Called when player breaks a log block.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		if(!isset($this->data[$player])){
			$this->data[$player] = 0;
		}
		$this->data[$player] += $value;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 1000;
	}
	
}