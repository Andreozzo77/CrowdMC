<?php

declare(strict_types=1);

namespace kenygamer\Core\quest;

use pocketmine\Player;
use pocketmine\IPlayer;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;

abstract class Quest{
	/** @var string */
	protected $name, $description;
	/** @var Main */
	protected $plugin;
	/** @var array */
	protected $data;
	
	//Rewards
	
	/** @var float */
	protected $money = 0;
	/** @var int */
	protected $tokens = 0;
	
	/**
	 * @api
	 *
	 * @param string|Player $player
	 * @param mixed $value
	 * @param mixed $sub
	 *
	 * @return bool true if the quest is completed in that progress add up
	 */
	public function progress($player, $value, $sub = "") : bool{
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = mb_strtolower($player);
		if(!$this->isCompleted($player)){
			$this->registerProgress($player, $value, $sub);
			if($this->isCompleted($player)){
				$p = $this->plugin->getServer()->getPlayerExact($player);
				if($p instanceof Player && $p->isOnline()){
					$p->addTitle(TextFormat::GOLD . "Quest Completed", $this->getDescription());
				}
				$this->plugin->addTokens($this->plugin->getServer()->getOfflinePlayer($player), $this->tokens);
				Main::getInstance()->addMoney($player, $this->money);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @api
	 *
	 * @param string|Player $player
	 *
	 * @returns float 0-100 progress
	 */
	public function getProgress($player) : float{
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = mb_strtolower($player);
		return $this->obtainProgress($player) * 100;
	}
	
	/**
	 * @api
	 *
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}
	
	/**
	 * @api
	 *
	 * @return array
	 */
	public function getData() : array{
		return $this->data;
	}
	
	/**
	 * @api
	 *
	 * @return string
	 */
	public function getDescription() : string{
		return $this->description;
	}
	
	/**
	 * Get tokens given upon completing the quest.
	 *
	 * @return int
	 */
	public function getTokens() : int{
		return $this->tokens;
	}
	
	/**
	 * Get money given upon completing the quest.
	 *
	 * @return float
	 */
	public function getMoney() : float{
		return $this->money;
	}
	
	/**
	 * @api
	 *
	 * @param Player|string $player
	 *
	 * @return bool
	 */
	public function isCompleted($player) : bool{
		return $this->getProgress($player) >= 100.0;
	}
	
	/**
	 * @param string $name
	 * @param Main $plugin
	 * @param array $data
	 * @param string $description
	 */
	public function __construct(string $name, Main $plugin, array $data, string $description){
		$this->name = $name;
		$this->plugin = $plugin;
		$this->data = $data;
		$this->description = $description;
	}
	
	abstract protected function registerProgress(string $player, $value, $sub = "") : void;
	
	abstract protected function obtainProgress(string $player) : float;
	
	public function onSave() : void{
	}
	
}