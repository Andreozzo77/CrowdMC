<?php

declare(strict_types=1);

namespace kenygamer\Core;

use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\FallingSand; # Fallabe is block
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\Painting;
use pocketmine\entity\object\PaintingMotive;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\entity\Human;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\RemoteConsoleCommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\OfflinePlayer;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Server;
use pocketmine\level\format\Chunk;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Sword;
use pocketmine\item\Shovel;
use pocketmine\item\Pickaxe;
use pocketmine\item\Axe;
use pocketmine\item\Hoe;
use pocketmine\item\TieredTool;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\lang\TranslationContainer;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;
use kenygamer\Core\vehicle\VehicleBase;
use kenygamer\Core\entity\BaseBoss;
use kenygamer\Core\entity\EasterEgg;
use kenygamer\Core\task\WingsTask;
use kenygamer\Core\util\FishingLootTable;
use kenygamer\Core\util\ArmorTypes;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\util\ImageUtils;
use kenygamer\Core\util\RestorableInventoryManager;
use kenygamer\Core\task\RainbowArmorTask;
use kenygamer\Core\task\TopPlayersTask;
use kenygamer\Core\text\FloatingText;
use kenygamer\Core\inventory\SaveableInventory;
use kenygamer\Core\block\Cauldron;
use kenygamer\Core\item\ChainHelmet;
use kenygamer\Core\item\ChainChestplate;
use kenygamer\Core\item\ChainLeggings;
use kenygamer\Core\item\ChainBoots;
use kenygamer\Core\item\NetheriteHelmet;
use kenygamer\Core\item\NetheriteChestplate;
use kenygamer\Core\item\NetheriteLeggings;
use kenygamer\Core\item\NetheriteBoots;
use kenygamer\Core\block\Anvil;
use kenygamer\Core\block\Beacon;
use kenygamer\Core\block\RailBlock;
use kenygamer\Core\block\HopperBlock;
use kenygamer\Core\block\EnchantingTable;
use kenygamer\Core\block\ShulkerBox;
use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\clipboard\ClipboardManager;
use kenygamer\Core\clipbord\ClipboardException;
use kenygamer\Core\entity\FireworksEntity;
use kenygamer\Core\brewing\BrewingManager;
use kenygamer\Core\item\Shield;
use kenygamer\Core\item\FireworksItem;
use kenygamer\Core\item\ArmorStandItem;
use kenygamer\Core\item\Trident;
use kenygamer\Core\item\FilledMap;
use kenygamer\Core\item\EmptyMap;
use kenygamer\Core\item\EnchantedBook;
use kenygamer\Core\item\Elytra;
use kenygamer\Core\item\MinecartItem;
use kenygamer\Core\item\FishingRod;
use kenygamer\Core\block\SlimeBlock;
use kenygamer\Core\block\BrewingStandBlock;
use kenygamer\Core\tile\BeaconTile;
use kenygamer\Core\tile\HopperTile;
use kenygamer\Core\tile\BrewingStand;
use kenygamer\Core\tile\ShulkerBoxTile;
use kenygamer\Core\util\SQLiteConfig;
use kenygamer\Core\entity\Minecart;
use kenygamer\Core\bedwars\BedWarsArena;
use kenygamer\Core\bedwars\BedWarsTask;
use kenygamer\Core\bedwars\BedWarsManager;
use falkirks\minereset\Mine;
use LegacyCore\Events\Area;
use LegacyCore\Commands\Area\ProtectedArea;
use LegacyCore\Commands\RankUp;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\session\PlayerManager;
use BlockHorizons\BlockPets\pets\BasePet;
use revivalpmmp\pureentities\entity\BaseEntity;

/**
 * @class Main2
 * @package kenygamer\Core
 */
abstract class Main2{
    public const SKIN_WIDTH_MAP = [
        64 * 32 * 4 => 64,
        64 * 64 * 4 => 64,
        128 * 128 * 4 => 128
    ];

    public const SKIN_HEIGHT_MAP = [
        64 * 32 * 4 => 32,
        64 * 64 * 4 => 64,
        128 * 128 * 4 => 128
    ];
	
	private const WINGS_COLOR_PRESET_2 = [
	   "Dark Red", "Red", "Gold", "Golden Rod", "Yellow", "Dark Green", "Green", "Aqua", "Dark Aqua", "Dark Blue",
	   "Blue", "Light Purple", "Dark Purple", "White", "Gray", "Dark Gray", "Black"
	];
	
	public const VANILLA_ENCHANT_LIST = [
	   Enchantment::FEATHER_FALLING, Enchantment::RESPIRATION, Enchantment::DEPTH_STRIDER,
	   Enchantment::SHARPNESS, Enchantment::KNOCKBACK, Enchantment::FIRE_ASPECT, Enchantment::EFFICIENCY,
	   Enchantment::SILK_TOUCH, Enchantment::UNBREAKING, Enchantment::FORTUNE, Enchantment::POWER, Enchantment::PUNCH,
	   Enchantment::PROTECTION
	];
	
	public const WINGS_COLOR_PRESET = [
	    TextFormat::DARK_RED => 11141120,
	    TextFormat::RED => 16733525,
	    TextFormat::GOLD => 16755200,
	    TextFormat::ESCAPE . "g" => 14329120, 
	    TextFormat::YELLOW => 16777045,
	    TextFormat::DARK_GREEN => 43520,
	    TextFormat::GREEN => 5635925,
	    TextFormat::AQUA => 5636095,
	    TextFormat::DARK_AQUA => 43690,
	    TextFormat::DARK_BLUE => 170,
	    TextFormat::BLUE => 5592575,
	    TextFormat::LIGHT_PURPLE => 16733695,
	    TextFormat::DARK_PURPLE => 11141290,
	    TextFormat::WHITE => 16777215,
	    TextFormat::GRAY => 11184810,
	    TextFormat::DARK_GRAY => 5592405,
	    TextFormat::BLACK => 0,
	];
	
	public const WINGS_TOGGLE_PRESET = 0;
	public const WINGS_TOGGLE_ADVANCED = 1;
	public const WINGS_TOGGLE_DISABLE = 2;
	
	// DO NOT change these if you have already opened personal mines
	public const PMINE_STARTX = 1000, PMINE_STARTZ = 1000;
	//Those are relative to the original copy position (so compared against clipboard.js values)
	public const PMINE_MINE_STARTX = -5, PMINE_MINE_STARTY = 105, PMINE_MINE_STARTZ = -2140;//X(-4) Z(-2139)
	public const PMINE_MINE_ENDX = 16, PMINE_MINE_ENDY = 3, PMINE_MINE_ENDZ = -2159;//X(17)
	public const PMINE_WORLD = "prison";
	public const PMINE_MINE = "pmine_{player}";
	public const PMINE_AREA = "pmine_{player}";
	public const PMINE_CLIPBOARD = "pmine";
	
	public const BEDWARS_MODE_NORMAL = 0;
	public const BEDWARS_MODE_CUSTOM = 1;
	
	/** @var Config */
	public static $wings, $pmines, $cosmetics, $xuids;
	/** @var array */
	public static $shop, $topPlayersCache = [];
	/** @var BedWarsManager|null */
	private static $bedWarsManager = null;
	/** @var array */
	public static $pmineGenerationLock = [];
	/** @var string[] */
	public static $oldSkin = [];
	
	/**
	 * Called after Main::onLoad()
	 */
	public static function onLoad() : void{
		$plugin = Main::getInstance();
		//onLoad() is called before plugin commands are registered, so yey we can unregister some defaults

		$map = $plugin->getServer()->getCommandMap();
		$unregisterCommands = [
		   "give", "teleport", "say", "particle", "list", "effect", "kick", "difficulty", "pardon-ip", "ban-ip", "banlist", "me", "time", "kill", "tell", "gamemode", "seed", "transferserver", "version", "stop", "plugins"
		];
		foreach($unregisterCommands as $cmd){
			$map->unregister($map->getCommand($cmd));
		}
		ItemUtils::initGemContainers();
		SaveableInventory::init();
		
		Entity::registerEntity(FireworksEntity::class, true);
		//Entity::registerEntity(Minecart::class, true);
		FishingLootTable::init();
		$items = [
			new ChainHelmet(), new ChainChestplate(), new ChainLeggings(), new ChainBoots(), new FireworksItem(), new EnchantedBook(), new ArmorStandItem(), new Elytra(), new Trident(), new Shield(), new FishingRod(), new FilledMap(), new EmptyMap() //new MinecartItem()
		];
		foreach($items as $item){
			ItemFactory::registerItem($item, true);
			Item::addCreativeItem($item);
		}
		
		/*ItemFactory::registerItem(new Item(Item::NETHERITE_INGOT, 0, "Netherite"));
		ItemFactory::registerItem(new Sword(Item::NETHERITE_SWORD, 0, "Netherite Sword", TieredTool::TIER_NETHERITE));
		ItemFactory::registerItem(new Shovel(Item::NETHERITE_SHOVEL, 0, "Netherite Shovel", TieredTool::TIER_NETHERITE));
		ItemFactory::registerItem(new Pickaxe(Item::NETHERITE_PICKAXE, 0, "Netherite Pickaxe", TieredTool::TIER_NETHERITE));
		ItemFactory::registerItem(new Axe(Item::NETHERITE_AXE, 0, "Netherite Axe", TieredTool::TIER_NETHERITE));
		ItemFactory::registerItem(new Hoe(Item::NETHERITE_HOE, 0, "Netherite Hoe", TieredTool::TIER_NETHERITE));
		ItemFactory::registerItem(new NetheriteHelmet());
		ItemFactory::registerItem(new NetheriteChestplate());
		ItemFactory::registerItem(new NetheriteLeggings());
		ItemFactory::registerItem(new NetheriteBoots());
		*/
		$manager = new BrewingManager();
		$manager->init();
		
		$blocks = [
			new BrewingStandBlock(), new Anvil(), new SlimeBlock(),
			new HopperBlock(), new EnchantingTable(), new Beacon(),
			new ShulkerBox(), new Cauldron(),// new RailBlock()
		];
		foreach($blocks as $block){
			BlockFactory::registerBlock($block, true);
		}
		
		$tiles = [
			HopperTile::class, BrewingStand::class, BeaconTile::class,
			ShulkerBoxTile::class
		];
		foreach($tiles as $tile){
			Tile::registerTile($tile);
		}
	}
	
	/**
	 * Called after Main::onEnable()
	 */
	public static function onEnable() : void{
		$plugin = Main::getInstance();
		
		self::$wings = new Config($plugin->getDataFolder() . "wings.js", Config::JSON);
		$plugin->getScheduler()->scheduleRepeatingTask(new RainbowArmorTask(), 5);
		
		new RestorableInventoryManager($plugin);
		self::$bedWarsManager = new BedWarsManager($plugin);

		
		new ClipboardManager($plugin);
		//if(!is_dir($plugin->getDataFolder() . ClipboardManager::CLIPBOARD_PATH . "pmine")){
		if(true){
			$phar = \Phar::running(\true);
			if($phar !== ""){
				$src = $phar . "/resources/" . ClipboardManager::CLIPBOARD_PATH . "pmine";
			}else{
				$src = $plugin->getServer()->getDataPath() . "plugins/" . $plugin->getName() . "/resources/" . ClipboardManager::CLIPBOARD_PATH . "pmine";
			}
			if(!is_dir($src)){
				$plugin->getLogger()->error($src . " not found. This will throw an error when /pmine is used");
			}else{
				//Parent directory made by ClipboardManager::__construct()
				$plugin->recursiveCopy($src, $plugin->getDataFolder() . ClipboardManager::CLIPBOARD_PATH . "pmine");
			}
		}
		
		self::$pmines = new SQLiteConfig($plugin->getDataFolder() . "server.db", "pmines");
		self::$cosmetics = new SQLiteConfig($plugin->getDataFolder() . "server.db", "cosmetics");
		self::$shop = new SQLiteConfig($plugin->getDataFolder() . "server.db", "shop");
		self::$xuids = new SQLiteConfig($plugin->getDataFolder() . "server.db", "xuids");
		
		FloatingText::init($plugin);
		
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
			self::clearGarbageEntities();
		}), 20);
		$plugin->scheduleDelayedCallbackTask(function(){
			self::$topPlayersCache = array_keys(array_slice(self::getAllStatuses(), 0, 5));
		}, 0);
	}
	
	/**
	 * Called after Main::onDisable()
	 */
	public static function onDisable() : void{
		$plugin = Main::getInstance();
		if(!($plugin instanceof Main)){
			return;
		}
		$property = new \ReflectionProperty(PlayerManager::class, "sessions");
        $property->setAccessible(true);
        foreach($property->getValue() as $uuid => $session){
        	$session->removeWindow();
        }
            
		self::$wings->save();
		self::$pmines->save();
		self::$cosmetics->save();
		self::$xuids->save();
		self::$shop->save();
		SaveableInventory::saveAll();
		self::$bedWarsManager = null;
	}
	
	/**
	 * @return BedWarsManager|null
	 */
	public static function getBedWarsManager() : ?BedWarsManager{
		return self::$bedWarsManager;
	}
	
	public static function displayMemory() : void{
		echo "Line #" . (debug_backtrace()[0]["line"]) . ": " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB" . PHP_EOL;
	}
	
	/**
	 * @param string $color
	 * @return int
	 */
	private static function getWingsColorIndexFromString(string $color) : int{
		return array_search($color, self::WINGS_COLOR_PRESET_2);
	}
	
	/**
	 * @param int $index
	 * @return string
	 */
	public static function getWingsColorFormatFromIndex(int $index) : string{
		$colors = array_keys(self::WINGS_COLOR_PRESET);
		return $colors[$index];
	}
	
	/**
	 * @param Player|string $player
	 * @return array
	 */
	public static function getWingsShape($player) : array{
		if($player instanceof Player){
			$player = $player->getName();
		}
		$shape = self::$wings->getNested($player . ".advanced", []);
		if(empty($shape)){
			for($i = 0; $i < count(WingsTask::WINGS_SHAPE); $i++){
				$shape[$i] = [];
				for($j = 0; $j < strlen(WingsTask::WINGS_SHAPE[0]); $j++){
					$shape[$i][$j] = self::getWingsColorIndexFromString("White");
				}
			}
			self::$wings->setNested($player . ".advanced", $shape);
		}
		return $shape;
	}
	
	/**
	 * @param Player $player
	 * @param ?string $goback Null or a sort-of callable (just the method name)
	 */
	private static function wingsSaved(Player $player, ?string $goback) : void{
		$form = new ModalForm(function(Player $player, ?bool $home) use($goback){
			if(is_bool($home)){
				if($home || is_null($goback)){
					self::wingsHome($player);
				}else{
					self::$goback($player);
				}
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->setContent(LangManager::translate("wings-saved", $player));
		$form->setButton1(LangManager::translate("continue", $player));
		$form->setButton2(LangManager::translate("goback", $player));
		$player->sendForm($form);
			
	}
	
	/**
	 * @param Player $player
	 */
	private static function wingsSize(Player $player) : void{
		$opts = ["1.0", "1.1", "1.2", "1.3", "1.4"];
		$form = new CustomForm(function(Player $player, ?array $data) use($opts){ //custom forms always transmit back an array 
			if(is_array($data)){
				$size = $data[0];
				self::$wings->setNested($player->getName() . ".size", $opts[$size]);
				self::wingsSaved($player, "wingsSize");
			}elseif($player->isOnline()){
				self::wingsHome($player);
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->addStepSlider(LangManager::translate("wings-size", $player), $opts, array_search(self::$wings->getNested($player->getName() . ".size", "1.0"), $opts));
		$player->sendForm($form);
	}
	
	/**
	 * @param Player $player
	 */
	private static function wingsColorAdvanced(Player $player) : void{
		$form = new SimpleForm(function(Player $player, ?int $layer){
			if(is_int($layer)){
				/** @var string */
				$llayer = WingsTask::WINGS_SHAPE[$layer];
				/** @var int Number of X's in the layer */
				$count = substr_count($llayer, "X");
				
				$shape = self::getWingsShape($player);
				$llayercopy = $llayer;
				$llayer = "";
				$j = 0;
				for($i = 0; $i < strlen($llayercopy); $i++){
					$substr = $llayercopy[$i];
					if($substr === "X"){
						$llayer .= self::getWingsColorFormatFromIndex($shape[$layer][$j++]) . "X";
					}else{
						$llayer .= " ";
					}
				}
				
				$form = new CustomForm(function(Player $player, ?array $data) use($layer){
					if(is_array($data)){
						array_shift($data);
						/** @var int[] */
						$colors = $data;
						self::$wings->setNested($player->getName() . ".advanced." . $layer, $colors);
						self::wingsSaved($player, "wingsColorAdvanced");
					}elseif($player->isOnline()){
						self::wingsColorAdvanced($player);
					}
				});
				$form->setTitle(LangManager::translate("wings-title", $player));
				$form->addLabel(LangManager::translate("wings-layer-edit", $player, $layer + 1, $llayer));
				for($i = 0; $i < $count; $i++){
					$form->addDropdown(strval($i + 1), self::WINGS_COLOR_PRESET_2, self::getWingsShape($player)[$layer][$i]);
				}
				$player->sendForm($form);
			}elseif($player->isOnline()){
				self::wingsHome($player);
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->setContent(LangManager::translate("wings-color-advanced", $player));
		for($i = 0; $i < count(WingsTask::WINGS_SHAPE); $i++){
			$form->addButton(LangManager::translate("wings-layer", $player, $i + 1));
		}
		$player->sendForm($form);
	}
	
	/**
	 * @param Player $player
	 */
	private static function wingsColorPreset(Player $player) : void{
		$form = new CustomForm(function(Player $player, ?array $data){
			if(is_array($data)){
                $color = $data[0];
				self::$wings->setNested($player->getName() . ".preset", $color);
				self::wingsSaved($player, "wingsColorPreset");
			}elseif($player->isOnline()){
				self::wingsHome($player);
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->addDropdown(LangManager::translate("wings-color-preset", $player), self::WINGS_COLOR_PRESET_2, self::$wings->getNested($player->getName() . ".preset", 0));
		$player->sendForm($form);
	}
	
	/**
	 * @param Player $player
	 */
	private static function wingsToggle(Player $player) : void{
		$form = new CustomForm(function(Player $player, ?array $data){
			if(is_array($data)){
				$toggle = $data[0];
				self::$wings->setNested($player->getName() . ".toggle", $toggle);
				self::wingsSaved($player, "wingsToggle");
			}elseif($player->isOnline()){
				self::wingsHome($player);
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->addDropdown(LangManager::translate("wings-toggle", $player), [LangManager::translate("wings-toggle-1", $player), LangManager::translate("wings-toggle-2", $player), LangManager::translate("wings-toggle-3", $player)], self::$wings->getNested($player->getName() . ".toggle", 0));
		$player->sendForm($form);
	}
	
	/**
	 * @param Player $player
	 */
	public static function wingsHome(Player $player) : void{
		$form = new SimpleForm(function(Player $player, ?int $opt){
			if(is_int($opt)){
				switch($opt){
					case 0:
					   self::wingsSize($player);
					   break;
					case 1:
					   self::wingsColorPreset($player);
					   break;
					case 2:
					   self::wingsColorAdvanced($player);
					   break;
					case 3:
					   self::wingsToggle($player);
					   break;
				}
			}
		});
		$form->setTitle(LangManager::translate("wings-title", $player));
		$form->addButton(LangManager::translate("wings-size", $player));
		$form->addButton(LangManager::translate("wings-color-preset", $player));
		$form->addButton(LangManager::translate("wings-color-advanced", $player));
		$form->addButton(LangManager::translate("wings-toggle", $player));
		$player->sendForm($form);
	}
	
	/**
	 * @param string $player
	 */
	public static function personalMineGenerationCallback(string $player){
		if(self::$pmines === null || !self::$pmines->exists($player)){
			throw new \BadMethodCallException("Unexisting personal mine of " . $player);
		}
		unset(self::$pmineGenerationLock[$player]);
		
		list($minX, $minY, $minZ, $maxX, $maxY, $maxZ) = self::$pmines->get($player);
		//So it does not let intruders in/PVP off
		new ProtectedArea(str_replace("{player}", $player, self::PMINE_AREA), ["edit" => true, "god" => true, "touch" => true], new Vector3($minX, $minY, $minZ), new Vector3($maxX, $maxY, $maxZ), self::PMINE_WORLD, [], Area::getInstance()->cmd);
		Area::getInstance()->cmd->saveAreas();
		
		$info = ClipboardManager::getInstance()->getClipboardInfo(self::PMINE_CLIPBOARD);
		list($originalMinX, $originalMinY, $originalMinZ, $originalMaxX, $originalMaxY, $originalMaxZ) = $info;
		
		$mineManager = Main::getInstance()->getPlugin("MineReset")->getMineManager();
		$mine = str_replace("{player}", mb_strtolower($player), self::PMINE_MINE);
		
		//Get the minimum and maximum values ​​to subtract safely without abs()
		$mineMinX = min(self::PMINE_MINE_STARTX, self::PMINE_MINE_ENDX);
		$mineMinY = min(self::PMINE_MINE_STARTY, self::PMINE_MINE_ENDY);
		$mineMinZ = min(self::PMINE_MINE_STARTZ, self::PMINE_MINE_ENDZ);
		$mineMaxX = max(self::PMINE_MINE_STARTX, self::PMINE_MINE_ENDX);
		$mineMaxY = max(self::PMINE_MINE_STARTY, self::PMINE_MINE_ENDY);
		$mineMaxZ = max(self::PMINE_MINE_STARTZ, self::PMINE_MINE_ENDZ);
		
		//Normalize the start and end coordinates of this mine using the coordinates relative to
		// where this clipboard was created
		$mineRelMinX = $mineMinX - $originalMinX;
		$mineRelMinY = $mineMinY - $originalMinY;
		$mineRelMinZ = $mineMinZ - $originalMinZ;
		
		$mineRelMaxX = $mineRelMinX + ($mineMaxX - $mineMinX);
		$mineRelMaxY = $mineRelMinY + ($mineMaxY - $mineMinY);
		$mineRelMaxZ = $mineRelMinZ + ($mineMaxZ - $mineMinZ);
		
		//Creates the mine using the magic offsetSet method of the MineManager class
		$mineManager[$mine] = new Mine(
		   $mineManager,
		   new Vector3($minX + $mineRelMinX, $minY + $mineRelMinY, $minZ + $mineRelMinZ),
		   new Vector3($minX + $mineRelMaxX, $minY + $mineRelMaxY, $minZ + $mineRelMaxZ),
		   self::PMINE_WORLD,
		   $mine,
		   [Block::EMERALD_BLOCK => 50, Block::PRISMARINE => 40, Block::SEA_LANTERN => 10]
		);
		
		//Teleport the player to their personal mine
		$player = Server::getInstance()->getPlayerExact($player);
		if($player instanceof Player){
			$player->chat("/pmine");
		}
	}
	
	public static function pasteCallback(string $player) : void{
		$player = Server::getInstance()->getPlayerExact($player);
		if($player instanceof Player){
			$player->sendMessage("clipboard-pasted");
		}
	}
	
	/* Cosmetics */
	
	/**
	 * Applies the cape to the Player skin.
	 *
	 * @param Player $player
	 * @param string $cape_name
	 */ 
	public static function updateCape(Player $player, string $cape_name = ""){
		$cape = Main::getInstance()->designs[$cape_name === "" ? self::$cosmetics->getNested($player->getName() . ".cape") : $cape_name] ?? null;
		if($cape !== null){
			$oldSkin = $player->getSkin();
			$newSkin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
			$player->setSkin($newSkin);
			$player->sendSkin($player->getServer()->getOnlinePlayers());
		}
	}

	
	/**
	 * Applies the cosmetic to the Player skin. This can be a suit, or a hat.
	 * $overlay If set to true, it will be a hat. Otherwise it will not overlay the skin geometry, 
	 * rather just replace the entire skin geometry.
	 *
	 * @param Player $player
	 * @param string $cosmetic_name The cosmetic name, including prefix.
	 * @param bool $overlay true for hat, false for cape
	 */ 
	public static function updateCosmetic(Player $player, string $cosmetic_name = "", bool $overlay) : void{
		if($cosmetic_name === ""){
			//TODO
			$cosmetics = self::$cosmetics->get($player->getName());
			if($cosmetics === false || ($overlay && !isset($cosmetics["hat"])) || (!$overlay && !isset($cosmetics["cape"]))){
				return;
			}
			if($overlay){
				$cosmetic_name = $cosmetics["hat"];
			}else{
				$cosmetic_name = $cosmetics["cape"];
			}
		}
	
        $skin = $player->getSkin();
		$skinData = $skin->getSkinData();
		$pluginDir = Main::getInstance()->getDataFolder();
		//This temp file will be used to save both the old and new skin.
        $skinTmp = $pluginDir . "skins/" . $player->getName() . "_tmp.png";
		$path = $pluginDir . "skins/" . $cosmetic_name . ".png";
		var_dump(compact("cosmetic_name", "overlay"));
        if(!file_exists($path)){
            Main::getInstance()->getLogger()->error($path . " does not exist");
			return;
        }
		
		//Create temporal image from skin bytes
		$skinData = $player->getSkin()->getSkinData();
		 
        $size = strlen($skinData);
        if (!in_array((int) $size, Skin::ACCEPTED_SKIN_SIZES)){
			//Invalid skin?
			var_dump("Size $size is invalid");
            return;
        }
        $width = self::SKIN_WIDTH_MAP[$size];
        $height = self::SKIN_HEIGHT_MAP[$size];
        $img = imagecreatetruecolor($width, $height);
        if($img === false){
			Main::getInstance()->getLogger()->error("Unsupported gd version (need > 2.0.1)");
            return;
        }

        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
		$i = 0;
		var_dump($height . ", " . $width);
        for($y = 0; $y < $height; $y++){
            for($x = 0; $x < $width; $x++){
                $r = ord($skinData[$i]);
                $i++;
                $g = ord($skinData[$i]);
                $i++;
                $b = ord($skinData[$i]);
                $i++;
                $a = 127 - intdiv(ord($skinData[$i]), 2);
                $i++;
                $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
                imagesetpixel($img, $x, $y, $color);
            }
        }
        imagesavealpha($img, true);
			
        if($img == null){
			//We might never get here, as it apparently concerns only JPEG files
			//	as they cannot handle transparency. To make "transparency" in JPEG,
			// Alpha channel is dropped, and pixels are dimmed.
			var_dump("Image is null");
            return;
        }
        imagepng($img, $skinTmp);
	
		if($overlay){
			var_dump("Overlay");
	        $size = getimagesize($path);
	
	        $down = imagecreatefrompng($skinTmp);
	        $upper = null;
	        if($size[0] * $size[1] * 4 == 65536){
	            $upper = ImageUtils::resizeImage($path, 128, 128);
	        }else{
	            $upper = ImageUtils::resizeImage($path, 64, 64);
	        }
			
	        //Remove black color out of the PNG
	        imagecolortransparent($upper, imagecolorallocatealpha($upper, 0, 0, 0, 127));
			
	        imagealphablending($down, true);
	        imagesavealpha($down, true);
	        imagecopymerge($down, $upper, 0, 0, 0, 0, $size[0], $size[1], 100);
			
	        imagepng($down, $skinTmp);
		}
		
        $img = @imagecreatefrompng($skinTmp);
		var_dump(gettype($img));
		var_dump($img);
        $skinbytes = "";
        for ($y = 0; $y < $size[1]; $y++) {
            for ($x = 0; $x < $size[0]; $x++) {
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
		$geometryData = Main::getInstance()->models[$cosmetic_name] ?? null;
		if($geometryData === null){
			Main::getInstance()->getLogger()->error("Model " . $cosmetic_name . " does not exist");
		}
		//IMPORTANT: geometry name MUST be the same as the file, otherwise the overlay will not show
        $player->setSkin(
			new Skin($skin->getSkinId(), $skinbytes, $skin->getCapeData(), "geometry." . $cosmetic_name, json_encode($geometryData))
		);
        $player->sendSkin();
		
	}
	
	/**
	 * Returns an array with the status of all players
	 * This is a HEAVY operation.
	 *
	 * @return float[]
	 */
	private static function getAllStatuses() : array{
		$plugin = Main::getInstance();
		if($plugin->getPlugin("LegacyCore") === null){
			return [];
		}
		$fullPath = $plugin->getPlugin("LegacyCore")->getDataFolder() . "player/";
		$manager = $plugin->permissionManager;
		$ranks = RankUp::getRanks();
		$players = array_map(function(string $path) use($fullPath){
			return str_replace([$fullPath, ".yml"], "", $path);
		}, glob($fullPath . "*.yml"));
		
		$max = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1];
		foreach($players as $name){
			$player = $plugin->getServer()->getOfflinePlayer($name);
			
			$entries = [
			    $plugin->myMoney($name),
			    $plugin->getEntry($name, Main::ENTRY_KILLS),
			    $plugin->getEntry($name, Main::ENTRY_DEATHS),
		        $plugin->getEntry($name, Main::ENTRY_BLOCKS_PLACED),
		        $plugin->getEntry($name, Main::ENTRY_BLOCKS_BROKEN),
		        $plugin->getEntry($name, Main::ENTRY_KILL_STREAK),
			    $plugin->getEntry($name, Main::ENTRY_BOUNTY),
			    $plugin->getEntry($name, Main::ENTRY_PRESTIGE),
			    $plugin->getEntry($name, Main::ENTRY_HEADHUNTING),
			    intval(array_search($manager->getPlayerPrefix($player), $ranks))
			];
			foreach($entries as $i => $value){
				if($value > $max[$i]){
					$max[$i] = $value;
				}
			}
		}
		$all = [];
		foreach($players as $name){
			$player = Server::getInstance()->getOfflinePlayer($name);
			$all[$name] = "0";
			
			$entries = [
			    Main::getInstance()->myMoney($name),
			    $plugin->getEntry($name, Main::ENTRY_KILLS),
			    $plugin->getEntry($name, Main::ENTRY_DEATHS),
		        $plugin->getEntry($name, Main::ENTRY_BLOCKS_PLACED),
		        $plugin->getEntry($name, Main::ENTRY_BLOCKS_BROKEN),
		        $plugin->getEntry($name, Main::ENTRY_KILL_STREAK),
			    $plugin->getEntry($name, Main::ENTRY_BOUNTY),
			    $plugin->getEntry($name, Main::ENTRY_PRESTIGE),
			    $plugin->getEntry($name, Main::ENTRY_HEADHUNTING),
			    intval(array_search($manager->getPlayerPrefix($player), $ranks))
			];
			foreach($entries as $i => $value){
				$all[$name] = bcadd(strval($value / $max[$i]), $all[$name]);
			}
		}
		
		arsort($all);
		return $all;
	}
	
	/**
	 * @param Entity $entity
	 * @return bool
	 */
	public static function isEntityExempted(Entity $entity) : bool{
		static $classes = [
			EasterEgg::class, Human::class, BasePet::class, FallingBlock::class, ItemEntity::class, ExperienceOrb::class, Painting::class,
				PaitingMotive::class, Projectile::class, VehicleBase::class
		];
		$exempted = $entity::NETWORK_ID === Entity::CREEPER && $entity->namedtag->hasTag("Bomby", StringTag::class);
		foreach($classes as $class){
			if($entity instanceof $class){
				$exempted = true;
				break;
			}
		}
		return $exempted;
	} 
	
	public static function clearGarbageEntities() : void{
		$plugin = Main::getInstance();
		$levels = Server::getInstance()->getLevels();
		$maxEntityCount = ceil(100 / count($levels));
		
		foreach($levels as $level){
			$entities = $level->getEntities();
			$entityCount = 0;
			$npcCount = 0;
			foreach($entities as $entity){
				//Remove garbage vehicles
				if($entity instanceof VehicleBase){
					if($entity->owner !== ""){
						foreach($levels as $level_){
							foreach($level_->getEntities() as $entity_){
								if($entity_ instanceof VehicleBase && $entity_->owner === $entity->owner && $entity_->getId() !== $entity->getId()){
									//Make sure the vehicle the player just spawned doesn't get removed too
									if($entity->ticksLived > $entity_->ticksLived){
										$remove = $entity;
									}else{
										$remove = $entity_;
									}
									if(!$remove->isClosed()){
										$remove->flagForDespawn();
									}
									break;
								}
							}
						}
					}
				}
				if($level->getFolderName() === "warzone"){
					if($entity instanceof BaseBoss){
						$npcCount++;
						if($npcCount > 3){
							$entity->flagForDespawn();
						}
					}
				}
				
				if(!self::isEntityExempted($entity)){
					$entityCount++;
					if($entity->ticksLived >= 300 * 20){
						$entity->flagForDespawn();
					}
				}
			}
			if($entityCount > $maxEntityCount){
				foreach($entities as $entity){
					if(!self::isEntityExempted($entity)){
						$entity->flagForDespawn();
					}
				}
			}
		}
	}
}