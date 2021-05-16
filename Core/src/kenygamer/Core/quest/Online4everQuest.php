<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use pocketmine\event\entity\EntityDamageeByEntityEvent;
use pocketmine\Player;
use kenygamer\Core\Main;

class Online4everQuest extends Quest{
	protected $money = 100000000; //100M
	protected $tokens = 1;
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Be online for an hour without disconnecting");
	}
	
	/**
	 * Called when a player quits.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		$this->data[$player] = $value;
	}
	
	protected function obtainProgress(string $player) : float{
		$progress = $this->data[$player] ?? 0;
		return $progress / 3600;
	}
	
}