<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\inventory\Inventory;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use jojoe77777\FormAPI\SimpleForm;

class Sell extends PluginCommand{
	
	/** @var array */
	public $plugin;
	
	/** @var array<int: itemID<int: sellPrice>> */
	public static $market;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Sell the item held or all your inventory");
        $this->setUsage("/sell <hand|all>");
        $this->setAliases(["sell"]);
        $this->setPermission("core.command.sell");
		$this->plugin = $plugin;
		self::$market = (new Config($this->plugin->getDataFolder() . "sell.yml"))->getAll();
		if(!class_exists(Main2::class) || !isset(Main2::$shop)){
			return;
		}
		foreach(self::$market as $item => $price){
			if(count($parts = explode(":", $item)) < 2){
				unset(self::$market[$item]);
				$item = $item . ":0";
				self::$market[$item] = $price;
			}
			list($id, $meta) = explode(":", $item);
			foreach(Main2::$shop as $loc => $data){
				if($data["item"] == $id && $data["meta"] == $meta && $data["price"] < $price){
					$plugin->getLogger()->error("Sell price must not be higher than buy price " . $price . "$");
					self::$market[$item] = $data["price"];
				}
			}
		}
    }
    
    /**
     * Sells your inventory.
     *
     * @param Player $player
     * @param bool $silent Whether to notify the player or not
     * @param string[] $items Format: id:meta Pass specific items to sell, or leave empty to sell everything
     */
    public static function sellAll(Player $player, bool $silent = false, array $items = []) : void{
    	foreach($items as $i => $item){
    		if(count($parts = explode(":", $item)) < 2){
    			$parts[1] = 0;
    			$items[$i] = implode(":", $parts);
    		}
    	}
    	
    	$totalprice = 0;
    	
    	$inventory = $player->getInventory();
    	$contents = $inventory->getContents();
    	
    	foreach($contents as $item){
    		if(isset(self::$market[$item->getId() . ":" . $item->getDamage()])){
    			$canSell = true;
    			if(count($items) > 0){
    				$canSell = false;
    				foreach($items as $it){
    					list($id, $meta) = explode(":", $it);
    					if($item->getId() == $id && $item->getDamage() == $meta){
    						$canSell = true;
    						break;
    					}
    				}
    			}
    			if(!$canSell){
    				continue;
    			}
    			$price = self::$market[$item->getId() . ":" . $item->getDamage()];
    			$count = 0;
    			foreach($contents as $i => $slot){
    				if($slot->equals($item)){
    					$count += $slot->getCount();
    					$slot->setCount(0);
    					$inventory->setItem($i, Item::get(Item::AIR));
                	}
				}
				$totalprice += $count * $price;
			}
		}
		if($totalprice !== 0){
			Main::getInstance()->addMoney($player->getName(), (int) $totalprice);
			if(!$silent){
				LangManager::send("core-sell-all", $player, (int) $totalprice);
			}
		}elseif(!$silent){
			LangManager::send("core-sell-all-none", $player);
		}
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.sell")) {
	      	LangManager::send("cmd-noperm", $sender);
            return true;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return true;
		}
        if (count($args) < 1) {
            return true;
        }
		if(isset($args[0])) {
			switch($args[0]) {
		        case "hand":
                $item = $sender->getInventory()->getItemInHand();
                if (isset(self::$market[$item->getID() . ":" . $item->getDamage()])){
                    $price = self::$market[$item->getID() . ":" . $item->getDamage()];
                    $count = $item->getCount();
                    $totalprice = $price * $count;
                    Main::getInstance()->addMoney($sender->getName(), (int)$totalprice);
                    $item->setCount($item->getCount() - (int)$count);
                    $sender->getInventory()->setItemInHand($item);
                    LangManager::send("core-sell-hand", $sender, $count, $item->getName(), $totalprice);
                    return true;
                }
                LangManager::send("core-sell-hand-error", $sender);
                return true;
                break;
				case "all":
				self::sellAll($sender);
                return true;
                break;
                case "list":
                if(empty(self::$market)){
                	return true;
                }
                $market = self::$market;
                asort($market);
                $market = array_reverse($market, true);
                $page = isset($args[1]) ? (int) $args[1] : 1;
                $offset = ($page - 1) * 5;
                $limit = 10;
                $pageCount = ceil(count($market) / $limit);
                if($page < 1 or $page > $pageCount){
                	LangManager::send("core-list-error", $sender, $page);
                	return true;
                }
                $form = new SimpleForm(function(Player $player, ?string $res) use($page){
                	if($res !== null){
                		if($res === "back"){
                			$player->getServer()->dispatchCommand($player, "sell list " . ($page - 1));
                		}elseif($res === "next"){
                			$player->getServer()->dispatchCommand($player, "sell list " . ($page + 1));
                		}
                	}
                });
                $form->setTitle(LangManager::translate("core-list-title", $sender, $page, $pageCount));
                $content = "";
                $pageSell = array_slice($market, $offset, $limit, true);
                $ids = array_keys($pageSell);
                foreach($pageSell as $_item => $price){
                	var_dump($_item);
                	list($id, $meta) = explode(":", $_item);
                	$item = ItemFactory::get($id, $meta);
                	$content .= LangManager::translate("core-list-item", $sender, $item->getName() . " (" . $id . ":" . $meta . ")", $price);
                	$content .= $id === end($ids) ? "" : "\n";
                }
                $form->setContent(TextFormat::colorize($content));
                if($page != $pageCount){
                	$form->addButton(LangManager::translate("core-list-next", $sender, $page + 1), -1, "", "next");
                }
                if($page !== 1){
                	$form->addButton(LangManager::translate("core-list-previous", $sender, $page - 1), -1, "", "back");
                }
                $form->sendToPlayer($sender);
                return true;
                break;
			}
		}
		return true;
	}

}