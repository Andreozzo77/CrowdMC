<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\BaseInventory;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\BigEndianNBTStream;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use CustomEnchants\CustomEnchants\CustomEnchants;

/**
 * Function set for working with items.
 */
final class ItemUtils{
	//Containers
	public const GEM_CONTAINER_TAG = "GemContainer";
	
	public const CONSUMABLE_HOLY_SCROLL = 0;
	public const CONSUMABLE_ENCHANT_DUST = 1;
	public const CONSUMABLE_WHITE_SCROLL = 2;
	public const CONSUMABLE_TAG = "Consumable";
	public const BOOK_CHANCE_TAG = "BookChance";
	
	/** @var BigEndianNBTStream */
	private static $nbtSerializer = null;
	/** @var bool */
	private static $gemContainersInit = false;
	/** @var array */
	private static $gemContainers = [];
	
	public static function isInit() : bool{
		return self::$nbtSerializer !== null;
	}
	
	public static function init() : void{
		self::$nbtSerializer = new BigEndianNBTStream();
	}
	
	public static function initGemContainers() : void{
		self::init(); //...
		$plugin = Main::getInstance();
		if($plugin === null){
			return;
		}
		self::$gemContainersInit = false;
		self::$gemContainers = [];
		$plugin->saveResource("gem_containers.yml", true);
		$gemContainers = (new Config($plugin->getDataFolder() . "gem_containers.yml", Config::YAML))->getAll();
		$tier = 1;
		foreach($gemContainers as $stringyIndex => $gemContainer){
			$stringyIndex = mb_strtolower(str_replace(" ", "_", $stringyIndex));
			
			self::$gemContainers[$stringyIndex] = new \StdClass();
			self::$gemContainers[$stringyIndex]->kit = self::parseItems($gemContainer["kit"]);
			self::$gemContainers[$stringyIndex]->name = $gemContainer["name"];
			self::$gemContainers[$stringyIndex]->tier = $plugin->getPlugin("CustomEnchants")->getRomanNumber($tier);
		}
		self::$gemContainersInit = true;
	}
	
	private static function getGemContainer(string $stringyIndex) : ?Item{
		$gemContainer = self::$gemContainers[$stringyIndex] ?? null;
		if($gemContainer !== null){
			$item = self::get(Item::NETHER_STAR);
			$item->setCustomName(TextFormat::RESET . TextFormat::colorize($gemContainer->name));
			
			$wrapped = wordwrap(self::getDescription($gemContainer->kit), 35);
			$lore[] = "&r&7" . implode("&r&7\n", explode("\n", $wrapped));
			$lore[] = "";
			$lore[] = "&r&6Tier Level: &e" . $gemContainer->tier;
			$item->setLore(array_map(function(string $line) : string{
				return TextFormat::colorize($line);
			}, $lore));
			
			$nbt = $item->getNamedTag();
			$nbt->setString(self::GEM_CONTAINER_TAG, $stringyIndex);
			$item->setNamedTag($nbt);
			return $item;
		}
		
	return null;
	}
	
	/**
	 * @param Item $item
	 * @param int $sudcessRate
	 * @return Item
	 */
	public static function addBookChance(Item $item, int $successRate = -1) : Item{
		if($successRate !== -1 && $successRate > 100){
			throw new \OutOfBoundsException("Success rate is out of range");
		}
		if($successRate === -1){
			$successRate = mt_rand(0, 100);
		}
		$destroyRate = 100 - $successRate;
		$lore = $item->getLore();
		$lore[2] = TextFormat::RESET . TextFormat::GREEN . "Success Rate: " . $successRate . "%";
		$lore[3] = TextFormat::RESET . TextFormat::RED . "Destroy Rate: " . $destroyRate . "%";
		$item->setLore($lore);
		$nbt = $item->getNamedTag();
		$nbt->setInt(ItemUtils::BOOK_CHANCE_TAG, $successRate);
		$item->setNamedTag($nbt);
		return $item;
	}
    
	
	/**
	* @param Item $item
	* @return \StdClass|null
	*/
	private static function getGemContainerByItem(Item $item){
		if($item->getId() === Item::NETHER_STAR && $item->getNamedTag()->hasTag(self::GEM_CONTAINER_TAG, StringTag::class)){
			if(isset(self::$gemContainers[$item->getNamedTag()->getString(self::GEM_CONTAINER_TAG)])){
				return self::$gemContainers[$item->getNamedTag()->getString(self::GEM_CONTAINER_TAG)];
			}
		}
		return null;
	}
	
	/**
	 * @param Player $player
	 */
	public static function attemptRedeemGemContainer(Player $player) : void{
		$item = $player->getInventory()->getItemInHand();
		$gemContainer = self::getGemContainerByItem($item);
		if($gemContainer !== null){
			/*if($gemContainer->name !== "voter_gem"){
				LangManager::send("coming-soon");
				return;
			}*/
			if(self::addItems($player->getInventory(), ...$gemContainer->kit)){
				$item->pop();
				$player->getInventory()->setItemInHand($item);
				LangManager::send("gemcontainer-open", $player, $gemContainer->name);
				$player->getLevel()->addParticle(new HugeExplodeSeedParticle($player->asVector3()), [$player]);
			}else{
				LangManager::send("inventory-nospace", $player);
			}
		}
	}
	
	public static function getEnchantBookType(Item $item) : ?string{
		$books = [
			"common_book" => self::get("common_book"),
			"uncommon_book" => self::get("uncommon_book"),
			"rare_book" => self::get("rare_book"),
			"mythic_book" => self::get("mythic_book")
		];
		foreach($books as $name => $book){
			if($book->getId() === $item->getId() && $book->getDamage() === $item->getDamage()){
				return $name;
			}
		}
		return null;
	}
	public static function isEnchantBook(Item $item) : bool{
		return self::getEnchantBookType($item) !== null;
	}
	
	public static function addDummyEnchant(Item &$item){
		$item->addEnchantment(new EnchantmentInstance(new Enchantment(255, "", Enchantment::RARITY_COMMON, Enchantment::SLOT_ALL, Enchantment::SLOT_NONE, 1)));
	}
	
	/**
	 * @param string|int|null $count
	 * @return int
	 */
	private static function parseItemCount($count) : int{
		if(is_string($count) && count($range = explode("-", $count)) === 2){
			return \kenygamer\Core\Main::mt_rand(min((int) round($range[0]), (int) round($range[1])), max((int) round($range[0]), (int) round($range[1])));
		}else{
			$count = (int) round($count);
			if($count < 1){
				$count = 1;
			}
			return $count;
		}
	}
	
	/**
	 * @param array $data
	 * @return Item[]
	 */
	public static function parseItems(array $data) : array{
		$items = [];
		foreach($data as $entry){
			foreach($entry as $name => $data){
				$count = self::parseItemCount($data["count"] ?? null);
				$items[] = (ItemUtils::get($name, $data["name"] ?? "", $data["lore"] ?? [], $data["enchants"] ?? []))->setCount($count);
			}
		}
	
		return $items;
	}
	
	
	/**
	 * @param Item[]|Item $item
	 * @return string
	 */
	public static function getDescription($item){
		if($item instanceof Item){
			$items[] = $item;
		}else{
			$items = $item;
		}
		$desc = "";
		foreach($items as $it){
			$desc .= "x" . $it->getCount() . " " . TextFormat::clean(explode("\n", $it->getName())[0]) . ($it !== end($items) ? ", " : "");
		}
		return $desc;
	}
	
	/**
	 * @param Inventory|Item[] $inventory
	 * @param Item $item
	 * @return int
	 */
	public static function findItem($inventory, Item $item) : int{
		if($item->isNull()){
			return -1;
		}
		$items = $inventory instanceof Inventory ? $inventory->getContents() : $inventory;
		foreach($items as $slot => $item2){
			if($item2->getId() === $item->getId() && $item2->getDamage() === $item->getDamage() && $item2->getCount() === $item->getCount()){
				foreach($item->getEnchantments() as $enchantment){
					if(!(($enchant2 = $item2->getEnchantment($enchantment->getId())) && $enchant2->getLevel() === $enchantment->getLevel())){
						return -1;
					}
				}
				$lore = $item->getLore();
				foreach($lore as $line){
					foreach($item2->getLore() as $line2){
						if($line2 === $line){
							unset($lore[array_search($line, $lore)]);
						}
					}
				}
				if(!empty($lore)){
					return -1;
				}
				//TODO! NBT
				return $slot;
			}
		}
		return -1;
	}
	
	/**
	 * @param Inventory $inventory
	 * @param Item|Item[] ...$items
	 *
	 * @return bool true if all items were added successfully, false if lacking of slots
	 *              (in the latter case no items will be added)
	 */
	public static function addItems(Inventory $inventory, ...$items) : bool{
		$inv = new class extends BaseInventory{
			/**
			 * @return string
			 */
			public function getName() : string{
				return "FakeInventory";
			}
			/**
			 * @return int
			 */
			public function getDefaultSize() : int{
				return 37;
			}
		};
		$inv->setContents($inventory->getContents());
		foreach($items as $item){
			if(is_array($item)){
				foreach($item as $sub){
					if($inv->canAddItem($sub)){
						$inv->addItem($sub);
						continue;
					}
					return false;
				}
			}else{
				if($inv->canAddItem($item)){
					$inv->addItem($item);
					continue;
				}
				return false;
			}
		}
		$inventory->setContents($inv->getContents());
		return true;
	}
	
	/**
	 * @see ItemUtils::getItem()
	 */
	public static function get($item, string $customName = "", array $lore = [], array $enchants = [], string $nbt = "", bool $tryCustom = true){
		return self::getItem($item, $customName, $lore, $enchants, $nbt, $tryCustom);
	}
	
	public static function destructItem(Item $item) : array{
		//Item::hasEnchantments() checks the presence of the Item::TAG_ENCH tag, \true even if there are not enchantments in it
		//$item->getName() shortcut for $item->hasCustomName() ? $item->getCustomName() : $item->getVanillaName()
		if(!self::isInit()){
			self::init();
		}
		return [
		    "name" => $item->getName(),
		    "id" => $item->getId(),
		    "damage" => $item->getDamage(),
		    "count" => $item->getCount(),
		    "nbt" => $item->hasCompoundTag() ? utf8_encode(self::$nbtSerializer->writeCompressed($item->getNamedTag())) : ""
		];
	}
	
	public static function constructItem(array $item) : Item{
		$name = $item["name"] ?? "";
		$id = $item["id"] ?? Item::AIR;
		$damage = $item["damage"] ?? 0;
		$count = $item["count"] ?? 0;
		$enchants = $item["enchants"] ?? [];
		$nbt = $item["nbt"] ?? "";
		return self::getItem($id . ":" . $damage, $name, [], [], $nbt);
	}
	
	/**
	 * Helper function to get an Item with a custom name, lore and given enchants.
	 *
	 * You can use various item formats, such as int: int, string: int,
	 * and even string: string for potions. Potion string and metas
	 * are automatically mapped to their equivalents. 
	 *
	 * Valid potion metas:
	 * <ul>
	 * <li>mundane, long_mundane, thick, awkward, night_vision,</li>
	 * <li>long_night_vision, invisibility, long_invisibility, leaping,</li>
	 * <li>long_leaping, strong_leaping, fire_resistance,</li>
	 * <li>long_fire_resistance, swiftness, long_swiftness, strong_swiftness</li>
	 * <li>slowness, long_slowness, water_breathing, long_water_breathing,</li>
	 * <li>healing, strong_healing, harming, strong_harming, poison,</li>
	 * <li>long_poison, regeneration, long_regeneration,</li>
	 * <li>strong_regeneration, weakness, long_weakness, wither</li>
	 * </ul>
	 *
	 * @param int|string $item For reference, use ItemIds constants or item strings.
	 * @param string $customName A custom name, if empty will use the vanilla name.
	 * @param string[] $lore Up to 4 lore entries
	 * @param array $enchants A list of enchants in the form name: level. Both vanilla and custom enchants works, so do IDs and stringy enchants.
	 * @param string $nbt UTF8-encoded zlib_encode()'d binary data
	 * @param bool $tryCustom Set to false if called from CustomItems. Prevents recursion errors
	 *
	 * @return Item|Item[] The Item instance, or Item[] (custom name, lore, enchants and NBT not supported in this latter case)
	 */
	public static function getItem($item, string $customName = "", array $lore = [], array $enchants = [], string $nbt = "", bool $tryCustom = true){
		if(is_int($item)){
			$i = ItemFactory::get($item, 0, 1);
		}elseif(is_string($item)){
			
			if($tryCustom){
			    if(self::$gemContainersInit){
			    	if(isset(self::$gemContainers[mb_strtolower(str_replace(" ", "_", $item))])){
			    		$i = self::getGemContainer(mb_strtolower(str_replace(" ", "_", $item)));
			    	}
			    }
				# \x28 = opening parenthesis
				/** @var int */
				$pos = ($pos = strpos($item, "\x28")) === false ? 0 : $pos + 1;
				
				# \x29 = closing parenthesis
				if($pos !== 0){ //or $pos !== $item
				    /** @var string[] */
				    $args = explode(", ", str_replace("\x29", "", substr($item, $pos)));
				    /** @var string */
				    $noargs = str_replace("\x28", "", substr($item, 0, $pos));
				}else{
					$args = [];
					$noargs = $item;
				}
				   
				$methodName = "get";
				foreach(explode("_", $noargs) as $part){
					$methodName .= ucfirst(mb_strtolower($part));
				}
				if(method_exists(CustomItems::class, $methodName)){
					//Type casting
					foreach($args as $i => $arg){
						if(is_numeric($arg)){
							if(strpos($arg, ".") !== false){
								$args[$i] = floatval($arg);
							}else{
								$args[$i] = intval($arg);
							}
						}elseif($arg === "true"){
							$args[$i] = true;
						}elseif($arg === "false"){
							$args[$i] = false;
						}else{
				    	}
				    }
				    $i = CustomItems::{$methodName}(...$args);
				}
			}
			
			if(!isset($i)){
				try{
					$id = -1;
					$meta = 0;
					if(stripos($item, ":") !== false){
						$parts = explode(":", $item);
						$id = $parts[0];
						$meta = (int) $parts[1];
						switch($parts[0]){
							case (is_numeric($id) ? ((int) $id === Item::POTION) : (strcasecmp($id, "potion") === 0)):
								$constants = (new \ReflectionClass(Potion::class))->getConstants();
								foreach($constants as $name => $value){
									if(strcasecmp($name, (string) $meta) === 0){
										$meta = $value;
										break 2;
									}
								}
								break;
						}
					}
					if($id === -1){
						$i = ItemFactory::fromString($item);
					}else{
						if(!is_numeric($id)){
							try{
								$i = ItemFactory::fromString($id);
							}catch(\InvalidArgumentException $e){
								assert(false, "ItemFactory should never throw an exception in this condition");
							}
						}
						if(is_numeric($id)){
							$id = (int) $id;
						}else{
							$id = $i->getId();
						}
						$i = ItemFactory::get($id, $meta, 1);
					}
				}catch(\InvalidArgumentException $e){
				}
			}
		}
		if(!isset($i) || (!($i instanceof Item) && !is_array($i))){
			Server::getInstance()->getLogger()->error("Could not parse " . strval($item) . " as a valid item");
			$i = ItemFactory::get(Item::AIR); //null item
			return $i;
		}
		if(is_array($i)){
			return $i;
		}
		
		if($customName !== ""){
			$i->setCustomName(TextFormat::colorize("&r" . $customName . "&r"));
		}
		if(!empty($lore)){
			$lore = array_map(function(string $entry) : string{
				return TextFormat::colorize("&r" . $entry);
			}, $lore);
			$i->setLore($lore);
		}
		if(!empty($enchants)){
			self::addEnchantments($i, $enchants);
		}
		if($nbt !== ""){ 
			if(!self::isInit()){
				self::init();
			}
			
			$tags = null;
			try{
				$tags = self::$nbtSerializer->readCompressed(utf8_decode($nbt));
			}catch(\InvalidArgumentException | \UnexpectedValueException $e){
				Server::getInstance()->getLogger()->error($e);
			}finally{
				if(isset($tags) && $tags instanceof CompoundTag){ //can be an array
				    $nbt = $i->getNamedTag();
					foreach($tags->getValue() as $tag){
						//The custom name, lore and enchants passed to __METHOD__ override NBT.
						if($tag->getName() === Item::TAG_DISPLAY){
							$customNameTag = $tag->getTag(Item::TAG_DISPLAY_NAME);
							if($customNameTag !== null && $customName !== ""){
								continue;
							}
							$loreTag = $tag->getTag(Item::TAG_DISPLAY_LORE);
							if($loreTag !== null && !empty($lore)){
								continue;
							}
						}
						if($tag->getName() === Item::TAG_ENCH && ($enchantList = $tag->getValue()) instanceof ListTag && count($i->getEnchantments()) > 0){
							foreach($enchantList as $key => $enchant){
								if($enchant instanceof CompoundTag){
									foreach($enchant->getValue() as $tag){
										$id = $tag->getShort("id");
										$level = $tag->getShort("lvl");
										if($id !== null && $level !== null){
											$enchantment = $i->getEnchantment($id);
											if($enchantment !== null){
												continue;
											}
										}
									}
								}
							}
						}
						$nbt->setTag($tag);
					}
					$i->setNamedTag($nbt);
				}
			}
		}
		return $i;
	}
	
	/**
	 * Helper function to create enchantment instances.
	 *
	 * @param array $enchants string (enchantName) => int (level)
	 *
	 * @return EnchantmentInstance[]
	 */
	public static function getEnchantments(array $enchants) : array{
		$instances = [];
		foreach($enchants as $enchantName => $level){
			$enchantment = CustomEnchants::getEnchantmentByName($enchantName);
			if($enchantment === null){
				$list = (new \ReflectionClass(Enchantment::class))->getConstants();
				foreach($list as $enchant => $enchantId){
					if(mb_strtolower(str_replace("_", "", $enchantName)) === mb_strtolower(str_replace(" ", "", $enchant))){
						$enchantment = CustomEnchants::getEnchantment($enchantId);
					}
				}
				if($enchantment === null){
					continue; //Invalid
				}
			}
			$instance = new EnchantmentInstance($enchantment, (int) $level);
			$instances[] = $instance;
		}
		return $instances;
	}
	
	/**
	 * Helper function to apply enchantments to an item.
	 *
	 * @param Item &$item
	 * @param array $enchants
	 */
	public static function addEnchantments(Item &$item, array $enchants) : void{
		if($item->getId() === Item::BOOK){
			$item = ItemFactory::get(Item::ENCHANTED_BOOK, $item->getDamage(), $item->getCount());
		}
		foreach(self::getEnchantments($enchants) as $e){
			$item->addEnchantment($e);
		}
		$CE = Server::getInstance()->getPluginManager()->getPlugin("CustomEnchants");
		$CE->updateItemDescription($item);
	}
	
}