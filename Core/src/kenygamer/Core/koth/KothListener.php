<?php

declare(strict_types=1);

namespace kenygamer\Core\koth;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use kenygamer\Core\Main;
use kenygamer\Core\koth\KothTask;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class KothListener implements Listener{
	/** @var Main */
	private $plugin;
	/**
	 * @var array faction => 
	 *							[0] = gained
	 * 							[1] = lost
	 */
	public static $leaderboard = [];
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	//ASSUMED: CEs such as Disarmor, Disarming, Thief are disabled.
	//In this understanding KOTH pvp has to replicate friendly duel pvp
	//Unlike duels PVP between allies will obviously not be uncancelled if detected, this is faction between faction PVP.
	//ASSUMED: god mode is off in the koth area
	//ASSUMED: self::onEntityDamage() priority is lower to CustomListener::onEntityDamage()
	
	/**
	 * @param EntityDamageEvent $event
	 * @priority HIGH
	 * @ignoreCancelled true
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && KothTask::getInstance()->isPlaying($player)){
			if(!($event instanceof EntityDamageByEntityEvent) || !(($damager = $event->getDamager()) instanceof Player) || !KothTask::getInstance()->isPlaying($damager)){
				$event->setCancelled();
				return;
			}
			foreach([$player, $damager] as $target){
				if(!KothTask::getInstance()->hasRespawned($target)){
					$event->setCancelled();
					return;
				}
			}
			if($event->getFinalDamage() >= $player->getHealth()){
				$event->setCancelled();
				$fp = $this->plugin->getPlugin("FactionsPro");
				foreach([$damager, $player] as $check){
					if(!$fp->isInFaction($check->getName())){
						KothTask::getInstance()->removePlayer($check, "no longer in faction");
						return;
					}
				}
				$damagerFac = $fp->getPlayerFaction($damager->getName());
				$playerFac = $fp->getPlayerFaction($player->getName());
				
				$damagerStr = $fp->getFactionPower($damagerFac);
				$playerStr = $fp->getFactionPower($playerFac);
			
				$robAmount = 50;
				$robAmount += $playerStr / 100;
				$robAmount = (int) ceil($robAmount);
				
				if(!($fp->transferPower($playerFac, $damagerFac, $robAmount) < $robAmount)){
					self::$leaderboard[$playerFac][1] += $robAmount;
				    self::$leaderboard[$damagerFac][0] += $robAmount;
				    
					KothTask::getInstance()->respawn($player);
					KothTask::getInstance()->broadcastInGame("koth-kill", $player->getName(), $playerFac, $playerStr - $robAmount, $damager->getName(), $damagerFac, $damagerStr + $robAmount);
				}else{ //The rob amount was not transferred 100 %
				    $eliminated = 0;
					foreach(KothTask::getInstance()->getPlayers() as $p){
						if($fp->getPlayerFaction($p->getName()) === $playerFac){
							KothTask::getInstance()->removePlayer($p, "eliminated");
							$eliminated++;
						}
					}
					KothTask::getInstance()->broadcastInGame("koth-faceliminated", $playerFac, $eliminated);
				}
			}
		}
	}
	
	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(KothTask::getInstance()->isPlaying($player)){
			KothTask::getInstance()->removePlayer($player, "disconnect");
		}
	}
	
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		if((($slashIndex = strpos($msg, "/")) === 0 xor $slashIndex === 1) && KothTask::getInstance()->isPlaying($player)){
			//Cancel: if running game, or if it is not running game (countdown/inactive) and command used is koth
			$isRunning = KothTask::getInstance()->getStatus() === KothTask::GAME_STATUS_RUNNING;
			if($isRunning || (!$isRunning && !(substr($msg, 0, 5) === "/koth" xor substr($msg, 0, 6) === "./koth"))){
				LangManager::send("koth-nocmd", $player);
				$event->setCancelled();
			}
		}
	}
	
	/**
	 * @param PlayerChatEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		$recipients = $event->getRecipients();
		foreach($recipients as $i => $recipient){
			if($recipient instanceof Player && KothTask::getInstance()->isPlaying($recipient)){
				unset($recipients[$i]);
			}
		}
		$event->setRecipients($recipients);
		
		if(KothTask::getInstance()->isPlaying($player)){
			LangManager::send("koth-nochat", $player);
			$event->setCancelled();
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		
		if(!($item->getId() === Item::NETHER_STAR && $item->getNamedTag()->hasTag("KothLootbag", IntTag::class) && $item->getNamedTag()->getInt("KothLootbag"))){
			return;
		}
		$event->setCancelled();
		
		// - - - - -  K O T H  L O O T B A G  - - - - - 
		$helmet = ItemUtils::get(Item::DIAMOND_HELMET, "&l" . LangManager::translate("koth") . " &bHelmet", [], [
			"protection" => 7,
			"unbreaking" => 5,
			"solarpowdered" => 1,
			"nightowl" => 1,
			"divine" => 3,
			"glowing" => 1,
			"ghost" => 1,
			"overload" => 8,
			"antiknockback" => 1,
			"berseker" => 3,
			"clarity" => 1,
			"focus" => 3 
	    ]);
	    $chestplate = ItemUtils::get(Item::DIAMOND_CHESTPLATE, "&l" . LangManager::translate("koth") . " &bChestplate", [], [
	    	"protection" => 7,
	    	"unbreaking" => 5,
	    	"adhesive" => 1,
	    	"solarpowdered" => 1,
	    	"nightowl" => 1,
	    	"armored" => 4,
	    	"tank" => 4,
	    	"shielded" => 4,
	    	"overload" => 8 
	 	]);
	 	$leggings = ItemUtils::get(Item::DIAMOND_LEGGINGS, "&l" . LangManager::translate("koth") . " &bLeggings", [], [
	 		"protection" => 7,
	 		"unbreaking" => 5,
	 		"solarpowdered" => 1,
	 		"nightowl" => 1,
	 		"armored" => 4,
	 		"tank" => 4,
	 		"shielded" => 4,
	 		"overload" => 8 
	    ]);
	    $boots = ItemUtils::get(Item::DIAMOND_BOOTS, "&l" . LangManager::translate("koth") . " &bBoots", [], [
	        "protection" => 7,
	        "unbreaking" => 5,
	        "solarpowdered" => 1,
	        "nightowl" => 1,
	        "warmer" => 1,
	        "frostwalker" => 1,
	        "overload" => 8 
	    ]);
	    $axe = ItemUtils::get(Item::DIAMOND_AXE, "&l" . LangManager::translate("koth") . " &bAxe", [], [
	        "hellforged" => 2,
	        "freeze" => 4,
	        "skillswipe" => 9,
	        "hex" => 3,
	        "antitheft" => 2,
	        "bleeding" => 4,
	        "cripple" => 4,
	        "critical" => 3 
	    ]);
	    $sword = ItemUtils::get(Item::DIAMOND_SWORD, "&l" . LangManager::translate("koth") . " &bSword", [], [
	        "hellforged" => 2,
	        "freeze" => 4,
	        "skiillswipe" => 9,
	        "hex" => 3,
	        "antitheft" => 2,
	        "bleeding" => 4,
	        "cripple" => 4,
	        "critical" => 3 
	    ]);
	    $set = [$helmet, $chestplate, $leggings, $boots, $sword, $axe]; 
	    /** @var Item */
	    $piece = $set[array_rand($set)];
	    
	    $items = [
	        $piece, ItemUtils::get("atlas_gem")->setCount(\kenygamer\Core\Main::mt_rand(1, 3)), ItemUtils::get("hestia_gem")->setCount(\kenygamer\Core\Main::mt_rand(1, 2)), ItemUtils::get("mythic_note(1000000000, 2000000000)")
	    ]; //+ tokens
	    $fortune = $items[1]->getCount() / 3;
	    $fortune += $items[2]->getCount() / 2;
	    $fortune += $items[3]->getNamedTag()->getInt("NoteValue") / 2000000000;
	    if(ItemUtils::addItems($player->getInventory(), $piece, ...$items)){
	    	$item->setCount($item->getCount() - 1);
	    	$player->getInventory()->setItemInHand($item);
	    	$this->plugin->addTokens($player, \kenygamer\Core\Main::mt_rand(20, 50));
	    	LangManager::broadcast("koth-lootbag", $player->getName(), round($fortune / 3 * 100));
	    }else{
	    	LangManager::send("inventory-nospace", $player);
	    }
	    // - - - - -  K O T H  L O O T B A G  - - - - - 
	}
	
}