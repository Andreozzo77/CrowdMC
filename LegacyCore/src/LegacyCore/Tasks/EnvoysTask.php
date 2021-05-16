<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\inventory\ChestInventory;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\nbt\NBT;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use CustomEnchants\CustomEnchants\CustomEnchants;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class EnvoysTask extends Task{

    /** @var array */
    public $time = 0;
	/** @var array */
    public $plugin;
    
    public static $last_envoy = null;
    public static $last_envoy_time = null;
    
    /**
     * EnvoysTask constructor.
     * @param Core $plugin
     * @param Player $player
     */
    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
	}
    
	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
    	$level = $this->plugin->getServer()->getLevelByName("wild");
    	if(self::$last_envoy_time !== null && time() - self::$last_envoy_time >= 600){
    		if(($tile = $level->getTile(self::$last_envoy)) instanceof Chest){
    			$tile->close();
    			$level->setBlock(self::$last_envoy, new \pocketmine\block\Air());
    		}
    	}
        switch($this->time) {
			case 300:
            
		    if ($level !== null) {
		    	$x = rand(7500, 8000);
		    	$y = rand(66, 70);
		    	$z = rand(7500, 8000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
				$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
				$chance = rand(1, 9);
                switch($chance) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(1, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["disarming" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 9:
					$item = ItemUtils::get("book", "", [], ["endershift" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(2, $item);
					break;
				}
				$chance2 = rand(1, 7);
                switch($chance2) {
					case 1:
					$item = Item::get(4, 0, \kenygamer\Core\Main::mt_rand(10, 50));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["haste" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 6:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 7:
					$item = Item::get(279, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(138), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bDiamond Axe\n§6Insanity II\n§eRage III\n§eLumberjack I");
					$chest->getInventory()->setItem(5, $item);
					break;
				}
				$chance3 = rand(1, 6);
                switch($chance3) {
					case 1:
					$item = Item::get(264, 0, \kenygamer\Core\Main::mt_rand(10, 20));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["enraged" => 1]);
				 	$chest->getInventory()->setItem(11, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["haste" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 6:
					$item = Item::get(397, 5, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance4 = rand(1, 7);
                switch($chance4) {
					case 1:
					$item = Item::get(1, 0, \kenygamer\Core\Main::mt_rand(10, 64));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 2:
					$item = Item::get(331, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(2, 3));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 5:
					$item = Item::get(265, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(12, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["disarming" => \kenygamer\Core\Main::mt_rand(1, 7)]);
					$chest->getInventory()->setItem(12, $item);
					break;
					case 7:
					$item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(107), 2));
                    $item->setCustomName("§r§eDiamond Sword\n§cShockwave II");
					$chest->getInventory()->setItem(12, $item);
					break;
				}
				$chance5 = rand(1, 5);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(0, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 3:
					$item = Item::get(260, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = ItemUtils::get("mythic_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["driller" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(15, $item);
					break;
				}
				$chance6 = rand(1, 8);
                switch($chance6) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(102)")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
					$chest->getInventory()->setItem(19, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["antiknockback" => 1]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["jetpack" => 1]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 4:
					$item = Item::get(276, 0, 1);
		            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 8));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(141), 4));
                    $item->setCustomName("§r§cFire Sword§r\n§eDemise IV\n§eRage III\n§eBlessed III\n§bLifesteal V");
					$chest->getInventory()->setItem(19, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["overload" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 6:
                    $item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(5, 30));
                    $chest->getInventory()->setItem(19, $item);
                    break;
					case 7:
                    $item = Item::get(296, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(19, $item);
                    break;
					case 8:
					$item = Item::get(283, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 2));
                    $item->setCustomName("§r§eGold Shine Sword\n§6Deathbringer III\n§eObliterate II\n§eBlessed II\n§bLifesteal IV");
					$chest->getInventory()->setItem(19, $item);
					break;
					
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 600:
            
		    if ($level !== null) {
		    	$x = rand(2500, 4500);
		    	$y = rand(66, 70);
		    	$z = rand(2500, 4500);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
				$chance = rand(1, 9);
                switch($chance) {
					case 1:
		        	$item = Item::get(276, 0, 1);
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			    	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 4));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 5));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 5));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(127), 3));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(128), 7));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(132), 2));
			    	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(140), 10));
					$item->setCustomName("§r§l§eHeroic Sword§r\n§cExcalibur V\n§cCorrupt II\n§6Soulbound III\n§6Skill Swipe X\n§6Critical III\n§6Disarmor VII\n§eAutorepair IV\n§eBlessed III\n§eRage V");
			        $chest->getInventory()->setItem(7, $item);
					break;
					case 2:
					$item = ItemUtils::get("rare_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["bleeding" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 4:
					$item = ItemUtils::get("knight_note");
					$chest->getInventory()->setItem(3, $item);
					break;
					case 5:
					$item = Item::get(444, 0, 1);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 6:
					$item = ItemUtils::get("experience_bottle2(102)")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(4, $item);
					break;
					case 7:
					$item = ItemUtils::get("atlas_gem")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(4, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(1, $item);
					break;
					case 9:
					$item = ItemUtils::get("book", "", [], ["shockwave" => 1]);
					$chest->getInventory()->setItem(1, $item);
					break;
				}
				$chance2 = rand(1, 7);
                switch($chance2) {
					case 1:
					$item = ItemUtils::get("red_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 2:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 3:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["haste" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(11, $item);
					break;
					case 5:
					$item = Item::get(12, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["nutrition" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(11, $item);
					break;
					case 7:
					$item = Item::get(57, 0, 64);
					$item2 = Item::get(41, 0, 64);
					$item3 = Item::get(133, 0, 64);
					$chest->getInventory()->setItem(15, $item);
					$chest->getInventory()->setItem(16, $item2);
					$chest->getInventory()->setItem(17, $item3);
					break;
				}
				$chance3 = rand(1, 7);
                switch($chance3) {
					case 1:
					$item = Item::get(264, 0, \kenygamer\Core\Main::mt_rand(10, 20));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = Item::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 3:
					$item = Item::get(278, 0, \kenygamer\Core\Main::mt_rand(0, 1));
		        	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 10));
		        	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(207), 5));
		            $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(212), 7));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(213), 7));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(214), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(215), 3));
		          	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(216), 3));
                    $item->setCustomName("§r§l§eHeroic Pickaxe§r\n§6Soulbound V\n§6Keyplus III\n§6Treasure Hunter III\n§6Money Farm VII\n§6Grind VII\n§6Miner Luck V\n§eAutorepair V\n§eDriller V\n§bHaste V");
				 	$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 5:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 6:
					$item = Item::get(264, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 7:
					$item = Item::get(307, 0, 1);
					$chest->getInventory()->setItem(15, $item);
					break;
				}
				$chance4 = rand(1, 8);
                switch($chance4) {
					case 1:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(5, 15));
					$chest->getInventory()->setItem(16, $item);
					break;
					case 2:
					$item = Item::get(399, 0, \kenygamer\Core\Main::mt_rand(0, 2));
					$chest->getInventory()->setItem(16, $item);
					break;
					case 3:
					$item = Item::get(310, 0, \kenygamer\Core\Main::mt_rand(0, 1));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload VI\n§6Implants III\n§6Tank III");
					$chest->getInventory()->setItem(16, $item);
					break;
					case 4:
					$item = Item::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 1));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
                    $item->setCustomName("§r§bDiamond Chestplate\n§6Overload VI\n§6Tank III\n§eRevive III");
					$chest->getInventory()->setItem(16, $item);
					break;
					case 5:
					$item = Item::get(3, 0, \kenygamer\Core\Main::mt_rand(30, 64));
					$item2 = Item::get(1, 0, \kenygamer\Core\Main::mt_rand(30, 64));
					$chest->getInventory()->setItem(16, $item);
					$chest->getInventory()->setItem(17, $item2);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["backstab" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(16, $item);
					break;
					case 7:
					$item = Item::get(20, 0, \kenygamer\Core\Main::mt_rand(0, 64));
					$chest->getInventory()->setItem(16, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["headhunter" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(16, $item);
					break;
				}
				$chance5 = rand(1, 7);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["shileded" => 1]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 2:
					$item = ItemUtils::get("red_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(19, $item);
					break;
					case 3:
					$item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(10, 20));
					$chest->getInventory()->setItem(19, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["lifesteal" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 5:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(0, 30));
					$item2 = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(20, $item);
					$chest->getInventory()->setItem(21, $item2);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["lifesteal" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(19, $item);
					break;
					case 7:
                    $item = Item::get(296, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(20, $item);
                    break;
				}
				$chance6 = rand(1, 7);
                switch($chance6) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["longbow" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 2:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(22, $item);
					break;
					case 3:
					$item = ItemUtils::get("legendary_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
					$chest->getInventory()->setItem(23, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["longbow" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 5:
					$item = ItemUtils::get("harpy_note");
					$chest->getInventory()->setItem(22, $item);
					break;
					case 6:
					$item = Item::get(276, 0, 1);
		            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 8));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 2));
                    $item->setCustomName("§r§bDiamond Sword§r\n§eObliterate II\n§eRage III\n§eBlessed III\n§bLifesteal V");
					$chest->getInventory()->setItem(23, $item);
					break;
					case 7:
					$item = Item::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(428), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(500), 1));
                    $item->setCustomName("§r§l§6Heroic Chestplate§r\n§cDivine I\n§6Overload VIII\n§6Anti Knockback I\n§6Vitamins I\n§eAutorepair II");
					$chest->getInventory()->setItem(24, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 900:
            
		    if ($level !== null) {
		    	$x = rand(-5000, -8000);
		    	$y = rand(66, 70);
		    	$z = rand(-5000, -8000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
				$chance = rand(1, 3);
                switch($chance) {
					case 1:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
					$chest->getInventory()->setItem(0, $item);
					break;
					case 2:
					$item = Item::get(391, 0, \kenygamer\Core\Main::mt_rand(20, 30));
					$chest->getInventory()->setItem(1, $item);
					break;
					case 3:
					$item = ItemUtils::get("harpy_note");
					$chest->getInventory()->setItem(1, $item);
					break;
				}
				$chance2 = rand(1, 3);
                switch($chance2) {
					case 1:
					$item = Item::get(339, 0, 1);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 2:
					$item = Item::get(354, 0, 1);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 30));
					$chest->getInventory()->setItem(3, $item);
					break;
				}
				$chance3 = rand(1, 4);
                switch($chance3) {
					case 1:
					$item = ItemUtils::get("rare_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(6, $item);
					break;
					case 2:
					$item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 2));
                    $item->setCustomName("§r§bDiamond Sword\n§eBlessed II\n§bLifesteal II");
					$chest->getInventory()->setItem(6, $item);
					break;
					case 3:
					$item = Item::get(262, 0, \kenygamer\Core\Main::mt_rand(20, 30));
					$chest->getInventory()->setItem(6, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["frozen" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(6, $item);
					break;
				}
				$chance4 = rand(1, 5);
                switch($chance4) {
					case 1:
					$item = Item::get(352, 0, \kenygamer\Core\Main::mt_rand(20, 30));
					$chest->getInventory()->setItem(9, $item);
					break;
					case 2:
					$item = Item::get(375, 0, \kenygamer\Core\Main::mt_rand(10, 15));
					$chest->getInventory()->setItem(9, $item);
					break;
					case 3:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(9, $item);
					break;
					case 4:
					$item = Item::get(338, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(9, $item);
					break;
					case 5:
					$item = Item::get(38, 8, \kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(9, $item);
					break;
				}
				$chance5 = rand(1, 3);
                switch($chance5) {
					case 1:
					$item = Item::get(17, 0, \kenygamer\Core\Main::mt_rand(10, 50));
					$chest->getInventory()->setItem(12, $item);
					break;
					case 2:
					$item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(12, $item);
					break;
					case 3:
					$item = Item::get(35, 1, \kenygamer\Core\Main::mt_rand(0, 64));
					$chest->getInventory()->setItem(12, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 1200:
            
		    if ($level !== null) {
		    	$x = rand(-2500, -9000);
		    	$y = rand(70, 72);
		    	$z = rand(-2500, -9000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	self::$last_envoy_time = time();
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 6);
                switch($chance) {
					case 1:
					$item = ItemUtils::get("red_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
				    $item2 = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(30, 50));
				    $item3 = ItemUtils::get("book", "", [], ["demise" => \kenygamer\Core\Main::mt_rand(1, 2)]);
			    	$item4 = ItemUtils::get("book", "", [], ["overload" => \kenygamer\Core\Main::mt_rand(1, 3)]);
				    $item5 = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 8)]);
			        $chest->getInventory()->setItem(1, $item);
			      	$chest->getInventory()->setItem(2, $item2);
			    	$chest->getInventory()->setItem(3, $item3);
				    $chest->getInventory()->setItem(4, $item4);
				    $chest->getInventory()->setItem(5, $item5);
					break;
					case 2:
					$item = ItemUtils::get("legendary_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["backstab" => 1]);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(2, 3));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["armored" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["armored" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(3, $item);
					break;
				}
				$chance2 = rand(1, 7);
                switch($chance2) {
					case 1:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["backstab" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 3:
					$item = Item::get(30, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 4:
					$item = Item::get(56, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 5:
					$item = Item::get(103, 0, \kenygamer\Core\Main::mt_rand(1, 5));
					$chest->getInventory()->setItem(6, $item);
					break;
					case 6:
					$item = Item::get(399, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(7, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["backstab" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(8, $item);
					break;
				}
				$chance3 = rand(1, 7);
                switch($chance3) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(10, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 6:
                    $item = Item::get(65, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(10, $item);
                    break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["soulbound" => 1]);
					$chest->getInventory()->setItem(10, $item);
					break;
				}
				$chance4 = rand(1, 8);
                switch($chance4) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 2:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 40));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 5:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(11, $item);
					break;
					case 7:
					$item = Item::get(261, 0, 1);
                    $item2 = Item::get(262, 0, 64);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(21), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(311), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(317), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 5));
                    $item->setCustomName("§r§eBow\n§6Blaze I\n§6Disarm Protection I\n§eVirus IV\n§eVolley IV\n§bBow Lifesteal V");
                    $chest->getInventory()->setItem(11, $item);
                    $chest->getInventory()->setItem(12, $item2);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(12, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 1800:
            
		    if ($level !== null) {
		    	$x = rand(5500, 10000);
		    	$y = rand(68, 70);
		    	$z = rand(2500, 5000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
				$chance = rand(1, 7);
                switch($chance) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(2, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(2, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(2, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(2, $item);
					break;
					case 6:
                    $item = Item::get(65, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(2, $item);
                    break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["soulbound" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(2, $item);
					break;
				}
				$chance2 = rand(1, 7);
                switch($chance2) {
					case 1:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 2:
					$item = ItemUtils::get("shard_note");
					$chest->getInventory()->setItem(5, $item);
					break;
					case 3:
					$item = Item::get(265, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 5:
			 		$item = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 5)]);
			  		$chest->getInventory()->setItem(5, $item);
					break;
					case 6:
                    $item = Item::get(121, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(5, $item);
                    break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["revive" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(4, $item);
					break;
				}
				$chance3 = rand(1, 5);
                switch($chance3) {
					case 1:
                    $item = Item::get(391, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(6, $item);
					break;
					case 2:
				    $item = Item::get(399, 0, \kenygamer\Core\Main::mt_rand(0, 5));
                    $chest->getInventory()->setItem(6, $item);
					break;
					case 3:
					$item = Item::get(368, 0, \kenygamer\Core\Main::mt_rand(0, 16));
                    $chest->getInventory()->setItem(7, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["cursed" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["cursed" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(6, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 2400:
            
		    if ($level !== null) {
		    	$x = rand(5500, 10000);
		    	$y = rand(68, 70);
		    	$z = rand(2500, 5000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 6);
                switch($chance) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(1, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 10)]);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 6:
                    $item = Item::get(296, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(2, $item);
                    break;
				}
				$chance2 = rand(1, 6);
                switch($chance2) {
					case 1:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 2:
					$item = ItemUtils::get("shard_note");
					$chest->getInventory()->setItem(5, $item);
					break;
					case 3:
					$item = Item::get(265, 0, \kenygamer\Core\Main::mt_rand(10, 30));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 5:
			 		$item = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 6)]);
			  		$chest->getInventory()->setItem(5, $item);
					break;
					case 6:
                    $item = Item::get(121, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(5, $item);
                    break;
				}
				$chance3 = rand(1, 5);
                switch($chance3) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
			    	$item2 = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 4)]);
			    	$item3 = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 5)]);
				    $item4 = ItemUtils::get("shard_note");
				    $item5 = Item::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 1));
			    	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 10));
			        $item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
		            $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 5));
		    	    $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 5));
		    	    $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(414), 5));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 10));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(428), 3));
		         	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(500), 1));
			    	$item5->setCustomName("§r§l§eHeroic Chestplate§r\n§cDivine III\n§6Soulbound V\n§6Vitamins I\n§6Anti Knockback I\n§6Overload X\n§eAutorepair V\n§eRevive V\n§eShrink V");
			        $chest->getInventory()->setItem(6, $item);
				    $chest->getInventory()->setItem(7, $item2);
			    	$chest->getInventory()->setItem(8, $item3);
				    $chest->getInventory()->setItem(9, $item4);
				    $chest->getInventory()->setItem(10, $item5);
					break;
					case 2:
					$item = Item::get(98, 0, \kenygamer\Core\Main::mt_rand(50, 64));
                    $chest->getInventory()->setItem(8, $item);
					break;
					case 3:
                    $item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(6, $item);
                    break;
					case 4:
					$item = Item::get(388, 0, \kenygamer\Core\Main::mt_rand(0, 25));
			    	$item2 = Item::get(368, 0, \kenygamer\Core\Main::mt_rand(0, 16));
			    	$item3 = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(1, 15));
			    	$item4 = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
			    	$item5 = Item::get(466, 0, \kenygamer\Core\Main::mt_rand(0, 5));
			    	$item6 = Item::get(263, 0, \kenygamer\Core\Main::mt_rand(10, 30));
			        $chest->getInventory()->setItem(7, $item);
			     	$chest->getInventory()->setItem(8, $item2);
			    	$chest->getInventory()->setItem(9, $item3);
			    	$chest->getInventory()->setItem(10, $item4);
			    	$chest->getInventory()->setItem(11, $item5);
			    	$chest->getInventory()->setItem(12, $item6);
					break;
					case 5:
					$item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 2));
                    $item->setCustomName("§r§l§4Chaos §6Saber §eSword§r\n§6Deathbringer III\n§eRage II\n§bLifesteal III");
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance4 = rand(1, 7);
                switch($chance4) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(103)")->setCount(\kenygamer\Core\Main::mt_rand(3, 5));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 40));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 5:
					$item = ItemUtils::get("mythic_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["charge" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(15, $item);
					break;
				}
				$chance5 = rand(1, 10);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(103)")->setCount(\kenygamer\Core\Main::mt_rand(3, 5));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 2:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 40));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["gears" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(18, $item);
					break;
					case 5:
					$item = Item::get(312, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
		 			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
		          	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 2));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Leggings\n§6Overload II\n§6Armored II\n§6Heavy II\n§eDrunk IV");
					$chest->getInventory()->setItem(18, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["shrink" => 1]);
                    $chest->getInventory()->setItem(18, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["gears" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(18, $item);
					break;
					case 9:
					$item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(130), 5));
                    $item->setCustomName("§r§l§cUltimate Dragon Sword§r\n§cExcalibur III\n§6Drain V\n§eRage III\n§eBlessed III");
					$chest->getInventory()->setItem(19, $item);
					break;
					case 10:
					$item = ItemUtils::get("atlas_gem")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(20, $item);
					break;
				}
				$chance6 = rand(1, 6);
                switch($chance6) {
					case 1:
					$item = Item::get(386, 0, 1);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["bleeding" => 1]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["freeze" => 1]);
					$chest->getInventory()->setItem(21, $item);
					break;
					case 4:
					$item = ItemUtils::get("knight_note");
					$chest->getInventory()->setItem(20, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["warmer" => 1]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(20, $item);
					break;
				}
				$chance7 = rand(1, 6);
                switch($chance7) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["shrink" => \kenygamer\Core\Main::mt_rand(1, 2)]);
                    $chest->getInventory()->setItem(25, $item);
					break;
					case 2:
					$item = Item::get(57, 0, 30);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 3:
					$item = Item::get(7, 0, 15);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 4:
					$item = Item::get(41, 0, 40);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 5:
					$item = Item::get(170, 0, 10);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 6:
					$item = Item::get(455, 0, 1);
				    $chest->getInventory()->setItem(23, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 2600:
            
		    if ($level !== null) {
		    	$x = rand(-5500, -10000);
		    	$y = rand(68, 70);
		    	$z = rand(-2500, -5000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 6);
                switch($chance) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 2:
					$item = Item::get(276, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(1, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 6:
                    $item = Item::get(296, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(2, $item);
                    break;
				}
				$chance2 = rand(1, 6);
                switch($chance2) {
					case 1:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["nutrition" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 3:
					$item = Item::get(14, 0, \kenygamer\Core\Main::mt_rand(30, 64));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 5:
			 		$item = ItemUtils::get("book", "", [], ["disarmprotection" => 1]);
			  		$chest->getInventory()->setItem(4, $item);
					break;
					case 6:
                    $item = Item::get(121, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(5, $item);
                    break;
				}
				$chance3 = rand(1, 5);
                switch($chance3) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
			    	$item2 = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 4)]);
			    	$item3 = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 5)]);
				    $item4 = Item::get(309, 0, 1);
					$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(401), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(403), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 1));
                    $item4->setCustomName("§r§bIron Boots\n§eEnlighted III\n§bPoisoned III\n§bGears I");
			        $chest->getInventory()->setItem(6, $item);
				    $chest->getInventory()->setItem(7, $item2);
			    	$chest->getInventory()->setItem(8, $item3);
				    $chest->getInventory()->setItem(9, $item4);
					break;
					case 2:
					$item = Item::get(98, 0, \kenygamer\Core\Main::mt_rand(50, 64));
                    $chest->getInventory()->setItem(8, $item);
					break;
					case 3:
                    $item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(6, $item);
                    break;
					case 4:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(0, 30));
                    $chest->getInventory()->setItem(6, $item);
					break;
					case 5:
					$item = Item::get(283, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 2));
                    $item->setCustomName("§r§dGold Sword§r\n§6Deathbringer V\n§eRage II\n§bLifesteal III");
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance4 = rand(1, 6);
                switch($chance4) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(105)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["treasurehunter" => 1]);
					$chest->getInventory()->setItem(15, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 5:
					$item = ItemUtils::get("mythic_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
					$chest->getInventory()->setItem(15, $item);
					break;
				}
				$chance5 = rand(1, 9);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(103)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 2:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 40));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["gears" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(18, $item);
					break;
					case 5:
					$item = Item::get(312, 12, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
		 			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
		          	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 2));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Leggings\n§6Overload II\n§6Armored II\n§6Heavy II\n§eDrunk IV");
					$chest->getInventory()->setItem(18, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["shrink" => 1]);
                    $chest->getInventory()->setItem(18, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["gears" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(18, $item);
					break;
					case 9:
					$item = ItemUtils::get("book", "", [], ["autorepair" => 1]);
					$chest->getInventory()->setItem(19, $item);
					break;
				}
				$chance6 = rand(1, 7);
                switch($chance6) {
					case 1:
					$item = Item::get(386, 0, 1);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["bleeding" => 1]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["freeze" => 1]);
					$chest->getInventory()->setItem(21, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["disarmor" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["warmer" => 1]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(20, $item);
					break;
				}
				$chance7 = rand(1, 6);
                switch($chance7) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["shileded" => \kenygamer\Core\Main::mt_rand(1, 2)]);
                    $chest->getInventory()->setItem(25, $item);
					break;
					case 2:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(1, 30));
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 3:
					$item = ItemUtils::get("experience_bottle2(104)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 4:
					$item = Item::get(41, 0, 40);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 5:
					$item = Item::get(271, 23, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bWooden Axe\n§eLumberjack I");
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 6:
					$item = Item::get(455, 0, 1);
				    $chest->getInventory()->setItem(23, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 2800:
            
		    if ($level !== null) {
		    	$x = rand(-6550, -8000);
		    	$y = rand(68, 70);
		    	$z = rand(-4500, -6530);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 7);
                switch($chance) {
					case 1:
					$item = Item::get(444, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 2:
					$item = Item::get(450, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 10));
					$chest->getInventory()->setItem(1, $item);
					break;
					case 4:
					$item = Item::get(359, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
                    $item->setCustomName("§r§bShears\n§eAutorepair II");
					$chest->getInventory()->setItem(3, $item);
					break;
					case 5:
					$item = Item::get(49, 0, \kenygamer\Core\Main::mt_rand(30, 50));
					$chest->getInventory()->setItem(3, $item);
					break;
					case 6:
                    $item = Item::get(296, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $chest->getInventory()->setItem(2, $item);
                    break;
                    case 7:
                    $item = ItemUtils::get("corona_mask");
                    $chest->getInventory()->setItem(2, $item);
                    break;
				}
				$chance2 = rand(1, 7);
                switch($chance2) {
					case 1:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 10));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["energizing" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(5, $item);
					break;
					case 3:
					$item = Item::get(14, 0, \kenygamer\Core\Main::mt_rand(30, 64));
					$chest->getInventory()->setItem(5, $item);
					break;
					case 4:
					$item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(1000), 3));
                    $item->setCustomName("§r§bTrident§r\n§6Disarm Protection I\n§6Nautica III\n§eAutorepair II");
					$chest->getInventory()->setItem(5, $item);
					break;
					case 5:
			 		$item = ItemUtils::get("book", "", [], ["disarmprotection" => 1]);
			  		$chest->getInventory()->setItem(4, $item);
					break;
					case 6:
                    $item = Item::get(121, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(5, $item);
                    break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["shockwave" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(1, $item);
					break;
				}
				$chance3 = rand(1, 5);
                switch($chance3) {
					case 1:
					$item = Item::get(369, 0, \kenygamer\Core\Main::mt_rand(0, 10));
			    	$item2 = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 4)]);
			    	$item3 = ItemUtils::get("book", "", [], ["angelic" => 1]);
				    $item4 = Item::get(309, 0, 1);
					$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(401), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(403), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 1));
                    $item4->setCustomName("§r§bIron Boots\n§eEnlighted III\n§bPoisoned III\n§bGears I");
			        $chest->getInventory()->setItem(6, $item);
				    $chest->getInventory()->setItem(7, $item2);
			    	$chest->getInventory()->setItem(8, $item3);
				    $chest->getInventory()->setItem(9, $item4);
					break;
					case 2:
					$item = Item::get(98, 0, \kenygamer\Core\Main::mt_rand(50, 64));
                    $chest->getInventory()->setItem(8, $item);
					break;
					case 3:
                    $item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $chest->getInventory()->setItem(6, $item);
                    break;
					case 4:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(0, 30));
                    $chest->getInventory()->setItem(6, $item);
					break;
					case 5:
					$item = Item::get(350, 0, \kenygamer\Core\Main::mt_rand(1, 3));
                    $item->setCustomName("§r§fDiamond Apple");
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance4 = rand(1, 6);
                switch($chance4) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(105)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["treasurehunter" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(15, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 5:
					$item = ItemUtils::get("mythic_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 6:
					$item = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
					$chest->getInventory()->setItem(15, $item);
					break;
				}
				$chance5 = rand(1, 9);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("experience_bottle2(105)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 2:
					$item = Item::get(400, 0, \kenygamer\Core\Main::mt_rand(20, 40));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 3:
					$item = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 4:
					$item = Item::get(314, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bGolden Helmet\n§6Overload I\n§6Implants III\n§6Tank II");
					$chest->getInventory()->setItem(18, $item);
					break;
					case 5:
					$item = Item::get(300, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
		 			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 2));
		          	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 4));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§bLeather Pants\n§6Overload IV\n§6Armored II\n§6Anti Knockback I\n§6Heavy II\n§eDrunk II");
					$chest->getInventory()->setItem(18, $item);
					break;
					case 6:
					$item = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(3, 6));
					$chest->getInventory()->setItem(18, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["shrink" => 1]);
                    $chest->getInventory()->setItem(18, $item);
					break;
					case 8:
					$item = ItemUtils::get("book", "", [], ["gears" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(18, $item);
					break;
					case 9:
					$item = ItemUtils::get("book", "", [], ["blind" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(19, $item);
					break;
				}
				$chance6 = rand(1, 8);
                switch($chance6) {
					case 1:
					$item = Item::get(386, 0, 1);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 2:
					$item = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(5, 15));
					$chest->getInventory()->setItem(20, $item);
					break;
					case 3:
					$item = Item::get(267, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), \kenygamer\Core\Main::mt_rand(1, 3)));
					$chest->getInventory()->setItem(21, $item);
					break;
					case 4:
					$item = Item::get(317, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
                    $item->setCustomName("§r§l§6Divine Gold Boots§r\n§6Overload VIII\n§6Armored V\n§6Anti Knockback I\n§eRevive V\n§bGears II");
					$chest->getInventory()->setItem(20, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["charge" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["poison" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["poison" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(20, $item);
					break;
					case 8:
					$item = ItemUtils::get("king_goblin_egg");
					$chest->getInventory()->setItem(20, $item);
					break;
				}
				$chance7 = rand(1, 7);
                switch($chance7) {
					case 1:
					$item = Item::get(455, 0, 1);
                    $chest->getInventory()->setItem(25, $item);
					break;
					case 2:
					$item = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(1, 30));
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["overload" => \kenygamer\Core\Main::mt_rand(1, 2)]);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 4:
					$item = Item::get(41, 0, 40);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 5:
					$item = Item::get(271, 11, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bWooden Axe\n§eLumberjack I");
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 6:
					$item = Item::get(455, 0, 1);
				    $chest->getInventory()->setItem(23, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 7)]);
					$chest->getInventory()->setItem(24, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 3600:
            
		    if ($level !== null) {
		    	$x = rand(-2000, -7000);
		    	$y = rand(68, 69);
		    	$z = rand(-2000, -7000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 3);
                switch($chance) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["blessed" => 1]);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 2:
					$item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 6));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 2));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(119), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(123), 1));
                    $item->setCustomName("§r§l§6Legendary Sword§r\n§cExcalibur II\n§6Curse I\n§6Soulbound III\n§6Hallucination III");
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["enraged" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["excalibur" => 1]);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["excalibur" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(3, $item);
					break;
				}
				$chance2 = rand(1, 5);
                switch($chance2) {
					case 1:
					$item = Item::get(368, 0, 16);
					$chest->getInventory()->setItem(6, $item);
					break;
					case 2:
					$item = Item::get(293, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(208), 1));
                    $item->setCustomName("§r§bDiamond Hoe\n§eFertilizer I\n§eAutorepair I");
					$chest->getInventory()->setItem(6, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["vampire" => 1]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 4:
					$item = Item::get(129, 0, 50);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 5:
					$item = Item::get(213, 0, 25);
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance3 = rand(1, 5);
                switch($chance3) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["angel" => 1]);
					$chest->getInventory()->setItem(10, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["angel" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(10, $item);
					break;
					case 3:
					$item = Item::get(373, 8, 1);
					$chest->getInventory()->setItem(10, $item);
					break;
					case 4:
					$item = Item::get(373, 8, 1);
					$item2 = Item::get(373, 8, 1);
					$chest->getInventory()->setItem(10, $item);
					$chest->getInventory()->setItem(11, $item2);
					break;
					case 5:
					$item = Item::get(347, 0, 1);
					$chest->getInventory()->setItem(11, $item);
					break;
				}
				$chance4 = rand(1, 7);
                switch($chance4) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["wither" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["wither" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 3:
					$item = Item::get(309, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(703), 1));
                    $item->setCustomName("§r§bIron Boots\n§6Jetpack I");
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = Item::get(309, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(508), 2));
                    $item->setCustomName("§r§bIron Boots\n§eEvasion II");
					$chest->getInventory()->setItem(14, $item);
					break;
					case 5:
					$item = Item::get(297, 0, 30);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 6:
					$item = Item::get(121, 0, 64);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 7:
					$item = ItemUtils::get("book", "", [], ["disarming" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(2, $item);
					break;
				}
				$chance5 = rand(1, 5);
                switch($chance5) {
					case 1:
					$item = Item::get(310, 0, 1);
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 2));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload III\n§6Tank III\n§6Heavy III\n§6Amored III\n§6Implants II");
                    $item2 = Item::get(311, 0, 1);
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
		           	$item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
			        $item2->setCustomName("§r§bDiamond Chestplate\n§6Overload III\n§eRevive III");
                    $item3 = Item::get(312, 0, 1);
		        	$item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
			        $item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(405), 1));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(418), 2));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 2));
		        	$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		           	$item3->setCustomName("§r§bDiamond Leggings\n§6Overload III\n§eShileded II\n§eObsidian Shield I\n§bAngel II");
                    $item4 = Item::get(313, 0, 1);
		        	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
		          	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(2), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(7), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 3));
		        	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		          	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
		           	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(701), 2));
		         	$item4->setCustomName("§r§bDiamond Boots\n§6Overload III\n§eDrunk III\n§bGears II\n§bSprings II");
                    $item5 = Item::get(276, 0, 1);
		          	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 4));
		         	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
	                $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 4));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 3));
					$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(121), 3));
		        	$item5->setCustomName("§r§bDiamond Sword\n§6Gravity III\n§6Deathbringer IV\n§eAutorepair II\n§eCripple III\n§bLifesteal III");
					$chest->getInventory()->setItem(20, $item);
					$chest->getInventory()->setItem(21, $item2);
					$chest->getInventory()->setItem(22, $item3);
					$chest->getInventory()->setItem(23, $item4);
					$chest->getInventory()->setItem(24, $item5);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["autoaim" => 1]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["doomed" => 1]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["gravity" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(24, $item);
					break;
					case 5:
					$item = ItemUtils::get("rare_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$chest->getInventory()->setItem(24, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
			case 4800:
            
		    if ($level !== null) {
		    	$x = rand(5000, 9000);
		    	$y = rand(68, 69);
		    	$z = rand(5000, 9000);
		    	self::$last_envoy = new Vector3($x, $y, $z);
		    	MiscListener::$unclaimedEnvoys[] = self::$last_envoy;
		    	self::$last_envoy_time = time();
		    	$level->loadChunk($x >> 4, $z >> 4, true);
		    	$level->setBlock(new Vector3($x, $y, $z), Block::get(54));
			    $nbt = new CompoundTag(" ", [
		        	new ListTag("Items", []),
		        	new StringTag("id", Tile::CHEST),
		        	new IntTag("x", $x),
		        	new IntTag("y", $y),
		        	new IntTag("z", $z)
	        	]);
		    	$chest = Tile::createTile("Chest", $level, $nbt);
                $level->addTile($chest);
		    	$chance = rand(1, 7);
                switch($chance) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["revive" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["revive" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(3, $item);
					break;
					case 3:
					$item = Item::get(292, 0, 1);
					$chest->getInventory()->setItem(4, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["revive" => 1]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["volley" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(2, $item);
					break;
					case 6:
					$item = Item::get(276, \kenygamer\Core\Main::mt_rand(500, 1000), 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(107), 1));
                    $item->setCustomName("§r§eDiamond Sword\n§cShockwave I");
					$chest->getInventory()->setItem(2, $item);
					break;
					case 7:
					$item = Item::get(261, 45, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(21), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(311), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(317), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 5));
                    $item->setCustomName("§r§l§eFire Bow§r\n§6Blaze I\n§eVirus III\n§eVolley IV\n§bBow Lifesteal V");
					$chest->getInventory()->setItem(1, $item);
					break;
				}
				$chance2 = rand(1, 6);
                switch($chance2) {
					case 1:
					$item = Item::get(368, 0, 16);
					$chest->getInventory()->setItem(6, $item);
					break;
					case 2:
					$item = Item::get(293, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(208), 1));
                    $item->setCustomName("§r§bDiamond Hoe\n§eFertilizer I\n§eAutorepair I");
					$chest->getInventory()->setItem(6, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["drain" => 1]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 5:
					$item = Item::get(213, 0, 25);
					$chest->getInventory()->setItem(7, $item);
					break;
					case 6:
					$item = Item::get(275, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bStone Axe\n§eLumberjack I");
					$chest->getInventory()->setItem(7, $item);
					break;
				}
				$chance3 = rand(1, 6);
                switch($chance3) {
					case 1:
					$item = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 15));
					$chest->getInventory()->setItem(10, $item);
					break;
					case 2:
					$item = Item::get(272, 0, 1);
		          	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
		         	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 4));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(106), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
		        	$item->setCustomName("§r§9Ice Sword\n§6Deathbringer VI\n§eAutorepair II\n§bIce Aspect V\n§bLifesteal IV");
					$chest->getInventory()->setItem(10, $item);
					break;
					case 3:
					$item = Item::get(373, 8, 1);
					$chest->getInventory()->setItem(10, $item);
					break;
					case 4:
					$item = Item::get(373, 8, 1);
					$item2 = Item::get(373, 8, 1);
					$chest->getInventory()->setItem(10, $item);
					$chest->getInventory()->setItem(11, $item2);
					break;
					case 5:
					$item = Item::get(7, 0, 35);
					$chest->getInventory()->setItem(11, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["shockwave" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(1, $item);
					break;
				}
				$chance4 = rand(1, 7);
                switch($chance4) {
					case 1:
					$item = Item::get(280, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), \kenygamer\Core\Main::mt_rand(1, 2)));
					$chest->getInventory()->setItem(15, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["wither" => \kenygamer\Core\Main::mt_rand(1, 3)]);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["shuffle" => 1]);
					$chest->getInventory()->setItem(15, $item);
					break;
					case 4:
					$item = Item::get(309, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(508), 4));
                    $item->setCustomName("§r§bIron Boots\n§eEvasion IV");
					$chest->getInventory()->setItem(14, $item);
					break;
					case 5:
					$item = Item::get(297, 0, 30);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 6:
					$item = Item::get(121, 0, 64);
					$chest->getInventory()->setItem(14, $item);
					break;
					case 7:
					$item = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(2, 5));
					$chest->getInventory()->setItem(14, $item);
					break;
				}
				$chance5 = rand(1, 7);
                switch($chance5) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["molten" => \kenygamer\Core\Main::mt_rand(1, 4)]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["autoaim" => \kenygamer\Core\Main::mt_rand(1, 2)]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["bleeding" => \kenygamer\Core\Main::mt_rand(1, 5)]);
					$chest->getInventory()->setItem(22, $item);
					break;
					case 4:
					$item = Item::get(264, 0, 64);
					$chest->getInventory()->setItem(24, $item);
					break;
					case 5:
					$item = Item::get(400, 0, 64);
					$chest->getInventory()->setItem(24, $item);
					break;
					case 6:
					$item = ItemUtils::get("book", "", [], ["grind" => 1]);
					$chest->getInventory()->setItem(24, $item);
					break;
					case 7:
					$item = Item::get(267, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(116), 1));
                    $item->setCustomName("§r§bIron Sword\n§eHeadless I");
					$chest->getInventory()->setItem(25, $item);
					break;
				}
				LangManager::broadcast("core-envoy-arrival", $x, $y, $z);
			} else {
				$this->plugin->getLogger()->warning("Envoys could not spawn. World doesn't exist!");
			}
			break;
		}
        $this->time++;
	}
}