<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use LegacyCore\Commands\God;
use LegacyCore\Commands\Vanish;

class PlayerEvents implements Listener{
	public const NEGATIVE_EFFECTS = [
        Effect::FATAL_POISON, Effect::NAUSEA, Effect::LEVITATION, Effect::SLOWNESS, Effect::WEAKNESS,
        Effect::BLINDNESS, Effect::POISON, Effect::WITHER, Effect::MINING_FATIGUE
    ];
    
	/** @var Core */
	private $plugin;
	
	/** @var array */
    private static $PlayerData = [];
    /** @var string[] */
    public const OS_LIST = [
        "Unknown", "Android", "iOS", "macOS", "FireOS", "GearVR",
        "HoloLens", "Windows 10", "Win32", "Dedicated", "tvOS", "Orbis",
        "NX", "Xbox", "Windows Phone"
    ];
    /** @var string[] */
    public const CONTROLS = [
        "Unknown", "Mouse", "Touch", "Controller" 
    ];
	public const UIS = [
		"Classic UI", "Pocket UI"
	];
	public const GUIS = [
		-3 => "Unknown", -2 => "Minimum", -1 => "Medium", 0 => "Maximum"
	];
        
	/** @var array */
	private $minecooldown = [
	    "normal" => 0,
	    "pvp" => 0,
	    "premium" => 0
	];

    /**
     * @param Core $plugin
     */
    public function __construct(Core $plugin){
        $this->plugin = $plugin;
    }
    
    /**
     * @return array DeviceModel, DeviceOS, UIProfile, GuiScale, CurrentInputMode
     */
    public static function getPlayerData($player) : array{
    	if($player instanceof Player){
    		$player = $player->getName();
    	}
    	$data = self::$PlayerData[$player] ?? [];
    	foreach(["DeviceModel", "DeviceOS", "UIProfile", "GuiScale", "CurrentInputMode"] as $var){
    		if(!isset($data[$var])){
    			$data[$var] = 0;
    		}
    	}
    	return $data; 
    }
	
	/**
     * @param PlayerJoinEvent $event
     */
	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		if(!$this->plugin->homes->exists($player->getName())){
			$this->plugin->homes->set($player->getName(), []);
		}
		$time = date("m-d-Y H:i:s", time());
		$data = new Config($this->plugin->getDataFolder() . "player/" . mb_strtolower($player->getName()) . ".yml", Config::YAML);
		$cdata = self::getPlayerData($player);
		
        $data->setAll([
            "Name" => $player->getName(),
            "Model" => $this->DeviceModel($cdata["DeviceModel"]),
            "OS" => $this->DeviceModel($cdata["DeviceModel"]),
            "Skin" => base64_encode($player->getSkin()->getSkinData()),
            "UI" => self::UIS[$cdata["UIProfile"]],
            "GUI" => self::GUIS[$cdata["GuiScale"]],
            "Controls" => self::CONTROLS[$cdata["CurrentInputMode"]],
            "XUID" => $player->getXuid()
        ]);
		$data->save();
		
		$manager = Main::getInstance()->permissionManager;
		if($manager->getPlayerPrefix($player) === ""){
			$manager->setPlayerPrefix($player, "A");
		}
	}
	
	/**
     * @param DataPacketReceiveEvent $event
     * @priority HIGHEST
     */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        //Player has not created yet so username of Player = "". Can use $packet->username
        if($packet instanceof LoginPacket){
            self::$PlayerData[$packet->username] = $packet->clientData;
        }
    }
    
    /**
	 * @param string $model
     */
	public function DeviceModel(string $model) {
        $models = yaml_parse_file($this->plugin->getDataFolder() . "models.yml");
        if (isset($models[$model])) {
            return $models[$model];
        } else {
            return $model;
        }
    }
	
	/**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event): void{
		$player = $event->getPlayer();
		$message = $event->getMessage();
        
        if ((strpos($message, "&") !== false || strpos($message, "§") !== false) && $player instanceof Player && !$player->hasPermission("core.colorchat.bypass")){
        	LangManager::send("core-colorchat", $player);
            $event->setCancelled();
        }else{
        	$event->setMessage(TextFormat::colorize($message)); //Replace & with §
        }
    }
	
	/**
     * @param PlayerInteractEvent $event
     */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if ($player->getLevel()->getFolderName() == "prison"){
			if($block->x === 151 && $block->y === 205 && $block->z === 519){
				if(time() < $this->minecooldown["normal"]){
					LangManager::send("core-minereset-cooldown", $player, Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft($this->minecooldown["normal"]), TextFormat::RED, TextFormat::RED));
				}else{
					$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), "mine reset NormalMine");
					$this->minecooldown["normal"] = time() + 600;
				}
	    	}
            if($block->x === -19 && $block->y === 154 && $block->z === 493){
            	if(time() < $this->minecooldown["premium"]){
					LangManager::send("core-minereset-cooldown", $player, Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft($this->minecooldown["premium"]), TextFormat::RED, TextFormat::RED));
				}else{
					$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), "mine reset PremiumMine");
					$this->minecooldown["premium"] = time() + 600;
				}
            }
            if($block->x === 64 && $block->y === 182 && $block->z === 810){
            	if(time() < $this->minecooldown["pvp"]){
					LangManager::send("core-minereset-cooldown", $player, Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft($this->minecooldown["pvp"]), TextFormat::RED, TextFormat::RED));
				}else{
					$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), "mine reset PvPMine");
					$this->minecooldown["pvp"] = time() + 600;
				}
            }
		}
	}

	/**
     * @param PlayerDeathEvent $event
     */
	public function onDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        $this->plugin->back[$player->getName()] = $player->getPosition();
        if($cause instanceof EntityDamageByEntityEvent){
			$killer = $cause->getDamager();
			if($killer instanceof Player){
	    		$this->claimBounty($player, $killer);
			}
		}
	}
	
	/**
     * @param PlayerDeathEvent $event
     */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();
        if($entity instanceof Player){
			Main::getInstance()->resetEntry($entity->getName(), Main::ENTRY_KILL_STREAK);
		}
        if ($cause instanceof EntityDamageByEntityEvent){
			$killer = $cause->getDamager();
			if($killer instanceof Player){
				$streak = Main::getInstance()->getEntry($killer->getName(), Main::ENTRY_KILL_STREAK);
				Main::getInstance()->registerEntry($killer->getName(), Main::ENTRY_KILL_STREAK);
				
				if($streak % 5 === 0 && $streak > 0 && $streak < 250){
					$money = (500 + ($streak / 0.1282049)) * $streak;
					Main::getInstance()->addMoney($killer, $money);
					$killer->sendMessage(TextFormat::colorize("&l&a" . $money) . "+");
					LangManager::broadcast("core-killstreak", $killer->getName(), $streak);
				}
			}
		}
	}
	
	/**
     * @param PlayerQuitEvent $event
     */
	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->plugin->back[$player->getName()])){
	    	unset($this->plugin->back[$player->getName()]);
		}
		if (isset($this->plugin->hides[$player->getLowerCaseName()])){
	    	unset($this->plugin->hides[$player->getLowerCaseName()]);
		}
	}
	
	/**
     * @param EntityEffectAddEvent $event
     */
    public function onEffect(EntityEffectAddEvent $event) : void{
		$entity = $event->getEntity();
		$effect = $event->getEffect();
		if ($entity instanceof Player){
			if(in_array($entity->getName(), God::getInstance()->god)){
				if(in_array($effect->getId(), self::NEGATIVE_EFFECTS)){
				    	$event->setCancelled();
				}
			}
		}
	}

	/**
     * @param EntityDamageEvent $event
     * @priority HIGHEST
     * @ignoreCancelled true
     */
	public function onDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		$cause = $event->getCause();
		if($entity instanceof Player){
			if(in_array($entity->getName(), God::getInstance()->god)){
				$event->setCancelled();
				return;
			}
			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
				if($damager instanceof Player){
					if(in_array($damager->getName(), God::getInstance()->god)){
						$damager->sendPopup(LangManager::translate("core-attack-god", $damager));
                        $event->setCancelled();
                    }elseif(isset(Vanish::getInstance()->vanishes[$damager->getName()]) && Vanish::getInstance()->vanishes[$damager->getName()][0] && !$damager->hasPermission("core.vanish.pvp")){
						$damager->sendPopup(LangManager::translate("core-attack-vanish", $damager));
						$event->setCancelled();
					}
				}
			}
		}
	}

	/**
     * @param Player $player
     * @param Player $killer
     */
    public function claimBounty(Player $player, Player $killer){
		$money = Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY);
        if($money !== 0){
            Main::getInstance()->addMoney($killer->getName(), (float) $money);
            Main::getInstance()->resetEntry($player, Main::ENTRY_BOUNTY);
            LangManager::broadcast("core-bounty-claimed", $money, $player->getName(), $killer->getName());
        }
    }
    
}