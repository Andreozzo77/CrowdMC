<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\inventory\PlayerInventory;
use kenygamer\Core\LangManager;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;

class TradeCommand extends BaseCommand{
	public const TRADE_DISTANCE_MAX = 3; //Blocks
	public const TRADE_TIMEOUT = 60 * 2; //Seconds
	/** @var int[] */
	public $time = [];
	/** @var SharedInvMenu[] */
	private $menu = [];
	/** @var array */
	public $trades = [];
	/** @var Item[] */
	private $clientItems = [];
	/** @var Item[] */
	private $traderItems = [];
	
	/** @var self|null */
	private static $instance = null;
	
	public function __construct(){
		parent::__construct(
			"trade",
			"Trade Command",
			"/trade <view/accept/player>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
		self::$instance = $this;
	}
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset($args[0])){
            switch($args[0]){
                case "view":
                    $tradingWith = $this->getTrade($sender->getName());
                    if(!$tradingWith){
                        $sender->sendMessage("trade-notrade");
                    }elseif(isset($this->trades[$sender->getName()])){
                        $trader = $sender->getName();
                        if(!isset($this->clientItems[$trader])){
                            $sender->sendMessage("trade-nooffer");
                            break;
                        }
						$sender->sendMessage("trade-view", $tradingWith);
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                        $this->menu[$sender->getName()] = $menu;
						
                        $menu->setListener(InvMenu::readonly());
                        $menu->setName(LangManager::translate("trade-offer", $sender, $tradingWith));
                        $menu->getInventory()->setContents($this->clientItems[$trader]);
                        $menu->send($sender);
                        $menu->setInventoryCloseListener(function(Player $trader, InvMenuInventory $inventory){
                            $trader->sendMessage("trade-complete");
                        });
                    }else{
                        $trader = array_search($sender->getName(), $this->trades);
                        if(!isset($this->traderItems[$trader])){
                            $sender->sendMessage("trade-trader-nooffer");
                            break;
                        }
						$sender->sendMessage("trade-view", $tradingWith);
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                        $this->menu[$sender->getName()] = $menu;
                        
                        $menu->setListener(InvMenu::readonly());
                        $menu->setName(LangManager::translate("trade-offer", $sender, $tradingWith));
                        $menu->getInventory()->setContents($this->traderItems[$trader]);
                        $menu->send($sender);
                        $menu->setInventoryCloseListener(function(Player $client, InvMenuInventory $inventory){
                            $client->sendMessage("trade-send");
                        });
                    }
                    break;
                case "accept":
                    $tradingWith = $this->getTrade($sender->getName());
                    if(!$tradingWith){
                        $sender->sendMessage("trade-notrade");
                    }elseif(isset($this->trades[$sender->getName()])){
                        $trader = $sender->getName();
                        if(!isset($this->traderItems[$trader])){
                            unset($this->trades[$sender->getName()]);
                            break;
                        }
                        if(!isset($this->clientItems[$trader])){
                            $sender->sendMessage("trade-client-nooffer");
                            break;
                        }
                        $client = $this->getPlugin()->getServer()->getPlayerExact($this->trades[$trader]);
                        if(!($client instanceof Player)){
                            $this->finishTrade($trader, "trade-finish-7");
                            break;
                        }
                        $clientTest = new PlayerInventory($client);
                        $clientTest->setContents($client->getInventory()->getContents());
                        $traderTest = new PlayerInventory($sender);
                        $traderTest->setContents($sender->getInventory()->getContents());
                        foreach($this->clientItems[$trader] as $item){
                            if($clientTest->first($item, true) < 0){
                                $this->finishTrade($trader, "trade-finish-1");
                                break 2;
                            }
                            $clientTest->removeItem($item);
                            if(!$traderTest->canAddItem($item)){
                                $this->finishTrade($trader, "trade-finish-4");
                                break 2;
                            }
                            $traderTest->addItem($item);
                        }
                        foreach($this->traderItems[$trader] as $item){
                            if($traderTest->first($item, true) < 0){
                                $this->finishTrade($trader, "trade-finish-2");
                                break 2;
                            }
                            $traderTest->removeItem($item);
                            if(!$clientTest->canAddItem($item)){
                                $this->finishTrade($trader, "trade-finish-3");
                                break 2;
                            }
                            $clientTest->addItem($item);
                        }
                        $client->getInventory()->setContents($clientTest->getContents());
                        $sender->getInventory()->setContents($traderTest->getContents());  
                        $this->finishTrade($trader, "trade-finish-10");
                    }else{ 
                        if(isset($this->clientItems[$tradingWith])){
                            $sender->sendMessage("trade-nochange");
                            break;
                        }
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                        $this->menu[$sender->getName()] = $menu;
                        //$menu->readonly(false);
                        $menu->setName(LangManager::translate("trade-offerto", $sender, $tradingWith));
                        $menu->send($sender);
                        $menu->setInventoryCloseListener(function(Player $client, InvMenuInventory $inventory) use($tradingWith){
                        	$items = $inventory->getContents(false);
                        	foreach($items as $item){
                        		$client->getInventory()->addItem($item);
                        	}
                        	$inventory->setContents([]);
                            if(isset($this->trades[$tradingWith])){
                                if(empty($items)){
                                    $client->sendMessage("trade-noitems");
                                    $this->finishTrade($tradingWith, "trade-finish-5");
                                }else{
                                    $this->clientItems[$tradingWith] = $items;
                                    $this->time[$tradingWith] = time();
                                    $client->sendMessage("trade-client-sent");
                                    $trader = $this->getPlugin()->getServer()->getPlayerExact($tradingWith);
                                    if($trader !== null){
										$trader->sendMessage("trade-client-target", $client->getName());
                                    }
                                }
                            }
                        });
                    }
                    break;
                default:
                    $player = $this->getPlugin()->getServer()->getPlayer($args[0]);
                    if($player === null){
                        $sender->sendMessage("player-notfound");
                        break;
                    }
                    if($player->getName() === $sender->getName()){
                        $sender->sendMessage("trade-other");
                        break;
                    }
            
                    if($tradingWith = $this->getTrade($sender->getName())){
                        $sender->sendMessage("trade-trading");
                        break;
                    }
                    if($player instanceof Player){
                        if($player->distance($sender) > self::TRADE_DISTANCE_MAX){
                            $sender->sendMessage("trade-front2");
                            break;
                        }      
                        $trader = $sender->getName();
                        $this->trades[$trader] = $player->getName();
						$sender->sendMessage("trade-trading2", $player->getName());
                        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                        $this->menu[$sender->getName()] = $menu;
                        //$menu->readonly(false);
                        $menu->setName(LangManager::translate("trade-offerto", $player->getName()));
                        $menu->send($sender);
                        $this->time[$trader] = time();
                        $menu->setInventoryCloseListener(function(Player $trader, InvMenuInventory $inventory) use($player){
                        	$items = $inventory->getContents(false);
                        	foreach($items as $item){
                        		$trader->getInventory()->addItem($item);
                        	}
                        	$inventory->setContents([]);
                            if(isset($this->trades[$trader->getName()])){
                                if(empty($items)){
                                    $trader->sendMessage("trade-noitems");
                                    $this->finishTrade($trader->getName(), "finish-trade-6"); //Trader input no offer
                                }else{
                                    $this->traderItems[$trader->getName()] = $items;
									$player->sendMessage("trade-client", $trader->getName());
									$trader->sendMessage("trade-trader", $player->getName());
                                }
                            }
                        });
                    }
            }
            return true;
        }
        if(!$this->getTrade($sender->getName())){
            $sender->sendMessage("trade-front");
			return true;
        }
        return false;
	}
	
	/**
     * @param string $player
     * @return string|false
     */
    private function getTrade(string $player){
        foreach($this->trades as $trader => $client){
            if(strcasecmp($trader, $player) === 0){
                return $client;
            }
            if(strcasecmp($client, $player) === 0){
                return $trader;
            }
        }
        return false;
    }
    
    /**
     * @param string $trader
     * @param string $reason
     */
    private function finishTrade(string $trader, string $reason = "") : void{
        $trader = mb_strtolower($trader);
        if(isset($this->trades[$trader])){
        	$client = $this->trades[$trader];
            unset($this->time[$trader]);
            unset($this->traderItems[$trader]);
            unset($this->clientItems[$trader]);
            unset($this->trades[$trader]);
            foreach([$trader, $client] as $player){
                $p = $this->getPlugin()->getServer()->getPlayerExact($player);
                if($p !== null){
					$p->sendMessage($reason);
                    if(isset($this->player_menu[$player])){
                        $menu = $this->player_menu[$player];
                        unset($this->menu[$player]); //Fix infinite recursion
                        $p = $this->getPlugin()->getServer()->getPlayerExact($player);
                        if($p !== null){
                            $menu->onClose($p);
                        }
                    }
                }
            }
        }
    }
	
}