<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use pocketmine\inventory\BaseInventory;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\Item;
use pocketmine\utils\Config;

use kenygamer\Core\Main;

class SaveableInventory extends BaseInventory{
	/** @var bool */
	private static $isInit = false;
	/** @var BigEndianNBTStream|null */
	private static $nbtSerializer = null;
	/** @var Config|null */
	private static $inventoriesCfg = null;
	/** @var self[] */
	private static $inventories = [];
	
	//Known identifiers ::
	
	//fvault_%FACTION%
	//inv_%PLAYER%
	//inv2_%PLAYER%
	
	public static function init() : void{
		$plugin = Main::getInstance();
		if($plugin === null){
			return;
		}
		self::$inventoriesCfg = new Config($plugin->getDataFolder() . "inventories.js", Config::JSON);
		self::$nbtSerializer = new BigEndianNBTStream();
		foreach(self::$inventoriesCfg->getAll() as $identifier => $data){
			/** @var CompoundTag */
			$inventory = utf8_decode($data["inventory"]);
			/** @var CompoundTag[] */
        	$subtags = self::$nbtSerializer->readCompressed($inventory)->getListTag("Inventory");
        	$items = [];
        	foreach($subtags as $tag){
        		$items[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
        	}
        	$inventory = self::createInventory($identifier, $data["size"]);
        	$inventory->setContents($items);
		}
		self::$isInit = true;
	}
	
	public static function saveAll() : void{
		if(self::$isInit){
			self::$inventoriesCfg->setAll([]);
			foreach(self::$inventories as $identifier => $inventory){
				$items = [];
				foreach($inventory->getContents() as $slot => $item){
					$items[] = $item->nbtSerialize($slot);
				}
				/**
				 * @var string we cant use the json_encode JSON_UNESCAPED_UNICODE flag in Config::save(), so UTF-8 encode it
				 */
				$data = utf8_encode(self::$nbtSerializer->writeCompressed(new CompoundTag("", [
				    new ListTag("Inventory", $items)
				])));
				self::$inventoriesCfg->set($identifier, ["size" => $inventory->getSize(), "inventory" => $data]);
			}
			self::$inventoriesCfg->save();
			self::$isInit = false;
		}
	}
	
	/**
	 * @api
	 */
	public static function getInventory(string $identifier) : ?self{
		return self::$inventories[$identifier] ?? null;
	}
	
	/**
	 * @api
	 */
	public static function createInventory(string $identifier, int $size = 36){
		if(isset(self::$inventories[$identifier])){
			return self::$inventories[$identifier];
		}
		return self::$inventories[$identifier] = new self([], $size);
	}
	
	/**
	 * @api
	 */
	public static function deleteInventory(string $identifier) : bool{
		if(!isset(self::$inventories[$identifier])){
			return false;
		}
		unset(self::$inventories[$identifier]);
		return true;
	}
	
	public function __construct(array $items = [], int $size = null){
		parent::__construct($items, $size);
	}
	
	public function getName() : string{
		return "Inventory";
	}
	
	public function getDefaultSize() : int{
		return 36;
	}
	
}