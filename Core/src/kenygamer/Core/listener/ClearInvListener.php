<?php

declare(strict_types=1);

namespace kenygamer\Core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\inventory\CraftingGrid;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\tile\ItemFrame;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use LegacyCore\Events\Area;
use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;
use jojoe77777\FormAPI\ModalForm;

class ClearInvListener implements Listener{
    /** @var Main */
    private $plugin;
	/** @var InvMenuInventory */
	private $trash = [];
    
    /**
     * @param Main $plugin
     */
    public function __construct(Main $plugin){
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }
    
    /**
     * @param DataPacketReceiveEvent $event
     * @ignoreCancelled true
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
    	$player = $event->getPlayer();
    	$packet = $event->getPacket();
    	if($packet instanceof ItemFrameDropItemPacket){
    		$event->setCancelled();
    		$pos = new Vector3($packet->x, $packet->y, $packet->z);
    		$tile = $player->getLevel()->getTile($pos);
    		if($tile instanceof ItemFrame){
    			if($tile->hasItem() && Area::getInstance()->cmd->canEdit($player, Position::fromObject($pos, $player->getLevel()))){
    				if($player->distance($tile) <= 3 && $this->plugin->testSlot($player, 1) && ($player->getGamemode() % 2 === 0 || $player->isOp())){ //Survival, Adventure
    					$player->getInventory()->addItem($tile->getItem());
    					$tile->setItem(null);
    				}
    			}
    		}
    	}
    }
    
    /**
     * @param PlayerDropItemEvent $event
     * @priority NORMAL
     * @ignoreCancelled true
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event) : void{
        $player = $event->getPlayer();
        $item = $event->getItem();
        $event->setCancelled();
        if($this->plugin->getPlayerDuel($player) || (Main2::getBedWarsManager() && Main2::getBedWarsManager()->getArenaByPlayer($player) !== null)){
        	return;
        }
		if((isset(MiscListener2::$containerOpen[$player->getName()]) && MiscListener2::$containerOpen[$player->getName()] !== -1) || (isset(MiscListener2::$closedCraftingTable[$player->getName()]) && microtime(true) - MiscListener2::$closedCraftingTable[$player->getName()] < 0.5)){
			return;
		}
        $this->plugin->closeWindow($player, ContainerIds::INVENTORY);
        if(isset($this->plugin->trashMode[$player->getName()])){
        	LangManager::send("trash-off", $player);
        	return;
        }
        $this->plugin->scheduleDelayedCallbackTask(function() use($player, $item){
        	$menu = InvMenu::create(InvMenu::TYPE_CHEST);
        	
        	$slot = $player->getInventory()->first($item, true);
        	if($slot >= 0){
        		$player->getInventory()->setItem($slot, ItemFactory::get(Item::AIR));
        		$menu->getInventory()->addItem($item);
        	}
			$this->trash[$player->getName()] = $menu->getInventory();
        	$menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory){
				$items = $inventory->getContents(false);
				if(!$player->isOnline()){
					foreach($items as $item){
						$player->getInventory()->addItem($item);
					}
				}else{
        			$form = new ModalForm(function(Player $player, ?bool $confirm) use($items){
						unset($this->trash[$player->getName()]);
        				if($confirm){
        					Main::getInstance()->trashRollback[$player->getName()][0] = $items;
        					LangManager::send("trash-cleared", $player, count($items));
        				}else{
        					foreach($items as $item){
        						$player->getInventory()->addItem($item);
        					}
						}
					});
					$form->setTitle(LangManager::translate("trash-can", $player));
        			$form->setContent(LangManager::translate("trash-continue", $player, ItemUtils::getDescription($items)));
        			$form->setButton1(LangManager::translate("continue", $player));
        			$form->setButton2(LangManager::translate("cancel", $player));
        			$player->sendForm($form);
				}
        	});
        	$menu->setName(TextFormat::BOLD . "Trash Can");
        	$menu->send($player);
        });
    }
    
    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if(isset($this->plugin->trashRollback[$player->getName()])){
            unset($this->plugin->trashRollback[$player->getName()]);
        }
		if(isset($this->trash[$player->getName()])){
			foreach($this->trash[$player->getName()] as $item){
				$player->getInventory()->addItem($item);
			}
			unset($this->trash[$player->getName()]);
		}
    }
    
    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event) : void{
        $player = $event->getPlayer();
        if(isset($this->plugin->trashRollback[$player->getName()])){
            unset($this->plugin->trashRollback[$player->getName()]);
        }
    }
    
}