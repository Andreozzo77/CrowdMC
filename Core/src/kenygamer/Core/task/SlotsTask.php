<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\LangManager;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;

class SlotsTask extends Task{
	/** @var MiscListener */
	private $listener;
	/** @var Player */
	private $player;
	/** @var InvMenu */
	private $menu;
	/** @var int */
	private $money;
	
	/** @var Item[] */
	private $items = [];
	/** @var int[] */
	private $percentChances;
	
	public function __construct(MiscListener $listener, Player $player, int $money, InvMenu $menu){
		$this->money = $money;
		$this->listener = $listener;
		$this->player = $player;
		$this->menu = $menu;
		$this->items[] = (ItemFactory::get(Item::DIAMOND))->setCustomName(TextFormat::colorize("&r&b&lTier VI"));
		$this->items[] = (ItemFactory::get(Item::DYE, 4))->setCustomName(TextFormat::colorize("&r&9&lTier V"));
		$this->items[] = (ItemFactory::get(Item::GOLD_INGOT))->setCustomName(TextFormat::colorize("&r&e&lTier IV"));
		$this->items[] = (ItemFactory::get(Item::REDSTONE_DUST))->setCustomName(TextFormat::colorize("&r&c&lTier III"));
		$this->items[] = (ItemFactory::get(Item::IRON_INGOT))->setCustomName(TextFormat::colorize("&r&f&lTier II"));
		$this->items[] = (ItemFactory::get(263))->setCustomName(TextFormat::colorize("&r&0&lTier I"));
		
		$this->percentChances = [
		   \kenygamer\Core\Main::mt_rand(250, 300), \kenygamer\Core\Main::mt_rand(200, 250), \kenygamer\Core\Main::mt_rand(150, 200), \kenygamer\Core\Main::mt_rand(100, 150), \kenygamer\Core\Main::mt_rand(50, 100), \kenygamer\Core\Main::mt_rand(25, 50)
		];
	}
	
	public function onRun(int $currentTick) : void{
		if(isset($this->listener->playingSlots[$this->player->getName()])){
			$item = $this->items[array_rand($this->items)];
			$this->menu->getInventory()->setItem(\kenygamer\Core\Main::mt_rand(1, 3), $item);
			$i1 = $this->menu->getInventory()->getItem(1);
			$i2 = $this->menu->getInventory()->getItem(2);
			$i3 = $this->menu->getInventory()->getItem(3);
			if(!($i1->isNull() or $i2->isNull() or $i3->isNull()) && $i1->equals($i2) && $i1->equals($i3)){ //finish
				$percent = 0;
				foreach($this->items as $i => $item){
					if($item->equals($i1)){
						$percent = $this->percentChances[$i];
					}
				}
				$this->listener->plugin->getScheduler()->cancelTask($this->getTaskId());
				$this->listener->plugin->getScheduler()->scheduleDelayedTask(new class($this->listener, $this->player, $this->money, $percent, $this->menu) extends Task{
					public function __construct(MiscListener $listener, Player $player, int $money, int $percent, InvMenu $menu){
						$this->listener = $listener;
						$this->player = $player;
						$this->money = $money;
						$this->percent = $percent;
						$this->menu = $menu;
					}
					public function onRun(int $currentTick) : void{
						if($this->player->isOnline()){
							$this->listener->finishedSlots[] = $this->player->getName();
							$this->menu->onClose($this->player);
							$money = $this->money * $this->percent / 100;
							$success = \kenygamer\Core\Main::mt_rand(0, 1);
							if($success){
								$this->listener->plugin->questManager->getQuest("godly_gambler")->progress($this->player, 1);
								Main::getInstance()->addMoney($this->player, $money);
								$this->listener->casinoInfo($this->player, LangManager::translate("slots-title", $this->player), LangManager::translate("you-won", $this->player, $money, Main::getInstance()->myMoney($this->player)), 2);
							}else{
								//Workaround: if the money lost surpraises the player balance, it will not take money
								if(!Main::getInstance()->reduceMoney($this->player, $money)){
									Main::getInstance()->reduceMoney($this->player, $money = Main::getInstance()->myMoney($this->player));
								}
								$this->listener->plugin->questManager->getQuest("godly_gambler")->progress($this->player, 0);
								$this->listener->casinoInfo($this->player, LangManager::translate("slots-title", $this->player), LangManager::translate("you-lost", $this->player, $money, Main::getInstance()->myMoney($this->player)), 2);
							}
						}
					}
				}, 100);
			}
			
		}else{
			$this->listener->plugin->getScheduler()->cancelTask($this->getTaskId());
		}
	}
		
}