<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;

use kenygamer\Core\Main;
use kenygamer\Spawners\MonsterSpawner;
use pocketmine\entity\Entity;

class FactionListener implements Listener {
	public $plugin;
	
	/** @var array */
	private $kills = [];
	    
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
	/**
	 * @param BlockPlaceEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onSpawnerPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $event->getItem();
		
		if($block instanceof MonsterSpawner && ($entityId = $item->getNamedTagEntry("EntityId")) && $this->plugin->pointIsInPlot($block->getX(), $block->getZ())){
			$spawners = Main::getInstance()->getPlugin("Spawners");
			$exp = $spawners->spawners[$spawners->getSpawnerName($entityId->getValue())]["str"] ?? 0;
			if($exp > 0){
				$faction = $this->plugin->factionFromPoint($block->getX(), $block->getZ());
				$factionSpawners = $this->plugin->factionSpawners->get($faction, []);
				$factionSpawners[$block->getX() . ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName()] = $exp;
				$this->plugin->factionSpawners->set($faction, $factionSpawners);
			}
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onSpawnerBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		//If spawners weren't indestructible we would have to check for explosions and other block updates as well
		if($block instanceof MonsterSpawner){
			$loc = $block->getX() . ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName();
			$factionSpawners = $this->plugin->factionSpawners->getAll();
			foreach($factionSpawners as $faction => $spawner){
				foreach($spawner as $lloc => $entityId){
					if($lloc === $loc){
						unset($factionSpawners[$faction][$lloc]);
						break 2;
					}
				}
			}
			$this->plugin->factionSpawners->setAll($factionSpawners);
		}
	}
	
	public function factionChat(PlayerChatEvent $PCE) {
		
		$player = $PCE->getPlayer()->getName();
		//MOTD Check

		if($this->plugin->motdWaiting($player)) {
			if(time() - $this->plugin->getMOTDTime($player) > 30) {
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("Timed out. Please use /f desc again."));
				$this->plugin->db->query("DELETE FROM motdrcv WHERE player='$player';");
				$PCE->setCancelled(true);
				return true;
			} else {
				$motd = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($player);
				$this->plugin->setMOTD($faction, $player, $motd);
				$PCE->setCancelled(true);
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("Successfully updated the faction description. Type /f info.", true));
			}
			return true;
		}
		if(isset($this->plugin->factionChatActive[$player])){
			if($this->plugin->factionChatActive[$player]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($player);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::GOLD."[$faction]".TextFormat::YELLOW." $player > ".TextFormat::AQUA. $msg);
						}
					}
				}
			}
		}
		if(isset($this->plugin->allyChatActive[$player])){
			if($this->plugin->allyChatActive[$player]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($player);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->areAllies($this->plugin->getPlayerFaction($fP->getName()), $faction)){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_BLUE."[$faction]".TextFormat::YELLOW." $player > ".TextFormat::AQUA. $msg);
							$PCE->getPlayer()->sendMessage(TextFormat::DARK_BLUE."[$faction]".TextFormat::YELLOW." $player > ".TextFormat::AQUA. $msg);
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param EntityDamageEvent $factionDamage
	 * @priority HIGH
	 */
	public function factionPVP(EntityDamageEvent $factionDamage) {
		if($factionDamage instanceof EntityDamageByEntityEvent) {
			if(!($factionDamage->getEntity() instanceof Player) or !($factionDamage->getDamager() instanceof Player)) {
				return true;
			}
			if(($this->plugin->isInFaction($factionDamage->getEntity()->getPlayer()->getName()) == false) or ($this->plugin->isInFaction($factionDamage->getDamager()->getPlayer()->getName()) == false)) {
				return true;
			}
			if(($factionDamage->getEntity() instanceof Player) and ($factionDamage->getDamager() instanceof Player)) {
				$player1 = $factionDamage->getEntity()->getPlayer()->getName();
				$player2 = $factionDamage->getDamager()->getPlayer()->getName();
                		$f1 = $this->plugin->getPlayerFaction($player1);
				$f2 = $this->plugin->getPlayerFaction($player2);
				if((!$this->plugin->prefs->get("AllowFactionPvp") && $this->plugin->sameFaction($player1, $player2) == true) or (!$this->plugin->prefs->get("AllowAlliedPvp") && $this->plugin->areAllies($f1,$f2))) {
					$factionDamage->setCancelled();
				}
			}
		}
	}
	public function factionBlockBreakProtect(BlockBreakEvent $event) {
        $x = $event->getBlock()->getX();
      	$z = $event->getBlock()->getZ();
		if ($this->plugin->pointIsInPlot($x, $z)) {
			if ($this->plugin->factionFromPoint($x, $z) === $this->plugin->getFaction($event->getPlayer()->getName())) {
				return;
			} else {
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage($this->plugin->formatMessage("You cannot break blocks here. This is already a property of a faction. Type /f plotinfo for details."));
				return;
			}
		}
	}
	
	public function factionBlockPlaceProtect(BlockPlaceEvent $event) {
      	$x = $event->getBlock()->getX();
     	$z = $event->getBlock()->getZ();
		if ($this->plugin->pointIsInPlot($x, $z)) {
			if ($this->plugin->factionFromPoint($x, $z) == $this->plugin->getFaction($event->getPlayer()->getName())) {   
				return;
			} else {
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage($this->plugin->formatMessage("You cannot place blocks here. This is already a property of a faction. Type /f plotinfo for details."));
				return;
			}
		}
	}
	
	public function onKill(PlayerDeathEvent $event){
        $ent = $event->getEntity(); //The killed player.
        $cause = $event->getEntity()->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent){
            $killer = $cause->getDamager(); //The killer.
            if($killer instanceof Player){
                $p = $killer->getPlayer()->getName();
                if($this->plugin->isInFaction($p)){
                    $f = $this->plugin->getPlayerFaction($p); //Killer fac
                    
                    $n = $ent->getPlayer()->getLowerCaseName();
					//Prevent STR farming
                    if(isset($this->kills[$f][$n]) && !(time() >= $this->kills[$f][$n])){
                    	return;
                    }
                    $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                    if($ent instanceof Player){
                    	
                    	$this->kills[$f][$n] = time() + 300;
                    	
                        if($this->plugin->isInFaction($ent->getPlayer()->getName())){
                           	$this->plugin->addFactionPower($f,$e);
                        } else {
                           $this->plugin->addFactionPower($f,$e/2);
                        }
                    }
                }
            }
        }
        if($ent instanceof Player){
            $e = $ent->getPlayer()->getName();
            if($this->plugin->isInFaction($e)){
                $f = $this->plugin->getPlayerFaction($e);
                $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                if($ent->getLastDamageCause() instanceof EntityDamageByEntityEvent && $ent->getLastDamageCause()->getDamager() instanceof Player){
                    if($this->plugin->isInFaction($ent->getLastDamageCause()->getDamager()->getPlayer()->getName())){      
                        $this->plugin->subtractFactionPower($f,$e*2);
                    } else {
                        $this->plugin->subtractFactionPower($f,$e);
                    }
                }
            }
        }
    }
    
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->updateTag($event->getPlayer()->getName());
	}
}
