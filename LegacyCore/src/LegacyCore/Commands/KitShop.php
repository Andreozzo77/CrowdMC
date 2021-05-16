<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;

class KitShop extends PluginCommand{

    public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Buy a kit");
        $this->setUsage("/kitshop");
        $this->setPermission("core.command.kitshop");
        
        $kits = $plugin->getResource("kitshop.yml");
        $kitList = yaml_parse(stream_get_contents($kits));
        @fclose($kits);
        $this->kits = [];
        foreach($kitList as $kitName => $data){
        	$this->kits[$kitName] = new \StdClass();
        	$this->kits[$kitName]->items = ItemUtils::parseItems($data["items"]);
        	$this->kits[$kitName]->price = $data["price"];
        }
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$sender->hasPermission("core.command.kitshop")){
            LangManager::send("cmd-noperm", $sender);
            return false;
        }
        if($sender instanceof ConsoleCommandSender){
            LangManager::send("run-ingame", $sender);
            return false;
        }
        $this->ShopUI($sender);
        return true;
    }

    /**
     * @param Player $player
     */
    public function ShopUI(Player $player) : void{
        $form = new SimpleForm(function(Player $player, ?int $result){
        	$kits = array_values($this->kits);
        	if(isset($kits[$result])){
        		$kit = $kits[$result];
        		$kitName = array_keys($this->kits)[$result];
        		$form = new CustomForm(function(Player $player, ?array $data) use($kit, $kitName){
        			if(is_array($data)){
        				$amount = $data[1] ?? 1;
        				$price = $kit->price * $amount;
        				$items = [];
        				for($i = 0; $i < $amount; $i++){
        					foreach($kit->items as $item){
        						$items[] = $item;
        					}
        				}
        				if($player->getMoney() - $price < 0){
        					$player->sendMessage("money-needed-more", $price - $player->getMoney());
        					return;
        				}
        				if(ItemUtils::addItems($player->getInventory(), ...$items)){
        					$player->reduceMoney($price);
        					$player->sendMessage("core-kitshop-buy", $kitName, $price);
        				}else{
        					$player->sendMessage("inventory-nospace");
        				}
        			}elseif($player->isOnline()){
        				$this->ShopUI($player);
        			}
        		});
        		$form->setTitle(LangManager::translate("core-kit2-title", $player, $kitName));
        		$form->addLabel(LangManager::translate("core-kitshop-kit2", $player, ItemUtils::getDescription($kit->items), $kit->price));
        		$form->addSlider("Amount", 1, 3);
        		$player->sendForm($form);
        	}
        });
        $form->setTitle(LangManager::translate("core-kitshop-title", $player));
        $form->setContent(LangManager::translate("core-kitshop-desc", $player));
        foreach($this->kits as $kitName => $kit){
        	$form->addButton(LangManager::translate("core-kitshop-kit", $player, $kitName, $kit->price));
        }
        $player->sendForm($form);
    }
    
}