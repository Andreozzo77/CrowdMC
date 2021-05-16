<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Durable;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemFactory;

use CustomEnchants\CustomEnchants\CustomEnchants;
use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;

class Treasure implements Listener{
	/** @var Core */
	private $plugin;
	/** @var Vector3[] */
	private $crates = [];
	
	public function __construct(Core $plugin) {
        $this->plugin = $plugin;
		$this->crates = [
		    "yellow" => new Vector3(45062, 41, -42606),
		    "purple" => new Vector3(45065, 41, -42602),
		    "blue" => new Vector3(45049, 41, -42602),
		    "red" => new Vector3(45052, 41, -42606),
		    "green" => new Vector3(45057, 41, -42608)
		];
	}

	/**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$drops = $event->getDrops();
		//// Treasure Hunter Item Relic Bonus When Miner
		if ($player->getGamemode() === 0) {
			
			/** @var int[] */
			$blocks = [
			    1, 15, 14, 16, 57, 133, 41, 42, 22, 152, 82, 21, 73, 74, 75, 76, 153, 56, 87, 2, 3, 129  
			];
			
            if(in_array($block->getId(), $blocks)){
                if (\kenygamer\Core\Main::mt_rand(0, 3375 * 2) === 10) {
                    $drops[] = ItemUtils::get("common_key");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 5400 * 2) === 10) {
                    $drops[] = ItemUtils::get("rare_key");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 6750 * 2) === 10) {
                    $drops[] = ItemUtils::get("ultra_key");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 13500 * 2) === 5) {
                    $drops[] = ItemUtils::get("yellow_crystal");
                    $event->setDrops($drops);
                }
                if (\kenygamer\Core\Main::mt_rand(0, 13500 * 2) === 5) {
                    $drops[] = ItemUtils::get("red_crystal");
                    $event->setDrops($drops);
                }
                if (\kenygamer\Core\Main::mt_rand(0, 13500 * 2) === 5) {
                    $drops[] = ItemUtils::get("green_crystal");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 13500 * 2) === 5) {
                    $drops[] = ItemUtils::get("blue_crystal");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 13500 * 2) === 5) {
                    $drops[] = ItemUtils::get("purple_crystal");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 2025 * 2) === 10) { 
                    $drops[] = ItemUtils::get("mythic_note(50)");
                    $event->setDrops($drops);
				}
                if (\kenygamer\Core\Main::mt_rand(0, 3575 * 2) === 10) {
                    $player = $event->getPlayer();
                    $drops[] = ItemUtils::get("experience_bottle2(100)");
                    $event->setDrops($drops);
				}
			}
			
			if($block->getId() == 18 || $block->getId() == 161){
                if(\kenygamer\Core\Main::mt_rand(0, 135 * 2) === 10) {
                    $tier = Item::get(260, 0, \kenygamer\Core\Main::mt_rand(0, 2));
                    $drops[] = $tier;
                    $event->setDrops($drops);
				}

                if (\kenygamer\Core\Main::mt_rand(0, 200 * 2) === 10) {
                    $tier = Item::get(322, 0, \kenygamer\Core\Main::mt_rand(0, 1));
                    $drops[] = $tier;
                    $event->setDrops($drops);
				}
				if (\kenygamer\Core\Main::mt_rand(0, 270 * 2) === 10) {
                    $tier = Item::get(466, 0, \kenygamer\Core\Main::mt_rand(0, 1));
                    $drops[] = $tier;
                    $event->setDrops($drops);
                }
			}
		}
	}

	/**
     * @param PlayerInteractEvent $event
     * @ignoreCancelled false
     */
	public function onItems(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
        $block = $event->getBlock();
        if($block->getId() === Block::CHEST || $block->getId() === Block::TRAPPED_CHEST){
            if($item->getId() === Item::SLIMEBALL && $item->getDamage() === 1){ //Common Key
                $event->setCancelled();
                $player->getInventory()->removeItem(Item::get(341, 1, 1));
                $reward = rand(1, 80);
                switch($reward) {
                    case 1:
                    $bonus = rand(1000, 5000);
                    Main::getInstance()->addMoney($player, $bonus);
                    break;
                    case 2:
                    $item = Item::get(268, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 2));
                    $item->setCustomName("§r§bWooden Sword\n§eBlessed II\n§bLifesteal III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $tier = Item::get(298, 0, 1);
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 3));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 2));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
                    $tier->setCustomName("§r§bLeather Caps\n§6Overload II\n§6Armored II");
                    $player->getInventory()->addItem($tier);
                    break;
                    case 4:
                    case 5:
                    $tier = ItemUtils::get("book", "", [], ["implants" => 1]);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 6:
                    $item = Item::get(266, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    break;
                    case 8:
                    $item = Item::get(3, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 9:
                    $item = ItemUtils::get("book", "", [], ["gears" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 10:
                    $item = Item::get(1, 0, \kenygamer\Core\Main::mt_rand(10, 30));
                    $player->getInventory()->addItem($item);
                    break;
                    case 11:
                    $item = ItemUtils::get("book", "", [], ["frozen" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 12:
                    $item = ItemUtils::get("book", "", [], ["glowing" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 13:
                    $item = Item::get(272, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 14:
                    $item = Item::get(1, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 15:
                    $item = Item::get(257, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = Item::get(257, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
                    $item->setCustomName("§r§bIron Pickaxe§r\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 17:
                    $item = Item::get(268, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    $item = Item::get(2, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 19:
                    $item = Item::get(257, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), 1));
                    $item->setCustomName("§r§bIron Pickaxe");
                    $player->getInventory()->addItem($item);
                    break;
                    case 20:
                    $item = ItemUtils::get("book", "", [], ["vampire" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 21:
                    $bonus = rand(1000, 5000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 22:
                    $item = ItemUtils::get("book", "", [], ["armored" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 23:
                    $item = ItemUtils::get("book", "", [], ["gooey" => \kenygamer\Core\Main::mt_rand(1, 2)]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 24:
                    $item = Item::get(4, 0, \kenygamer\Core\Main::mt_rand(10, 50));
                    $player->getInventory()->addItem($item);
                    break;
                    case 25:
                    $item = ItemUtils::get("book", "", [], ["longbow" => \kenygamer\Core\Main::mt_rand(1, 2)]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 26:
                    $item = Item::get(4, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    $item = Item::get(297, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 28:
                    $item = Item::get(1,0,64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = Item::get(311, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = Item::get(267, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(113), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 2));
                    $item->setCustomName("§r§bIron Sword§r\n§eBlessed II\n§bLifesteal III\n§bCharge II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 31:
                    $item = Item::get(367, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    $item = ItemUtils::get("book", "", [], ["aerial" => \kenygamer\Core\Main::mt_rand(1, 4)]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 33:
                    $item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(354, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = ItemUtils::get("book", "", [], ["aquatic" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $bonus = rand(5000, 15000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 37:
                    $item = Item::get(257, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), 1));
                    $item->setCustomName("§r§bIron Pickaxe");
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = Item::get(263, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = Item::get(264, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = Item::get(260, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    $item = ItemUtils::get("book", "", [], ["poison" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 42:
                    $item = Item::get(265, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    $item = Item::get(322, 0, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 44:
                    $item = ItemUtils::get("book", "", [], ["wither" => \kenygamer\Core\Main::mt_rand(1, 2)]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $item = Item::get(388, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 46:
                    $item = ItemUtils::get("book", "", [], ["springs" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = Item::get(313, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    break;
                    case 49:
                    $item = ItemUtils::get("book", "", [], ["autorepair" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = ItemUtils::get("book", "", [], ["vampire" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 51:
                    $item = Item::get(271, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bWooden Axe§r\n§eLumberjack I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $item = Item::get(263, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = ItemUtils::get("book", "", [], ["lifesteal" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = Item::get(302, 0, 1);
                    $item2 = Item::get(303, 0, 1);
                    $item3 = Item::get(304, 0, 1);
                    $item4 = Item::get(305, 0, 1);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    break;
                    case 55:
                    $bonus = rand(2500, 7500);
                    Main::getInstance()->addMoney($player, $bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 56:
                    $item = Item::get(261, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(316), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(317), 1));
                    $item->setCustomName("§r§bBow\n§6Longbow I\n§eVirus I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = ItemUtils::get("book", "", [], ["bowlifesteal" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = Item::get(293, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(208), 1));
                    $item->setCustomName("§r§bDiamond Hoe\n§eFertilizer I\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $item = ItemUtils::get("book", "", [], ["enraged" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    $item = Item::get(307, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(401), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(403), 1));
                    $item->setCustomName("§r§bIron Chestplate\n§eEnlighted I\n§bPoisoned I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    $bonus = rand(1000, 5000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 62:
                    $bonus = rand(500, 2500);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 63:
                    $item = ItemUtils::get("book", "", [], ["overload" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 64:
                    $item = Item::get(272, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 65:
                    $item = ItemUtils::get("book", "", [], ["heavy" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 66:
                    $item = Item::get(359, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 1));
                    $item->setCustomName("§r§bShears\n§eAutorepair I\n§bHaste I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 67:
                    case 68:
                    $tier = ItemUtils::get("book", "", [], ["poison" => 1]);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 69:
                    $tier = ItemUtils::get("book", "", [], ["blind" => 1]);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 70:
                    $item = Item::get(260, 0, \kenygamer\Core\Main::mt_rand(10, 30));
                    $player->getInventory()->addItem($item);
                    break;
                    case 71:
                    $item = Item::get(17, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 72:
                    $tier = ItemUtils::get("book", "", [], ["charge" => 2]);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 73:
                    $item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 74:
                    $tier = ItemUtils::get("book", "", [], ["charge" => 3]);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 75:
                    $tier = ItemUtils::get("book", "", [], ["venom" => 1]);
                    $player->getInventory()->addItem($tier);
                    case 76:
                    $tier = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($tier);
					break;
					case 77:
                    $tier = ItemUtils::get("book", "", [], ["blind" => 1]);
                    $player->getInventory()->addItem($tier);
                    break;
					case 78:
                    $tier = ItemUtils::get("book", "", [], ["blind" => 2]);
                    $player->getInventory()->addItem($tier);
                    break;
					case 79:
                    $tier = ItemUtils::get("book", "", [], ["blind" => 3]);
                    $player->getInventory()->addItem($tier);
                    break;
					case 80:
                    $tier = ItemUtils::get("book", "", [], ["venom" => 2]);
                    $player->getInventory()->addItem($tier);
                    break;
                }
			}
		}
        if($block->getId() === Block::CHEST || $block->getId() === Block::TRAPPED_CHEST){
            if($item->getId() == Item::SLIMEBALL and $item->getDamage() === 2){ //Vote Key
                $player->getInventory()->removeItem(Item::get(341, 2, 1));
                $event->setCancelled();
                $reward = rand(1, 91);
                switch($reward) {
                    case 1:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
                    $item->setCustomName("§r§l§eVoter §6Sword§r\n§6Deathbringer V\n§eRage III\n§eCripple III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 2:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(207), 3));
                    $item->setCustomName("§r§l§eVoter §6Pickaxe§r\n§eDriller III\n§eAutorepair II\n§bHaste III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $item = Item::get(264, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 4:
                    $bonus = rand(1500, 8500);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 5:
                    $item = Item::get(322, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 6:
                    $item = Item::get(267, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 1));
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    break;
                    case 8:
                    case 9:
                    $item = Item::get(261, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(303), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(307), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(316), 3));
                    $item->setCustomName("§r§l§eVoter §6Bow§r\n§6Longbow III\n§6Piercing I\n§6Paralyze II\n§eVolley I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 10:
                    $item = Item::get(7, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 11:
                    break;
                    case 12:
                    $item = Item::get(41, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 13:
                    $item = Item::get(5, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 14:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 1));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Implants I\n§6Tank II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 15:
                    $item = ItemUtils::get("book", "", [], ["overload" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = Item::get(42, 0, 10);
                    $item2 = Item::get(41, 0, 10);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 17:
                    $item = ItemUtils::get("book", "", [], ["soulbound" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    $item = Item::get(173, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 19:
                    $item = Item::get(276, 0, 2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 20:
                    $item = Item::get(49, 0, 25);
                    $player->getInventory()->addItem($item);
                    break;
                    case 21:
                    $item = Item::get(3, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 22:
                    case 23:
                    $item = Item::get(46, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 24:
                    $item = Item::get(278, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 25:
                    $item = ItemUtils::get("book", "", [], ["shileded" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 26:
                    $item = Item::get(57, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    $item = ItemUtils::get("book", "", [], ["obsidianshield" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 28:
                    $item = Item::get(311, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(418), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
                    $item->setCustomName("§r§l§eVoter §6Chestplate§r\n§6Overload III\n§6Armored III\n§eRevive II\n§bAngel I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = ItemUtils::get("book", "", [], ["deathbringer" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = Item::get(262, 0, 64);
                    $item2 = Item::get(261, 0, 1);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 31:
                    $item = Item::get(352, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    break;
                    case 33:
                    $item = ItemUtils::get("book", "", [], ["autorepair" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(265, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = Item::get(400, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $item = ItemUtils::get("book", "", [], ["lifesteal" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 37:
                    $item = Item::get(322, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = Item::get(264, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = ItemUtils::get("book", "", [], ["angel" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = ItemUtils::get("book", "", [], ["blind" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    $item = Item::get(377, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 42:
                    $item = ItemUtils::get("book", "", [], ["blind" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    $item = ItemUtils::get("book", "", [], ["haste" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 44:
                    $item = Item::get(466, 0, \kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $bonus = rand(10000, 50000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 46:
                    $item = ItemUtils::get("book", "", [], ["wither" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = Item::get(22, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    $item = Item::get(14, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 49:
                    $item = ItemUtils::get("book", "", [], ["angelic" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = Item::get(261, 0, 1);
                    $item2 = Item::get(262, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 51:
                    $item = Item::get(293, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $item = ItemUtils::get("book", "", [], ["charge" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(202), 3));
                    $item->setCustomName("§r§bDiamond Pickaxe\n§eAutorepair I\n§bEnergizing III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = ItemUtils::get("book", "", [], ["iceaspect" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $bonus = rand(12500, 25000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 56:
                    $item = ItemUtils::get("book", "", [], ["tank" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = ItemUtils::get("shard_note");
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = ItemUtils::get("book", "", [], ["energizing" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $item = Item::get(57, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    $item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    case 62:
                    $item = ItemUtils::get("vote_key")->setCount(5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 63:
                    $bonus = rand(75000, 200000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 64:
                    $item = ItemUtils::get("lucky_block")->setCount(3);
                    $player->getInventory()->addItem($item);
                    break;
                    case 65:
                    $item = ItemUtils::get("lucky_block")->setCount(10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 66:
                    $item = ItemUtils::get("common_book")->setCount(3);
                    $player->getInventory()->addItem($item);
                    break;
					case 67:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Implants III\n§6Tank II");
                    $player->getInventory()->addItem($item);
                    break;
					case 68:
                    $item = Item::get(313, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(400), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 3));
                    $item->setCustomName("§r§l§eVoter §6Boots§r\n§6Overload III\n§6Armored III\n§eRevive III\n§eMolten I");
                    $player->getInventory()->addItem($item);
                    break;
					case 69:
                    $item = ItemUtils::get("common_book")->setCount(10);
                    $player->getInventory()->addItem($item);
                    break;
					case 70:
                    $item = ItemUtils::get("experience_bottle2(101)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
					case 71:
                    $item = ItemUtils::get("diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
					case 72:
                    $item = Item::get(399, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
					case 73:
                    $item = Item::get(201, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
					case 74:
                    $item = Item::get(455, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
					case 75:
                    $item = Item::get(359, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
                    $item->setCustomName("§r§bShears\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 76:
					case 77:
                    $item = ItemUtils::get("book", "", [], ["energizing" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
					case 78:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
					case 79:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
					case 80:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
					case 81:
                    $item = ItemUtils::get("book", "", [], ["nutrition" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
					case 82:
                    $item = ItemFactory::get(241, \kenygamer\Core\Main::mt_rand(1, 9), 64);
                    $player->getInventory()->addItem($item);
                    break;
					case 83:
                    $item = ItemFactory::get(406, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
					case 84:
                    $item = Item::get(397, 5, 1);
                    $player->getInventory()->addItem($item);
                    break;
					case 85:
					$item = Item::get(388, 0, \kenygamer\Core\Main::mt_rand(15, 30));
                    $player->getInventory()->addItem($item);
					break;
					case 86:
					$item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
                    $player->getInventory()->addItem($item);
					break;
					case 87:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(202), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(213), 2));
                    $item->setCustomName("§r§bDiamond Pickaxe\n§6Grind II\n§eAutorepair III\n§bEnergizing III");
                    $player->getInventory()->addItem($item);
                    break;
					case 88:
					$item = ItemUtils::get("green_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
					$player->getInventory()->addItem($item);
					break;
					case 89:
					$item = ItemUtils::get("blue_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
					break;
					case 90:
					$item = ItemUtils::get("book", "", [], ["insanity" => 2]);
                    $player->getInventory()->addItem($item);
					case 91:
                    $item = ItemUtils::get("book", "", [], ["armored" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                }
            }
        }
        if ($block->getId() === Block::CHEST || $block->getId() === Block::TRAPPED_CHEST) {
            if ($item->getId() == Item::SLIMEBALL and $item->getDamage() === 3) { //Rare Key
                $player->getInventory()->removeItem(Item::get(341, 3, 1));
                $event->setCancelled();
                $reward = rand(1, 72);
                switch($reward) {
                    case 1:
                    $item = Item::get(399, 0, 2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 2:
                    $item = ItemUtils::get("book", "", [], ["sash" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $item = Item::get(264, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 4:
                    $item = Item::get(322, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 5:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 2));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(119), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 1));
                    $item->setCustomName("§r§l§6Legendary Sword§r\n§cExcalibur I\n§6Soulbound I\n§6Hallucination III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 6:
                    $item = Item::get(466, 0, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    $item = Item::get(246, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 8:
                    $item = ItemUtils::get("book", "", [], ["haste" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 9:
                    break;
                    case 10:
                    $item = Item::get(7, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 11:
                    $item = ItemUtils::get("book", "", [], ["tank" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 12:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(207), 5));
                    $item->setCustomName("§r§l§6God Pickaxe§r\n§eDriller V\n§eAutorepair V\n§bHaste IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 13:
                    $item = Item::get(57, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 14:
                    $item = Item::get(57, 0, 10);
                    $item2 = Item::get(41, 0, 10);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 15:
                    $item = ItemUtils::get("book", "", [], ["driller" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = ItemUtils::get("book", "", [], ["blind" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 17:
                    $item = Item::get(170, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    case 19:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 0, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 20:
                    $bonus = 100000;
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 21:
                    $item = Item::get(310,0,1);
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload VI\n§6Implants III\n§6Tank III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 22:
                    $item = Item::get(313, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Boots\n§6Overload IV\n§6Heavy II\n§eShileded III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 23:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 0, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 24:
                    $item = Item::get(121, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 25:
                    $item = Item::get(312, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Leggings\n§6Overload V\n§6Armored II\n§6Heavy II\n§eDrunk IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 26:
                    $item = ItemUtils::get("book", "", [], ["overload" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    $bonus = 200000;
                    Main::getInstance()->addMoney($player, $bonus);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 28:
                    $item = Item::get(1, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = Item::get(3, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = ItemUtils::get("book", "", [], ["drunk" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 31:
                    $item = Item::get(49, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    $item = Item::get(42, 0, 25);
                    $player->getInventory()->addItem($item);
                    break;
                    case 33:
                    $item = ItemUtils::get("book", "", [], ["endershift" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(22, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = Item::get(73, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $item = ItemUtils::get("book", "", [], ["venom" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 37:
                    $item = Item::get(46, 0, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = Item::get(86, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = ItemUtils::get("book", "", [], ["stomp" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = Item::get(400, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    $item = ItemUtils::get("book", "", [], ["gears" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 42:
                    $item = Item::get(7,0,30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    $item = ItemUtils::get("book", "", [], ["overload" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 44:
                    $item = Item::get(466, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $item = ItemUtils::get("book", "", [], ["bowlifesteal" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 46:
                    $item = ItemUtils::get("book", "", [], ["bowlifesteal" => 3]);
                    $player->getInventory()->addItem($item);
                    case 47:
                    case 48:
                    $bonus = rand(30000, 80000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 49:
                    $item = Item::get(2, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = ItemUtils::get("book", "", [], ["shileded" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 51:
                    $bonus = rand(10000, 75000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 52:
                    $item = Item::get(133, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = Item::get(98, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = Item::get(7, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $item = Item::get(293, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(208), 1));
                    $item->setCustomName("§r§bDiamond Hoe\n§eFertilizer I\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 56:
                    $item = ItemUtils::get("book", "", [], ["enraged" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = Item::get(307, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(401), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(403), 2));
                    $item->setCustomName("§r§bIron Chestplate\n§eEnlighted II\n§bPoisoned II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $bonus = rand(25000, 75000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 59:
                    $item = Item::get(17, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    $item = ItemUtils::get("book", "", [], ["enlightee" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(418), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(803), 1));
                    $item->setCustomName("§r§bDiamond Helmet§r\n§6Overload V\n§6Clarity I\n§6Armored III\n§6Heavy II\n§eDrunk IV\n§bAngel II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 62:
                    $item = ItemUtils::get("book", "", [], ["moneyfarm" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 63:
                    $item = Item::get(17, 1, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 64:
                    $item = ItemUtils::get("book", "", [], ["smelting" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 65:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
                    $player->getInventory()->addItem($item);
                    break;
                    case 66:
                    $item = Item::get(311, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 67:
                    $item = Item::get(313, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 68:
					$item = Item::get(260, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 69:
                    $item = ItemUtils::get("common_book");
                    $player->getInventory()->addItem($item);
					break;
					case 70:
					$item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
                    $player->getInventory()->addItem($item);
					break;
					case 71:
					$item = ItemUtils::get("book", "", [], ["grind" => 1]);
					$player->getInventory()->addItem($item);
					break;
					case 72:
					$item = ItemUtils::get("book", "", [], ["grind" => 2]);
					$player->getInventory()->addItem($item);
                    break;
				}
			}
		}
        if ($block->getId() === Block::CHEST || $block->getId() == Block::TRAPPED_CHEST) {
            if ($item->getId() == Item::SLIMEBALL and $item->getDamage() == 4){ //Ultra Key
                $player->getInventory()->removeItem(Item::get(341, 4, 1));
                $event->setCancelled();
                $reward = rand(1, 67);
                switch($reward) {
                    case 1:
                    $item = Item::get(57, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 2:
                    $item = ItemUtils::get("book", "", [], ["blessed" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $item = Item::get(264, 0, 128);
                    $player->getInventory()->addItem($item);
                    break;
                    case 4:
                    $item = Item::get(322, 0, 50);
                    $player->getInventory()->addItem($item);
                    $event->setCancelled(true);
                    break;
                    case 5:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(119), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 1));
                    $item->setCustomName("§r§l§6Legendary Sword§r\n§cExcalibur I\n§6Soulbound I\n§6Hallucination III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 6:
                    $item = ItemUtils::get("book", "", [], ["antiknockback" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    $item = Item::get(261, 0, 1);
                    $item2 = Item::get(262, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 8:
                    $item = Item::get(264, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 9:
                    $item = ItemUtils::get("book", "", [], ["lifesteal" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 10:
                    break;
                    case 11:
                    $item = Item::get(7, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 12:
                    $item = ItemUtils::get("book", "", [], ["autorepair" => 1]);
					$item2 = ItemUtils::get("book", "", [], ["poison" => 2]);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 13:
                    $item = ItemUtils::get("book", "", [], ["enraged" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 14:
                    $item = Item::get(57, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 15:
                    $item = Item::get(368, 0, 16);
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = Item::get(455, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 17:
                    $item = ItemUtils::get("book", "", [], ["driller" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    $item = Item::get(170, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 19:
                    case 20:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 0, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 21:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload VI\n§6Implants III\n§6Tank III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 22:
                    $item = Item::get(322, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 23:
                    $item = Item::get(7, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 24:
                    $item = Item::get(313, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Boots\n§6Overload IV\n§6Heavy II\n§eShileded III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 25:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 0, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 26:
                    $bonus = 125000;
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 27:
                    $item = Item::get(312, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Leggings\n§6Overload V\n§6Armored II\n§6Heavy II\n§eDrunk IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 28:
                    $item = ItemUtils::get("book", "", [], ["overload" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = ItemUtils::get("book", "", [], ["volley" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = ItemUtils::get("book", "", [], ["hallucination" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 31:
                    $item = ItemUtils::get("common_book");
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    $item = ItemUtils::get("book", "", [], ["hallucination" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 33:
                    $item = ItemUtils::get("book", "", [], ["drunk" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(293, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = ItemUtils::get("book", "", [], ["molten" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $item = Item::get(41, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 37:
                    $item = Item::get(129, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = Item::get(276, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = ItemUtils::get("book", "", [], ["soulbound" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = Item::get(15, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    break;
                    case 42:
                    $item = Item::get(322, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    $item = ItemUtils::get("book", "", [], ["focused" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 44:
                    $item = ItemUtils::get("book", "", [], ["backstab" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $item = Item::get(2, 0, 64);
                    $player->getInventory()->addItem($item);
                    case 46:
                    $item = Item::get(35, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = ItemUtils::get("book", "", [], ["glowing" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    $item = ItemUtils::get("experience_bottle2(102)")->setCount(3);
                    $player->getInventory()->addItem($item);
                    break;
                    case 49:
                    $item = ItemUtils::get("book", "", [], ["gravity" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = ItemUtils::get("book", "", [], ["berseker" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 51:
                    $item = Item::get(7, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $item = Item::get(266, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = Item::get(286, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = Item::get(4, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $item = Item::get(46,0,64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 56:
                    $item = ItemUtils::get("book", "", [], ["critical" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = ItemUtils::get("book", "", [], ["longbow" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = ItemUtils::get("book", "", [], ["poison" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $item = Item::get(47, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    $item = ItemUtils::get("book", "", [], ["poison" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    $item = ItemUtils::get("uncommon_book");
                    $player->getInventory()->addItem($item);
                    break;
					case 62:
                    $item = Item::get(397, 5, 1);
                    $player->getInventory()->addItem($item);
                    break;
					case 63:
					$item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
                    $player->getInventory()->addItem($item);
					break;
					case 64:
					$item = Item::get(455, 0, 1);
                    $player->getInventory()->addItem($item);
					break;
					case 65:
					$item = Item::get(396, 0, 10);
                    $player->getInventory()->addItem($item);
					break;
					case 66:
					$item = ItemUtils::get("book", "", [], ["grind" => 3]);
					$player->getInventory()->addItem($item);
					break;
					case 67:
					$item = ItemUtils::get("book", "", [], ["grind" => 4]);
					$player->getInventory()->addItem($item);
					break;
				}
			}
		}
        if ($block->getId() === Block::CHEST || $block->getId() === Block::TRAPPED_CHEST) {
            if ($item->getId() == Item::SLIMEBALL and $item->getDamage() == 5) { //Mythic Key
                $reward = rand(1, 87);
                $player->getInventory()->removeItem(Item::get(341, 5, 1));
                $event->setCancelled();
                switch($reward) {
                    case 1:
                    $item = Item::get(399, 0, 15);
                    $item2 = Item::get(466, 0, 15);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 2:
                    $item = Item::get(57, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 6));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 2));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(117), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(119), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(127), 1));
                    $item->setCustomName("§r§l§5Mythic §6Sword§r\n§cExcalibur I\n§6Hallucination IV\n§6Critical I\n§eDisarming III\n§bLifesteal V");
                    $player->getInventory()->addItem($item);
                    break;
                    case 4:
                    $item = Item::get(322, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 5:
                    $item = Item::get(279, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(138), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bDiamond Axe§r\n§6Insanity II\n§eLumberjack I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 6:
                    $item = Item::get(466, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    case 8:
                    $bonus = rand(5000, 80000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 9:
                    break;
                    case 10:
                    $item = Item::get(266, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 11:
                    $item = ItemUtils::get("book", "", [], ["antitoxin" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 12:
                    $item = Item::get(266, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 13:
                    case 14:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(41, 0, 64);
                    $item3 = Item::get(7, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    break;
                    case 15:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 7));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(423), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload VII\n§6Implants III\n§6Tank III\n§eAngelic III\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = Item::get(279, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 9));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(138), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§l§5Mythic §6Axe§r\n§6Insanity IV\n§eRage II\n§eLumberjack I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 17:
                    $item = ItemUtils::get("common_book");
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    $item = ItemUtils::get("uncommon_book");
                    $player->getInventory()->addItem($item);
                    break;
                    case 19:
                    $bonus = rand(100000, 250000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 20:
                    $item = ItemUtils::get("book", "", [], ["smelting" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 21:
                    $item = ItemUtils::get("book", "", [], ["driller" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 22:
                    $item = ItemUtils::get("book", "", [], ["focused" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 23:
                    $item = ItemUtils::get("uncommon_book");
                    $player->getInventory()->addItem($item);
                    break;
                    case 24:
                    $item = Item::get(258, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
                    $item->setCustomName("§r§eAxe");
                    $player->getInventory()->addItem($item);
                    break;
                    case 25:
                    $item = ItemUtils::get("book", "", [], ["backstab" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 26:
                    $item = ItemUtils::get("experience_bottle2(100)");
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    $item = ItemUtils::get("book", "", [], ["gravity" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 28:
                    $item = ItemUtils::get("uncommon_book");
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = ItemUtils::get("book", "", [], ["radar" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = ItemUtils::get("uncommon_book")->setCount(2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 31:
                    $item = Item::get(266, 0, 128);
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    $item = Item::get(400, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 33:
                    $item = ItemUtils::get("book", "", [], ["angelic" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(400, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = ItemUtils::get("rare_book")->setCount(2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $item = Item::get(57, 0, 64);
                    $item2 = Item::get(276, 0, 2);
                    $item3 = Item::get(311, 0, 1);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    break;
                    case 37:
                    $item = Item::get(466, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload V\n§6Implants III\n§6Tank III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = Item::get(322, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = ItemUtils::get("book", "", [], ["soulbound" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    $item = Item::get(129, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 42:
                    $item = Item::get(276, 0, 2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    break;
                    case 44:
                    $item = ItemUtils::get("book", "", [], ["revive" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $item = Item::get(391, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 46:
                    $item = Item::get(368, 0, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = Item::get(292, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    $item = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($item);
                    break;
                    case 49:
                    $item = ItemUtils::get("book", "", [], ["cursed" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = Item::get(372, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 51:
                    $item = Item::get(352, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $item = ItemUtils::get("book", "", [], ["defense" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = Item::get(276, 0, 1);
		            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 8));
			    	$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(141), 4));
                    $item->setCustomName("§r§cFire Sword§r\n§eDemise IV\n§eAutorepair II\n§eRage III\n§eBlessed III\n§bLifesteal V");
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = Item::get(4, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $item = ItemUtils::get("book", "", [], ["blessed" => 2]);
                    $player->getInventory()->addItem($item);
                    case 56:
                    $item = ItemUtils::get("book", "", [], ["shrink" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = ItemUtils::get("book", "", [], ["gears" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = ItemUtils::get("book", "", [], ["angel" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $item = Item::get(466, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    case 61:
                    $item = Item::get(46, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 62:
                    $item = Item::get(357, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 63:
                    $item = Item::get(323, 0, 10);
                    $player->getInventory()->addItem($item);
                    $event->setCancelled(true);
                    break;
                    case 64:
                    $item = ItemUtils::get("book", "", [], ["angel" => 2]);
                    $player->getInventory()->addItem($item);
                    case 65:
                    $item = Item::get(312, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
		 			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 4));
		          	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 2));
		        	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 2));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 2));
                    $item->setCustomName("§r§bDiamond Leggings\n§6Overload II\n§6Armored II\n§6Heavy II\n§eDrunk IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 66:
                    case 67:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 1));
                    $item->setCustomName("§r§l§4Chaos §6Saber §eSword§r\n§6Deathbringer III\n§eRage I\n§bLifesteal III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 68:
                    $item = ItemUtils::get("harpy_note");
                    $player->getInventory()->addItem($item);
                    break;
                    case 69:
                    $item = ItemUtils::get("book", "", [], ["moneyfarm" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 70:
                    $item = Item::get(334, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 71:
                    $item = Item::get(325, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 72:
                    $item = ItemUtils::get("experience_bottle2(102)")->setCount(3);
                    $player->getInventory()->addItem($item);
                    break;
                    case 73:
                    $item = Item::get(373, 33, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 74:
                    $item = Item::get(283, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 2));
                    $item->setCustomName("§r§eShine Gold Sword\n§6Deathbringer III\n§eObliterate II\n§eBlessed II\n§bLifesteal III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 75:
                    $item = Item::get(266, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 76:
                    $item = Item::get(397, 5, 1);
                    $player->getInventory()->addItem($item);
                    break;
					case 77:
					$item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
                    $player->getInventory()->addItem($item);
					break;
					case 78:
					$item = Item::get(455, 0, 1);
                    $player->getInventory()->addItem($item);
					break;
					case 79:
					$item = Item::get(396, 0, 10);
                    $player->getInventory()->addItem($item);
					break;
					case 80:
                    $item = ItemUtils::get("experience_bottle2(102)")->setCount(\kenygamer\Core\Main::mt_rand(5, 10));
                    $player->getInventory()->addItem($item);
                    break;
					case 81:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
					case 82:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
					case 83:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
					case 84:
                    $item = ItemUtils::get("book", "", [], ["nutrition" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
					case 85:
					$item = Item::get(396, 0, \kenygamer\Core\Main::mt_rand(10, 20));
                    $player->getInventory()->addItem($item);
					break;
					case 86:
					$item = ItemUtils::get("book", "", [], ["drain" => 1]);
					$player->getInventory()->addItem($item);
					break;
					case 87:
					$item = ItemUtils::get("book", "", [], ["drain" => 4]);
					$player->getInventory()->addItem($item);
					break;
				}
			}
		}
        if ($block->getId() === Block::CHEST || $block->getId() == Block::TRAPPED_CHEST) {
            if ($item->getId() == Item::SLIMEBALL and $item->getDamage() == 6) { //Legendary Key
                $player->getInventory()->removeItem(Item::get(341, 6, 1));
                $event->setCancelled();
                $reward = rand(1, 127);
                switch($reward) {
                    case 1:
                    $item = Item::get(266, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 2:
                    $item = Item::get(264, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 3:
                    $item = ItemUtils::get("experience_bottle2(102)")->setCount(5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 4:
                    $item = Item::get(322, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 5:
                    case 6:
                    $item = ItemUtils::get("book", "", [], ["shrink" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 7:
                    $item = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(3, 5));
                    $player->getInventory()->addItem($item);
                    break;
                    case 8:
                    $item = Item::get(57, 0, 20);
                    $player->getInventory()->addItem($item);
                    break;
                    case 9:
                    $item = ItemUtils::get("book", "", [], ["soulbound" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 10:
                    $item = ItemUtils::get("book", "", [], ["gears" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 11:
                    $item = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 12:
                    $item = Item::get(264, 0, \kenygamer\Core\Main::mt_rand(30, 50));
                    $player->getInventory()->addItem($item);
                    break;
                    case 13:
                    $bonus = rand(150000, 300000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 14:
                    $item = ItemUtils::get("book", "", [], ["autorepair" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 15:
                    $item = ItemUtils::get("book", "", [], ["overload" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $item = ItemUtils::get("book", "", [], ["blaze" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 17:
                    $item = Item::get(280, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), \kenygamer\Core\Main::mt_rand(1, 2)));
                    $item->setCustomName("§r§bPower Stick");
                    $player->getInventory()->addItem($item);
                    break;
                    case 18:
                    $item = ItemUtils::get("book", "", [], ["shuffle" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 19:
                    $item = ItemUtils::get("enchanted_diamond_apple")->setCount(5);
                    break;
                    case 20:
                    $item = Item::get(399, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 21:
                    break;
                    case 22:
                    $item = Item::get(14, 0, 50);
                    $item2 = Item::get(15, 0, 50);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $event->setCancelled(true);
                    break;
                    case 23:
                    $item = Item::get(138, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 24:
                    $bonus = 50000;
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 25:
                    $item = Item::get(81, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 26:
                    $item = Item::get(322, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    case 28:
                    $item = Item::get(7, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 29:
                    $item = ItemUtils::get("book", "", [], ["headhunter" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 30:
                    $item = Item::get(246, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 31:
                    $item = Item::get(293, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 32:
                    $item = ItemUtils::get("book", "", [], ["molten" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 33:
                    $item = ItemUtils::get("book", "", [], ["volley" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 34:
                    $item = Item::get(313, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 35:
                    $item = ItemUtils::get("book", "", [], ["haste" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 36:
                    $item = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(3, 6));
                    $player->getInventory()->addItem($item);
                    break;
                    case 37:
                    $item = ItemUtils::get("book", "", [], ["hallucination" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 38:
                    $item = ItemUtils::get("book", "", [], ["wither" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 39:
                    $item = Item::get(22, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 40:
                    $item = Item::get(14, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 41:
                    $item = ItemUtils::get("book", "", [], ["angelic" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 42:
                    $item = ItemUtils::get("book", "", [], ["rage" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 43:
                    $item = ItemUtils::get("book", "", [], ["jetpack" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 44:
                    $item = Item::get(322, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 45:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 4, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 46:
                    $item = Item::get(3, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = ItemUtils::get("book", "", [], ["lifesteal" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    $item = ItemUtils::get("book", "", [], ["obsidianshield" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 49:
                    $item = Item::get(265, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 50:
                    $item = Item::get(400, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 51:
                    $item = ItemUtils::get("book", "", [], ["lifesteal" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $item = Item::get(7, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 53:
                    $item = Item::get(129, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 54:
                    $item = ItemUtils::get("book", "", [], ["bleeding" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $item = ItemUtils::get("book", "", [], ["armored" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 56:
                    $item = ItemUtils::get("book", "", [], ["drunk" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 57:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 2));
                    $item->setCustomName("§r§4Shadow §eHelmet\n§6Overload III\n§6Implants II\n§6Tank V");
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = Item::get(388, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $item = ItemUtils::get("book", "", [], ["glowing" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 60:
                    $item = ItemUtils::get("experience_bottle2(101)")->setCount(2);
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    $item = ItemUtils::get("book", "", [], ["gravity" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 62:
                    $item = Item::get(322, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 63:
                    $item = Item::get(354, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 64:
                    $item = ItemUtils::get("book", "", [], ["angel" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 65:
                    $item = ItemUtils::get("book", "", [], ["antiknockback" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 66:
                    $item = Item::get(466, 0, 10);
                    $player->getInventory()->addItem($item);
                    break;
                    case 67:
                    $item = Item::get(264, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 68:
                    $item = Item::get(399, 0, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 69:
                    $item = Item::get(274, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 70:
                    $item = ItemUtils::get("book", "", [], ["driller" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 71:
                    $item = ItemUtils::get("book", "", [], ["blind" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 72:
                    $item = Item::get(377, 0, 30);
                    $player->getInventory()->addItem($item);
                    break;
                    case 73:
                    $item = Item::get(49, 0, 40);
                    $player->getInventory()->addItem($item);
                    break;
                    case 74:
                    $item = Item::get(261, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(19), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(22), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(301), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(311), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(316), 3));
                    $item->setCustomName("§r§l§cDivine Bow§r\n§6Bow Lightning V\n§6Blaze I\n§6Longbow III\n§eVolley II");
					$item2 = Item::get(262, 0, 128);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 75:
                    $item = ItemUtils::get("book", "", [], ["disarmor" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 76:
                    $item = ItemUtils::get("book", "", [], ["remedy" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 77:
                    $item = ItemUtils::get("book", "", [], ["disarmor" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 78:
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(9), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(12), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(119), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 2));
                    $item->setCustomName("§r§l§6Legendary Sword§r\n§cExcalibur II\n§6Soulbound II\n§6Hallucination II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 79:
                    break;
                    case 80:
                    $item = ItemUtils::get("book", "", [], ["bountyhunter" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 81:
                    case 82:
                    $item = ItemUtils::get("book", "", [], ["cursed" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 83:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(15), 7));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(203), 2));
                    $item->setCustomName("§r§bDiamond Pickaxe§r\n§eAutorepair II\n§bQuickening II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 84:
                    $item = ItemUtils::get("book", "", [], ["divine" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 85:
                    $item = Item::get(46, 0, 50);
                    $item2 = Item::get(259, 0, 1);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 86:
                    $item = Item::get(352, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 87:
                    $item = Item::get(264, 0, 64);
                    $player->getInventory()->addItem($item);
                    break;
                    case 88:
                    $bonus = rand(150000, 250000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 89:
                    $bonus = rand(10000, 50000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 90:
                    $item = ItemUtils::get("fury_note");
                    $player->getInventory()->addItem($item);
                    break;
                    case 91:
                    $item = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(5, 9)]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 92:
                    $item = Item::get(373, 6, 5);
                    $player->getInventory()->addItem($item);
                    break;
                    case 93:
                    $item = Item::get(7, 0, 50);
                    $player->getInventory()->addItem($item);
                    break;
                    case 94:
                    $item = Item::get(49, 0, 100);
                    $item2 = Item::get(57, 0, 100);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 95:
                    $item = Item::get(266, 0, 64);
                    $item2 = Item::get(265, 0, 64);
                    $item3 = Item::get(264, 0, 64);
                    $item4 = Item::get(351, 4, 64);
                    $item5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 96:
                    $bonus = rand(10000, 50000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 97:
                    $item = ItemUtils::get("book", "", [], ["enraged" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 98:
                    $bonus = rand(10000, 50000);
                    $player->sendMessage("money-bonus", $bonus);
                    $player->addMoney($bonus);
                    break;
                    case 99:
                    $item = Item::get(373, 13, 3);
                    $player->getInventory()->addItem($item);
                    case 100:
                    $item = Item::get(322, 0, 64);
                    $item2 = Item::get(466, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 101:
                    $item = Item::get(359, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 2));
                    $item->setCustomName("§r§bShears\n§eAutorepair II\n§bHaste II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 102:
                    $item = ItemUtils::get("mythic_note(53)")->setCount(\kenygamer\Core\Main::mt_rand(2, 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 103:
                    $item = ItemUtils::get("book", "", [], ["rage" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 104;
                    $item = Item::get(276, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(130), 5));
                    $item->setCustomName("§r§l§cDragon Sword§r\n§cExcalibur III\n§6Drain V\n§eRage III\n§eBlessed III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 105:
                    $item = ItemUtils::get("book", "", [], ["bountyhunter" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 106:
                    $item = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($item);
                    break;
                    case 107:
                    $item = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
                    $player->getInventory()->addItem($item);
                    break;
                    case 108:
                    $item = ItemUtils::get("book", "", [], ["bountyhunter" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 109:
                    $bonus = 1000000;
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 110:
                    $item = ItemUtils::get("rare_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
                    $player->getInventory()->addItem($item);
                    break;
                    case 111:
                    $item = ItemUtils::get("book", "", [], ["divine" => 1]);
                    $player->sendMessage("core-bonus-ce");
                    $player->getInventory()->addItem($item);
                    break;
                    case 112:
                    $item = ItemUtils::get("mythic_note(53)");
                    $player->getInventory()->addItem($item);
                    break;
                    case 113:
                    $item = ItemUtils::get("book", "", [], ["haste" => 5]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 114:
                    $item = ItemUtils::get("book", "", [], ["bleeding" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
                    case 115:
                    $item = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
                    $player->getInventory()->addItem($item);
                    break;
					case 116:
                    $item = ItemUtils::get("book", "", [], ["bleeding" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
					case 117:
                    $item = ItemUtils::get("book", "", [], ["freeze" => 1]);
                    $player->getInventory()->addItem($item);
                    break;
					case 118:
                    $item = ItemUtils::get("book", "", [], ["freeze" => 2]);
                    $player->getInventory()->addItem($item);
                    break;
					case 119:
                    $item = ItemUtils::get("book", "", [], ["freeze" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
					case 120:
                    $tier = ItemUtils::get("knight_note");
                    $player->getInventory()->addItem($tier);
                    break;
					case 121:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 4]);
                    $player->getInventory()->addItem($item);
                    break;
					case 122:
                    $item = ItemUtils::get("book", "", [], ["nautica" => 3]);
                    $player->getInventory()->addItem($item);
                    break;
					case 123:
                    $item = ItemUtils::get("book", "", [], ["nautica" => \kenygamer\Core\Main::mt_rand(3, 5)]);
                    $player->getInventory()->addItem($item);
                    break;
					case 124:
                    $item = ItemUtils::get("book", "", [], ["nutrition" => \kenygamer\Core\Main::mt_rand(3, 5)]);
                    $player->getInventory()->addItem($item);
                    break;
					case 125:
					$item = Item::get(396, 0, \kenygamer\Core\Main::mt_rand(25, 45));
                    $player->getInventory()->addItem($item);
					break;
					case 126:
					$item = ItemUtils::get("yellow_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
                    $player->getInventory()->addItem($item);
					break;
					case 127:
					$item = ItemUtils::get("green_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 2));
                    $player->getInventory()->addItem($item);
					break;
				}
			}
		}
		
		//Color Keys
		if (($block->getId() === Block::CHEST || $block->getId() === Block::TRAPPED_CHEST) && $block->getLevel()->getFolderName() === "hub") {
			if ($block->floor() === $this->crates["yellow"]){
 				$reward = rand(1, 20);
 				$player->getInventory()->removeItem(Item::get(341, 11, 1));
 				$event->setCancelled();
                switch($reward) {
					case 1:
					case 2:
					$tier = Item::get(276, 0, 1);
		        	$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
		        	$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
		         	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(117), 10));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 5));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 5));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 5));
		         	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
		        	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(128), 10));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(133), 1));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
                    $tier->setCustomName("§r§l§eTarta§6rus §bSword§r\n§cHellforged I\n§cExcalibur V\n§6Soulbound V\n§6Disarm Protection I\n§6Disarmor X\n§eDisarming X\n§eAutorepair V\n§eBlessed III\n§eRage V");	
			        $tier->setLore([
			        '§r§a+5 Attack Damage'
			        ]);
                    $player->getInventory()->addItem($tier);
					break;
					case 3:
					$tier = ItemUtils::get("knight_note");
                    $player->getInventory()->addItem($tier);
					break;
					case 4:
					$tier = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(0, 16));
					$player->getInventory()->addItem($tier);
					break;
					case 5:
					$book = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$player->getInventory()->addItem($book);
					break;
					case 6:
					$item = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 32));
					$player->getInventory()->addItem($item);
					break;
					case 7:
					$item = ItemUtils::get("mythic_note(53)")->setCount(\kenygamer\Core\Main::mt_rand(1, 30));
					$player->getInventory()->addItem($item);
					break;
					case 8:
					$item = Item::get(57, 0, 128);
					$player->getInventory()->addItem($item);
					break;
					case 9:
					$book = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 10)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 10:
					$item = Item::get(278, 0, 1);
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
                    $item->setCustomName("§r§l§eTarta§6rus §bPickaxe§r\n§6Soulbound V\n§6Keyplus III\n§6Treasure Hunter III\n§6Money Farm VII\n§6Grind VII\n§6Miner Luck V\n§eAutorepair V\n§eDriller V\n§bHaste V");
                    $player->getInventory()->addItem($item);
					break;
					case 11:
					$book = ItemUtils::get("book", "", [], ["antitrap" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 12:
					$item = ItemUtils::get("book", "", [], ["nutrition" => \kenygamer\Core\Main::mt_rand(1, 3)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 13:
					case 14:
					$item = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 15:
					$item = ItemUtils::get("book", "", [], ["insanitt" => \kenygamer\Core\Main::mt_rand(1, 6)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 16:
					$book = ItemUtils::get("book", "", [], ["keyplus" => \kenygamer\Core\Main::mt_rand(1, 4)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 17:
					$book = ItemUtils::get("book", "", [], ["keyplus" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 18:
					case 19:
					$item = ItemUtils::get("book", "", [], ["grind" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 20:
					$item = ItemUtils::get("book", "", [], ["grind" => \kenygamer\Core\Main::mt_rand(1, 8)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 21: 
					$item = ItemUtils::get("lemon")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
				}
			}
			if ($item->getId() === Item::SLIMEBALL && $item->getDamage() == 12 && $block->floor() === $this->crates["red"]){
				$reward = \kenygamer\Core\Main::mt_rand(1, 19);
				$player->getInventory()->removeItem(Item::get(341, 12, 1));
			    $event->setCancelled();
                switch($reward) {
                    case 1:
					$tier = ItemUtils::get("rainbow_lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 25));
                    $player->getInventory()->addItem($tier);
					break;
					case 2:
					$tier = Item::get(276, 0, 1);
		        	$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
		        	$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
		         	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(117), 10));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 5));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(122), 5));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 5));
		         	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
		        	$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(128), 10));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(133), 1));
			        $tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
                    $tier->setCustomName("§r§l§eTarta&6rus §bSword§r\n§cHellforged I\n§cExcalibur V\n§6Soulbound V\n§6Disarm Protection I\n§6Disarmor X\n§eDisarming X\n§eAutorepair V\n§eBlessed III\n§eRage V");	
			        $tier->setLore([
			        '§r§a+5 Attack Damage'
			        ]);
                    $player->getInventory()->addItem($tier);
					break;
					case 3:
                    $tier = ItemUtils::get("knight_note");
                    $player->getInventory()->addItem($tier);
					break;
					case 4:
					$tier = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
					$player->getInventory()->addItem($tier);
					break;
					case 5:
					$book = ItemUtils::get("book", "", [], ["angelic" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 6:
					$book = ItemUtils::get("book", "", [], ["naturewrath" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 7:
					$book = ItemUtils::get("book", "", [], ["autorepair" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 8:
                    $tier = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 32));
                    $player->getInventory()->addItem($tier);
					break;
					case 9:
					$item = Item::get(57, 0, 64);
					$player->getInventory()->addItem($item);
					break;
					case 10:
					$book = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 8)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 11:
                    $book = ItemUtils::get("book", "", [], ["nautica" => \kenygamer\Core\Main::mt_rand(1, 6)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
                    break;
					case 12:
                    $book = ItemUtils::get("book", "", [], ["nautica" => \kenygamer\Core\Main::mt_rand(1, 7)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                     $player->getInventory()->addItem($book);
                    break;
					case 13:
					$book = ItemUtils::get("book", "", [], ["insanity" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 14:
					$book = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 2)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 15:
					$item = ItemUtils::get("book", "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 8)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 16:
					case 17:
					case 18:
					$item = ItemUtils::get("book", "", [], ["endershift" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 19:
					$item = ItemUtils::get("book", "", [], ["endershift" => \kenygamer\Core\Main::mt_rand(1, 2)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
				}
			}
			if ($item->getId() === Item::SLIMEBALL and $item->getDamage() == 13 && $block->floor() === $this->crates["green"]){
			    $player->getInventory()->removeItem(Item::get(341, 13, 1));
			    $event->setCancelled();
				$reward = rand(1, 18);
                switch($reward) {
                    case 1:
					$item = ItemUtils::get("rainbow_lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
                    $player->getInventory()->addItem($item);
					break;
					case 2:
					$item = Item::get(369, 0, 10);
                    $player->getInventory()->addItem($item);
					break;
					case 3:
					$item = ItemUtils::get("knight_note");
                    $player->getInventory()->addItem($item);
					break;
					case 4:
					$item = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 5:
					$item = Item::get(7, 0, 64);
                    $player->getInventory()->addItem($item);
					break;
					case 6:
					$book = ItemUtils::get("book", "", [], ["naturewrath" => \kenygamer\Core\Main::mt_rand(1, 2)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 7:
					$book = ItemUtils::get("book", "", [], ["autorepair" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
					case 8:
                    $item = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(0, 30));
                    $player->getInventory()->addItem($item);
					break;
					case 9:
					$item = Item::get(57, 0, 64);
					$player->getInventory()->addItem($item);
					break;
					case 10:
					$book = ItemUtils::get("book", "", [], ["moneyfarm" => \kenygamer\Core\Main::mt_rand(1, 10)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
					break;
                    case 11:
					case 12:
                    $item = ItemUtils::get("book", "", [], ["nautica" => \kenygamer\Core\Main::mt_rand(1, 7)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 13:
					$item = ItemUtils::get("book", "", [], ["lifesteal" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 14:
					$tier = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 15));
                    $player->getInventory()->addItem($tier);
					break;
					case 15:
					$item = ItemUtils::get("book", "", [], ["warmer" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 16:
					case 17:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 6)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 18:
					$item = ItemUtils::get("book", "", [], ["evasion" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
				}
			}
			if ($item->getId() === Item::SLIMEBALL and $item->getDamage() === 15 && $block->floor() === $this->crates["purple"]){
			    $player->getInventory()->removeItem(Item::get(341, 14, 1));
			    $event->setCancelled();
				$reward = rand(1, 25);
                switch($reward) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["evasion" => \kenygamer\Core\Main::mt_rand(1, 6)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["blind" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["naturewrath" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["armored" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["grind" => \kenygamer\Core\Main::mt_rand(1, 10)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 6:
					$item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(212), 8));
                    $item->setCustomName("§r§l§6Legendary Pickaxe§r\n§6Money Farm VIII\n§bHaste IV");
                    $player->getInventory()->addItem($item);
					break;
					case 7:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $player->getInventory()->addItem($item);
					break;
					case 8:
					$item = ItemUtils::get("enchanted_diamond_apple")->setCount(10);
					$player->getInventory()->addItem($item);
					break;
					case 9:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 7)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 10:
					$item = ItemUtils::get("book", "", [], ["driller" => \kenygamer\Core\Main::mt_rand(1, 4)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 11:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => \kenygamer\Core\Main::mt_rand(1, 3)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 12:
					$item = Item::get(57, 0, 128);
                    $player->getInventory()->addItem($item);
					break;
					case 13:
					$tier = ItemUtils::get("rainbow_lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($tier);
					break;
					case 14:
					$item = ItemUtils::get("book", "", [], ["shockwave" => 2])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 15:
					$item = Item::get(276, 0, 1);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(12), 1));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(107), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(123), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(130), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 1));
                    $item->setCustomName("§r§l§aSword of a Thousand Truths§r\n§cShockwave II\n§6Curse II\n§6Drain V\n§eObliterate I");
                    $player->getInventory()->addItem($item);
					break;
					case 16:
					$item = Item::get(267, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(116), 1));
                    $item->setCustomName("§r§bIron Sword\n§eHeadless I");
                    $player->getInventory()->addItem($item);
					break;
					case 17:
					$item = ItemUtils::get("book", "", [], ["evasion" => 4])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 18:
					$item = ItemUtils::get("book", "", [], ["evasion" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 19:
					$item = Item::get(283, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
                    $item->setCustomName("§r§l§6Divine Gold Sword Legendary§r\n§6Deathbringer VI\n§eAutorepair V\n§eRage III\n§eBlessed III\n§eCripple IV\n§bLifesteal V");
					$player->getInventory()->addItem($item);
					break;
					case 20:
					$item = ItemUtils::get("book", "", [], ["shockwave" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 21:
					$item = ItemUtils::get("mythic_note(50)")->setCount(20);
					$player->getInventory()->addItem($item);
					$event->setCancelled(true);
					break;
					case 22:
					$item = ItemUtils::get("book", "", [], ["armored" => 3])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 23:
					$item = ItemUtils::get("book", "", [], ["disarmprotection" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 24:
					$item = ItemUtils::get("book", "", [], ["bleeding" => 3])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 25:
					$item = ItemUtils::get("book", "", [], ["bleeding" => 2])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
				}
			}
			if ($item->getId() == 341 and $item->getDamage() == 11 && $block->floor() === $this->crates["yellow"]) {
				$reward = rand(1, 16);
                switch($reward) {
					case 1:
					$item = ItemUtils::get("book", "", [], ["evasion" => \kenygamer\Core\Main::mt_rand(1, 6)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 2:
					$item = ItemUtils::get("book", "", [], ["blind" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 3:
					$item = ItemUtils::get("book", "", [], ["naturewrath" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 4:
					$item = ItemUtils::get("book", "", [], ["armored" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 5:
					$item = ItemUtils::get("book", "", [], ["grind" => 10])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 6:
					$item = Item::get(279, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(138), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $item->setCustomName("§r§bDiamond Axe\n§6Insanity V\n§eRage III\n§eLumberjack I");
                    $player->getInventory()->addItem($item);
					break;
					case 7:
					$item = Item::get(7, 0, \kenygamer\Core\Main::mt_rand(30, 64));
                    $player->getInventory()->addItem($item);
					break;
					case 8:
					$item = ItemUtils::get("enchanted_diamond_apple")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
					$player->getInventory()->addItem($item);
					case 9:
					$item = ItemUtils::get("book", "", [], ["drain" => \kenygamer\Core\Main::mt_rand(1, 7)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 10:
					$item = $item = ItemUtils::get("book", "", [], ["shockwave" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 11:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => 3])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
					break;
					case 12:
					case 13:
					$tier = ItemUtils::get("rainbow_lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($tier);
					break;
					case 14:
                    $item = Item::get(261, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 10));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(316), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 5));
                    $item->setCustomName("§r§l§9Bow§r\n§6Longbow V\n§eVolley V\n§bBow Lifesteal V");
					$item2 = Item::get(262, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
					case 15:
					$item = Item::get(276, 0, 1);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(123), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(130), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 2));
                    $item->setCustomName("§r§l§aDiamond Sword§r\n§6Curse II\n§6Drain VIII\n§eObliterate II");
                    $player->getInventory()->addItem($item);
                    case 16:
                    $player->getInventory()->addItem(ItemUtils::get("mining_mask(1)"));
					break;
				}
			}
		}
	}
}