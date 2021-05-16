<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use jojoe77777\FormAPI\SimpleForm;

class Protection implements Listener{
	/** @var array */
	private $enderpearl = [];

    public function __construct(Core $plugin){
        $this->plugin = $plugin;
	}
    
    /**
     * @param PlayerCommandPreprocessEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
    	$player = $event->getPlayer();
    	$parts = explode(" ", $event->getMessage());
    	$cmd = mb_strtolower($parts[0]);
    	if(($cmd === "/pv" xor $cmd === "./pv") && !isset($parts[1])){
    		//$this->loadVaults($player);
    		//$event->setCancelled(true);
    	}
    }
	
	/**
     * @param PlayerInteractEvent $event
     * @ignoreCancelled false
     */
	public function onTouch(PlayerInteractEvent $event) : void{
		$action = $event->getAction();
	    $player = $event->getPlayer();
		$item = $event->getItem();
		
		// Vaults
		$block = $event->getBlock();
		if($block->getId() === Block::ENDER_CHEST){
			//$this->loadVaults($player);
			$event->setCancelled();
		}
		
		// Ender Pearl Cooldown
		if($item->getId() === Item::ENDER_PEARL && !$event->isCancelled() && $action === PlayerInteractEvent::RIGHT_CLICK_AIR){
			if(!in_array($player->getLevel()->getFolderName(), Main::TP_WORLDS)){
				LangManager::send("tpa-disallowed", $player);
				$event->setCancelled();
			}else{
				if(isset($this->enderpearl[$player->getName()]) && time() < ($cooldown = $this->enderpearl[$player->getName()])){
					LangManager::send("core-enderpearl-cooldown", $player, $cooldown - time());
					$event->setCancelled();
					return;
				}
				$this->enderpearl[$player->getName()] = time() + 15;
			}
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function loadVaults(Player $player) : void{
		$form = new SimpleForm(function(Player $player, ?string $vault){
			if($vault !== null){
			    $player->chat("/pv " . $vault);
			}
		});
		$form->setTitle(LangManager::translate("core-vaults-title", $player));
		$form->addButton(LangManager::translate("core-vaults-vault", $player, 1), 0, "", "1");
		if($player->hasPermission("playervaults.vault.2")){
			$form->addButton(LangManager::translate("core-vaults-vault", $player, 2), -1, "", "2");
		}
		if($player->hasPermission("playervaults.vault.3")){
			$form->addButton(LangManager::translate("core-vaults-vault", $player, 3), -1, "", "3");
		}
		$form->sendToPlayer($player);
	}
	
}