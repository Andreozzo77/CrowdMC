<?php

declare(strict_types=1);

namespace kenygamer\Core\duel;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;

/**
 * Notifies the player of the duel results with an interactive UI
 *
 * @class DuelNotifyResultTask
 */
final class DuelNotifyResultTask extends Task{
	/** @var Player */
	private $player;
	/** @var string */
	private $result;
	/** @var Item[] */
	private $opponentInventory;
	/** @var string */
	private $opponentName;
	/** @var int */
	private $duelType;
	
	public function __construct(Player $player, string $result, array $opponentInventory, string $opponentName, int $duelType){
		$this->player = $player;
		$this->result = $result;
		$this->opponentInventory = $opponentInventory;
		$this->opponentName = $opponentName;
		$this->duelType = $duelType;
	}
	
	public function onRun(int $currentTick) : void{
		$this->sendResult();
	}
	
	private function sendResult() : void{
		if($this->player->isOnline()){
			$form = new SimpleForm(function(Player $player, ?int $option){
				if($option !== null){
					switch($option){
						case 0:
						    if($this->result === DuelListener::RESULT_LOST){
						    	$this->player->getServer()->dispatchCommand($this->player, "duel " . $this->opponentName . " " . strval($this->duelType));
						    }else{
						    	$scores = [];
						    	$plugin = $this->player->getServer()->getPluginManager()->getPlugin("Core");
						    	foreach($plugin->stats->getAll() as $player => $stats){
						    		if(isset($stats[$this->duelType . "duelScore"])){
						    			$scores[$player] = $stats[$this->duelType . "duelScore"];
						    		}
						    	}
						    	if(count($scores) < 1){
						    		$this->sendResult();
						    	}else{
						    		$form = new SimpleForm(function(Player $player, ?int $rank){
						    			if($player->isOnline()){
						    				$this->sendResult();
						    			}
						    		});
						    		$form->setTitle(LangManager::translate("duel-topscores-title", $this->player, ($duelName = $plugin->getDuelName($this->duelType))));
						    		asort($scores);
						    		$scores = array_reverse($scores, true);
						    		array_splice($scores, 10);
						    		foreach($scores as $player => $score){
						    			$form->addButton(LangManager::translate("duel-topscores-score", $player, $score));
						    		}
						    		$form->sendToPlayer($this->player);
						    	}
						    }
						    break;
						case 1:
						    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
						    $menu->setName(LangManager::translate("duel-inventory", $this->player, $this->opponentName));
						    $menu->getInventory()->setContents($this->opponentInventory);
						    $menu->setListener(InvMenu::readonly());
						    $menu->send($player);
						    $menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory){
						    	$this->sendResult();
						    });
						    break;
						case 2:
						    $this->player->getServer()->dispatchCommand($this->player, "duel");
						    break;
						default:
						    //exit button
					}
				}
			});
			$form->setTitle(TextFormat::colorize($this->result));
			if($this->result === DuelListener::RESULT_LOST){
				$form->addButton(LangManager::translate("duel-result-1-1", $this->player), 0, "textures/items/diamond_sword");
			}else{
				$form->addButton(LangManager::translate("duel-result-1-2", $this->player), 0, "textures/items/brewing_stand");
			}
			$form->addButton(LangManager::translate("duel-result-2", $this->player), 0, "textures/items/diamond_chestplate");
			$form->addButton(LangManager::translate("duel-result-3", $this->player), 0, "textures/items/diamond_sword");
			$form->addButton(LangManager::translate("exit", $this->player), 0, "textures/blocks/barrier");
			$form->sendToPlayer($this->player);
		}
	}
}