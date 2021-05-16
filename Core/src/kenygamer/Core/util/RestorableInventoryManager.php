<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use kenygamer\Core\Main;
use pocketmine\Player;

/**
 * This class serves to avoid hardcoding mini-games that wipe your inventory and reset it i.e at the end of the match.
 */
class RestorableInventoryManager{
	/** @var array */
	private $inventories = [];
	
	/** @var self|null */
	private static $instance = null;
	
	/**
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		self::$instance = $this;
	}
	
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	public function saveInventory(Player $player, int $listeners = 0) : bool{
		if(isset($this->inventories[$player->getName()])){
			return false;
		} 
		$this->inventories[$player->getName()] = [
		   $player->getInventory() !== null ? $player->getInventory()->getContents(true) : [],
		   $player->getArmorInventory() !== null ? $player->getArmorInventory()->getContents(true) : []
		];
		if($player->getInventory() !== null){
			$player->getInventory()->clearAll();
		}
		if($player->getArmorInventory() !== null){
			$player->getArmorInventory()->clearAll();
		}
		return true;
	}
	
	public function returnInventory(Player $player) : ?array{
		if(!isset($this->inventories[$player->getName()])){
			return null;
		}
		if(($normalInventory = $player->getInventory()) !== null){
			$player->getInventory()->setContents($this->inventories[$player->getName()][0]);
		}
		if(($armorInventory = $player->getArmorInventory()) !== null){
			$player->getArmorInventory()->setContents($this->inventories[$player->getName()][1]);
		}
		unset($this->inventories[$player->getName()]);
		return [$normalInventory, $armorInventory];
	}
	
}