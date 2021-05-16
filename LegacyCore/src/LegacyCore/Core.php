<?php

namespace LegacyCore;

use LegacyCore\Commands\Blow;
use LegacyCore\Commands\Bounty;
use LegacyCore\Commands\Breaks;
use LegacyCore\Commands\Broadcast;
use LegacyCore\Commands\CeInfo;
use LegacyCore\Commands\Ceremove;
use LegacyCore\Commands\ChatEffect;
use LegacyCore\Commands\ClearChat;
use LegacyCore\Commands\Confuse;
use LegacyCore\Commands\Coordinates;
use LegacyCore\Commands\Effect;
use LegacyCore\Commands\Feed;
use LegacyCore\Commands\Fly;
use LegacyCore\Commands\Gamemode;
use LegacyCore\Commands\Getkey;
use LegacyCore\Commands\Getpos;
use LegacyCore\Commands\Givexp;
use LegacyCore\Commands\God;
use LegacyCore\Commands\Heal;
use LegacyCore\Commands\Kit;
use LegacyCore\Commands\Me;
use LegacyCore\Commands\More;
use LegacyCore\Commands\Near;
use LegacyCore\Commands\Nickname;
use LegacyCore\Commands\NPC;
use LegacyCore\Commands\PlayerList;
use LegacyCore\Commands\PlayerStats;
use LegacyCore\Commands\Prestige;
use LegacyCore\Commands\RankUp;
use LegacyCore\Commands\RealName;
use LegacyCore\Commands\Repair;
use LegacyCore\Commands\Say;
use LegacyCore\Commands\Seeskin;
use LegacyCore\Commands\Sell;
use LegacyCore\Commands\KitShop;
use LegacyCore\Commands\Snoop;
use LegacyCore\Commands\Sudo;
use LegacyCore\Commands\Suicide;
use LegacyCore\Commands\Tell;
use LegacyCore\Commands\Timer;
use LegacyCore\Commands\Vanish;
use LegacyCore\Commands\Version;
use LegacyCore\Commands\Withdraw;
use LegacyCore\Commands\Ceremoveall;
use LegacyCore\Commands\Stop;
use LegacyCore\Commands\Area\Area as AreaCommand;

use LegacyCore\Commands\Warp\Back;
use LegacyCore\Commands\Warp\PvPMine;
use LegacyCore\Commands\Warp\Spawn;
use LegacyCore\Commands\Warp\Top;
use LegacyCore\Commands\Warp\Vip;
use LegacyCore\Commands\Warp\Warp;
use LegacyCore\Commands\Warp\Warzone;
use LegacyCore\Commands\Warp\Wild;
use LegacyCore\Commands\Warp\World;

use LegacyCore\Commands\Home\Adminhome;
use LegacyCore\Commands\Home\Delhome;
use LegacyCore\Commands\Home\Home;
use LegacyCore\Commands\Home\Homes;
use LegacyCore\Commands\Home\Seehome;
use LegacyCore\Commands\Home\Sethome;

use LegacyCore\Commands\Teleport\TP;
use LegacyCore\Commands\Teleport\TPA;
use LegacyCore\Commands\Teleport\TPAccept;
use LegacyCore\Commands\Teleport\TPAHere;
use LegacyCore\Commands\Teleport\TPAll;
use LegacyCore\Commands\Teleport\TPDeny;
use LegacyCore\Commands\Teleport\TPHere;

use LegacyCore\Events\Area as AreaEvents;
use LegacyCore\Events\OtherEvents;
use LegacyCore\Events\PlayerEvents;
use LegacyCore\Events\NPCEvents;
use LegacyCore\Events\Protection;
use LegacyCore\Events\PrisonMiner;
use LegacyCore\Events\SocialChat;
use LegacyCore\Events\Treasure;
use LegacyCore\Events\WorldProtect;

use LegacyCore\Tasks\BroadcastTask;
use LegacyCore\Tasks\CombatTask;
use LegacyCore\Tasks\DropTask;
use LegacyCore\Tasks\EnvoysTask;
use LegacyCore\Tasks\NPCAttackTask;
use LegacyCore\Tasks\NPCSpawnTask;
use LegacyCore\Tasks\PlayerTask;
use LegacyCore\Tasks\ScoreHudTask;
use LegacyCore\Tasks\RemoveTimerTask;

use LegacyCore\Entities\Slapper;

use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class Core extends PluginBase{
	public $attack = 2.0;
	public $withdraw = 2.0;
	public $withdrawexp = 2.0;
	public $pmtimer = 10;
	public $wztimer = 10;
	public $wildtime = 15;
	public $pvpmine = [];
	public $suvwild = [];
	public $warzones = [];
	public $tpa = [];
	public $aliveBosses = [];
	
	/** @var Config */
	public $homes;
	/** @var array */
	public $loggerTime = [];
	/** @var Config */
	public $chatprefs; 
	/** @var Config */
	public $kitcooldown;
	/** @var array */
	public $snoopers = [];
	
	/** @var string */
	public static $snapshot = "";
	
	public function onLoad() : void{
		Entity::registerEntity(Slapper::class, true); //TODO
	}
	
	public function onEnable() : void{
		self::$snapshot = @file_get_contents($this->getServer()->getDataPath() . ".snapshot") ?? self::$snapshot;
		$this->chatprefs = new Config($this->getDataFolder() . "chat_prefs.json", Config::JSON);
		$this->kitcooldown = new Config($this->getDataFolder() . "kitcooldown.yml", Config::YAML);
		
		$this->saveResource("sell.yml", true);
		$this->saveResource("models.yml", true);
		if(!is_dir($this->getDataFolder() . "player")){
            @mkdir($this->getDataFolder() . "player", 0777);
        }
		
		$this->loadWorlds();
		$this->registerEnchants();
		$this->homes = new Config($this->getDataFolder() . "homes.yml", Config::YAML);
		
		//Commands
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) : void{
			$this->onDependencyResolution();
		}), 0);
	}
	
	private function onDependencyResolution() : void{
		$this->getServer()->getCommandMap()->register("area", ($areaCommand = new AreaCommand("area", $this)));
		$this->getServer()->getCommandMap()->register("adminhome", new Adminhome("adminhome", $this));
		$this->getServer()->getCommandMap()->register("back", new Back("back", $this));
		$this->getServer()->getCommandMap()->register("blow", new Blow("blow", $this));
		$this->getServer()->getCommandMap()->register("bounty", new Bounty("bounty", $this));
		$this->getServer()->getCommandMap()->register("breaks", new Breaks("breaks", $this));
		$this->getServer()->getCommandMap()->register("broadcast", new Broadcast("broadcast", $this));
		$this->getServer()->getCommandMap()->register("ceinfo", new CeInfo("ceinfo", $this));
		$this->getServer()->getCommandMap()->register("ceremove", new Ceremove("ceremove", $this));
		$this->getServer()->getCommandMap()->register("chateffect", new ChatEffect("chateffect", $this));
		$this->getServer()->getCommandMap()->register("clearchat", new ClearChat("clearchat", $this));
		$this->getServer()->getCommandMap()->register("confuse", new Confuse("confuse", $this));
		$this->getServer()->getCommandMap()->register("coordinates", new Coordinates("coordinates", $this));
		$this->getServer()->getCommandMap()->register("delhome", new Delhome("delhome", $this));
		$this->getServer()->getCommandMap()->register("effect", new Effect("effect", $this));
		$this->getServer()->getCommandMap()->register("feed", new Feed("feed", $this));
		$this->getServer()->getCommandMap()->register("fly", new Fly("fly", $this));
		$this->getServer()->getCommandMap()->register("gamemode", new Gamemode("gamemode", $this));
		$this->getServer()->getCommandMap()->register("getkey", new Getkey("getkey", $this));
		$this->getServer()->getCommandMap()->register("getpos", new Getpos("getpos", $this));
		$this->getServer()->getCommandMap()->register("givexp", new Givexp("givexp", $this));
		$this->getServer()->getCommandMap()->register("god", new God("god", $this));
		$this->getServer()->getCommandMap()->register("heal", new Heal("heal", $this));
		$this->getServer()->getCommandMap()->register("home", new Home("home", $this));
		$this->getServer()->getCommandMap()->register("homes", new Homes("homes", $this));
		$this->getServer()->getCommandMap()->register("kit", new Kit("kit", $this));
		$this->getServer()->getCommandMap()->register("me", new Me("me", $this));
		$this->getServer()->getCommandMap()->register("more", new More("more", $this));
		$this->getServer()->getCommandMap()->register("near", new Near("near", $this));
		$this->getServer()->getCommandMap()->register("nickname", new Nickname("nickname", $this));
		$this->getServer()->getCommandMap()->register("npc", new NPC("npc", $this));
		$this->getServer()->getCommandMap()->register("playerlist", new PlayerList("playerlist", $this));
		$this->getServer()->getCommandMap()->register("playerstats", new PlayerStats("playerstats", $this));
		$this->getServer()->getCommandMap()->register("prestige", new Prestige("prestige", $this));
		$this->getServer()->getCommandMap()->register("pvpmine", new PvPMine("pvpmine", $this));
		$this->getServer()->getCommandMap()->register("rankup", new RankUp("rankup", $this));
		$this->getServer()->getCommandMap()->register("realname", new RealName("realname", $this));
		$this->getServer()->getCommandMap()->register("repair", new Repair("repair", $this));
		$this->getServer()->getCommandMap()->register("say", new Say("say", $this));
		$this->getServer()->getCommandMap()->register("seeskin", new Seeskin("seeskin", $this));
		$this->getServer()->getCommandMap()->register("sell", new Sell("sell", $this));
		$this->getServer()->getCommandMap()->register("seehome", new Seehome("seehome", $this));
		$this->getServer()->getCommandMap()->register("sethome", new Sethome("sethome", $this));
		$this->getServer()->getCommandMap()->register("kitshop", new KitShop("kitshop", $this));
		$this->getServer()->getCommandMap()->register("snoop", new Snoop("snoop", $this));
		$this->getServer()->getCommandMap()->register("spawn", new Spawn("spawn", $this));
		$this->getServer()->getCommandMap()->register("sudo", new Sudo("sudo", $this));
		$this->getServer()->getCommandMap()->register("suicide", new Suicide("suicide", $this));
		$this->getServer()->getCommandMap()->register("tell", new Tell("tell", $this));
		$this->getServer()->getCommandMap()->register("timer", new Timer("timer", $this));
		$this->getServer()->getCommandMap()->register("top", new Top("top", $this));
		$this->getServer()->getCommandMap()->register("tp", new TP("tp", $this));
		$this->getServer()->getCommandMap()->register("tpa", new TPA("tpa", $this));
		$this->getServer()->getCommandMap()->register("tpaccept", new TPAccept("tpaccept", $this));
		$this->getServer()->getCommandMap()->register("tpall", new TPAll("tpall", $this));
		$this->getServer()->getCommandMap()->register("tpahere", new TPAHere("tpahere", $this));
		$this->getServer()->getCommandMap()->register("tpdeny", new TPDeny("tpdeny", $this));
		$this->getServer()->getCommandMap()->register("tphere", new TPHere("tphere", $this));
		$this->getServer()->getCommandMap()->register("vanish", new Vanish("vanish", $this));
		$this->getServer()->getCommandMap()->register("version", new Version("version", $this));
		$this->getServer()->getCommandMap()->register("vip", new Vip("vip", $this));
		$this->getServer()->getCommandMap()->register("warp", new Warp("warp", $this));
		$this->getServer()->getCommandMap()->register("warzone", new Warzone("warzone", $this));
		$this->getServer()->getCommandMap()->register("wild", new Wild("wild", $this));
	    $this->getServer()->getCommandMap()->register("withdraw", new Withdraw("withdraw", $this));
		$this->getServer()->getCommandMap()->register("world", new World("world", $this));
		$this->getServer()->getCommandMap()->register("ceremoveall", new Ceremoveall("ceremoveall", $this));
		$this->getServer()->getCommandMap()->register("stop", new Stop("stop", $this));

		//Events
		$this->getServer()->getPluginManager()->registerEvents(new AreaEvents($areaCommand), $this);
		$this->getServer()->getPluginManager()->registerEvents(new OtherEvents($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEvents($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new NPCEvents($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new Protection($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PrisonMiner($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new SocialChat($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new Treasure($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new WorldProtect($this), $this);
		
		//Tasks
		$this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), 20 * 60 * \kenygamer\Core\Main::mt_rand(10, 15));
		$this->getScheduler()->scheduleRepeatingTask(new DropTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new EnvoysTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new NPCAttackTask($this), 3);
		$this->getScheduler()->scheduleRepeatingTask(new NPCSpawnTask($this), 20 * 60 * 30);
		$this->getScheduler()->scheduleRepeatingTask(new PlayerTask($this), 10);
		$this->getScheduler()->scheduleRepeatingTask(new ScoreHudTask($this), 60);
		$this->getScheduler()->scheduleRepeatingTask(new RemoveTimerTask($this), 20);
    }

	public function onDisable() : void{
		$this->chatprefs->save();
		$this->kitcooldown->save();
		$this->homes->save();
    }
	
	private function loadWorlds() : void{
		foreach(["hub", "vipworld", "prison", "warzone", "wild", "minigames", "hotel", "maze", "duels"
		] as $world){
			if($this->getServer()->loadLevel($world)){
				$level = $this->getServer()->getLevelByName($world);
				//MW Fixname
				$provider = $level->getProvider();
				if($provider->getName() !== $level->getFolderName()){
					$provider->getLevelData()->setString("LevelName", $level->getFolderName());
				}
			}else{
				$this->getServer()->generateLevel($world);
			}
		}
	}
	
	private function registerEnchants() : void{
		Enchantment::registerEnchantment(new Enchantment(Enchantment::DEPTH_STRIDER, "Depth Strider", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_FEET, Enchantment::SLOT_NONE, 3));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::AQUA_AFFINITY, "Aqua Affinity", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_HEAD, Enchantment::SLOT_NONE, 1));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::SMITE, "Smite", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_AXE, 5));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::BANE_OF_ARTHROPODS, "Bane of arthropods", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_AXE, 5));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::LOOTING, "Looting", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 3));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FORTUNE, "Fortune", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_DIG, Enchantment::SLOT_NONE, 3));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FROST_WALKER, "Frost Walker", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_FEET, Enchantment::SLOT_NONE, 1));
	}

}