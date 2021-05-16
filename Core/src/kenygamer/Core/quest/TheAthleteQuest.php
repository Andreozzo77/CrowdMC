<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use pocketmine\Player;
use kenygamer\Core\Main;

class TheAthleteQuest extends Quest{
	public const WALKING_SPEED = 4.317;
	public const SPRINTING_SPEED = 5.612;
	public const FLYING_SPEED = 10.92;
	public const SPRINT_FLYING_SPEED = 21.60;
	
	protected $money = 10000000; //10M
	protected $tokens = 1;
	
	/** @var array */
	private $steps = [];
	
	public function __construct(string $name, Main $plugin, array $data){
		parent::__construct($name, $plugin, $data, "Walk non-stop in 5 minutes");
	}
	
	/**
	 * Called when a player moves.
	 */
	protected function registerProgress(string $player, $value, $sub = "") : void{
		$p = $this->plugin->getServer()->getPlayerExact($player);
		if($p instanceof Player){
			$pos = $p->asVector3();
			$now = time();
			if(!isset($this->steps[$player]) || empty($this->steps[$player])){
				$this->steps[$player][] = [$pos, $now];
				return;
			}
			$lastStep = end($this->steps[$player]);
			
			$dist = self::WALKING_SPEED;
			if($p->isFlying()){
				if($p->isSprinting()){
					$dist = self::SPRINT_FLYING_SPEED;
				}else{
					$dist = self::FLYING_SPEED;
				}
			}elseif($p->isSprinting()){
				$dist = self::SPRINTING_SPEED;
			}
			
			$lastStep = end($this->steps[$player]);
			if($lastStep[0]->distance($pos) >= $dist / self::WALKING_SPEED){
				$this->steps[$player][] = [$pos, $now];
			}
		}
	}
	
	protected function obtainProgress(string $player) : float{
		foreach($this->steps[$player] ?? [] as $i => $step){
			if(time() - $step[1] >= 300){
				unset($this->steps[$player][$i]);
			}
		}
		if(count($this->steps[$player] ?? []) >= 1094){
			$this->data[$player] = 1;
		}
		return isset($this->data[$player]) ? 1 : (count($this->steps[$player] ?? []) / 1094);
	}
	
}