<?php

declare(strict_types=1);

namespace kenygamer\Core;

use pocketmine\Player;
use pocketmine\network\SourceInterface;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\inventory\BaseInventory as PMBaseInventory;

use kenygamer\Core\inventory\ElitePlayerInventoryAdapter;
use kenygamer\Core\inventory\ArmorInventoryAdapter;
use pocketmine\inventory\ArmorInventory;
use kenygamer\Core\inventory\TestInventory;
use onebone\economyapi\EconomyAPI;

class ElitePlayer extends Player{
	/** @var PlayerInventory */
	private $realInventory;
	/** @var ElitePlayerInventoryAdapter */
	protected $inventory;
	private $testInventory;
	
	//Player::RESERVED_WINDOW_ID_RANGE_START = ContainerIds::LAST - 10
	//1 and 2 used
	public const HARDCODED_ANVIL_WINDOW_ID = ContainerIds::LAST - 10 + 3;
	public const HARDCODED_ENCHANTING_TABLE_WINDOW_ID = ContainerIds::LAST - 10 + 4;
	
	public $openHardcodedWindows = [];
	
	public function __construct(SourceInterface $interface, string $address, int $port){
		parent::__construct($interface, $address, $port);
	}
	
	public function addXp(int $amount, bool $playSound = true) : bool{
		if(($_1 = $this->totalXp + $amount < -0x7fffffff) or ($_2 = $this->totalXp + $amount > 0x7fffffff)){
			return $this->setXpAndProgress($_1 ? 0 : 0x7fffffff, ($_1 ? 0 : 1.0));
		}
		return parent::addXp($amount, $playSound);
	}
	
	public function sendMessage($message, ...$params){
		$lang = LangManager::getInstance();
		if($lang instanceof LangManager && is_string($message) && $lang->langExists($message, $lang->getLangByAddress($this->getAddress()))){
			LangManager::send($message, $this, ...$params);
		}else{
			parent::sendMessage($message);
		}
	}
	
	//Custom
	
	protected function sendAllInventories(){
		foreach($this->windowIndex as $id => $inventory){
			if($inventory instanceof ArmorInventory){
				$this->armorInventory->sendContents($this);
			}
			if($inventory instanceof PMBaseInventory){
				$this->testInventory->setSize($inventory->getSize());
				$this->testInventory->setContents($inventory->getContents(true));
				$this->testInventory->sendContents($this);
			}
		}
	}
	
	public function setCurrentTotalXp(int $amount) : bool{
		return parent::setCurrentTotalXp(max(0, $amount));
	}
	
	/**
	 * @return string|int
	 */
	public function getMoney(){
		$plugin = Main::getInstance();
		if($plugin){
			return $plugin->myMoney($this);
		}
		return 0;
	}
	
	/**
	 * @param string|int $money
	 * @return bool
	 */
	public function addMoney($money) : bool{
		$plugin = Main::getInstance();
		if($plugin){
			return $plugin->addMoney($this, $money);
		}
		return false;
	}
	
	/**
	 * @param string|int $money
	 * @return bool
	 */
	public function reduceMoney($money) : bool{
		$plugin = Main::getInstance();
		if($plugin){
			return $plugin->reduceMoney($this, $money);
		}
		return false;
	}
	
	public function getXpLevel() : int{
		$plugin = Main::getInstance();
		if($plugin){
			return (int) $plugin->getEntry($this, Main::ENTRY_EXPERIENCE_LEVEL);
		}
		return 0;
	}
	
	public function getXpProgress() : float{
		$plugin = Main::getInstance();
		if($plugin){
			return (float) $plugin->getEntry($this, Main::ENTRY_EXPERIENCE);
		}
		return 0.0;
	}
	
	protected function setXpAndProgress(?int $level, ?float $progress) : bool{
		$plugin = Main::getInstance();
		if($progress > 1){
			$progress = 1; //Fix outside range huh
		}
		if($plugin){
			$clampedLevel = $level > 24791 ? 24791 : $level;
			if(parent::setXpAndProgress($clampedLevel, $progress)){
				$plugin->registerEntry($this, Main::ENTRY_EXPERIENCE_LEVEL, $level);
				$plugin->registerEntry($this, Main::ENTRY_EXPERIENCE, $progress);
				return true;
			}
			return false;
		}
		return false;
	}
	
	protected function initEntity() : void{
		parent::initEntity();
		
		$this->inventory = new ElitePlayerInventoryAdapter($this->inventory);
		$this->armorInventory = new ArmorInventoryAdapter($this->armorInventory);
		$this->testInventory = new TestInventory([]);
	}
	
	public function setScale(float $value) : void{
		parent::setScale($value);
		$this->baseOffset = 1.62 * $value;
	}
	
}