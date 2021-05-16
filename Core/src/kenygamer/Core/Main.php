<?php

declare(strict_types=1);

namespace kenygamer\Core;

//PM imports
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\RakLibInterface;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\block\BlockFactory;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\SharpnessEnchantment;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\utils\Process;
use pocketmine\utils\Random;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;

//Other plugin imports
use LegacyCore\Commands\Sell;
use LegacyCore\Core;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use revivalpmmp\pureentities\tile\MobSpawner;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use xenialdan\libnbs\NBSFile;
use xenialdan\libnbs\Song;
use xenialdan\BossBar\BossBar;
use onebone\economyapi\EconomyAPI;
use specter\api\DummyPlayer;

//This plugin imports
use kenygamer\Core\network\InventoryContentPacket;
use kenygamer\Core\land\LandManager;
use kenygamer\Core\block\MonsterSpawner;
use kenygamer\Core\task\PlaySongTask;
use kenygamer\Core\task\RandomNumberTask;
use kenygamer\Core\task\VotePartyTask;
use kenygamer\Core\task\DonationsTask;
use kenygamer\Core\task\OutspotTask;
use kenygamer\Core\task\PardonTask;
use kenygamer\Core\task\HttpGetTask;
use kenygamer\Core\task\ReferralTask;
use kenygamer\Core\task\AreaTask;
use kenygamer\Core\task\GiveawayTask;
use kenygamer\Core\task\LoveTask;
use kenygamer\Core\task\TradeTask;
use kenygamer\Core\task\OutpostTask;
use kenygamer\Core\task\AlertTask;
use kenygamer\Core\task\MaintenanceTask;
use kenygamer\Core\task\CoinFlipTask;
use kenygamer\Core\task\ChangelogSendFormTask;
use kenygamer\Core\task\WingsTask;
use kenygamer\Core\task\OutlineAreaTask;
use kenygamer\Core\task\ReadFileTask;
use kenygamer\Core\task\SpawnerTask;
use kenygamer\Core\koth\KothTask;
use kenygamer\Core\koth\KothListener;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\listener\ClearInvListener;
use kenygamer\Core\listener\AntiLaggListener;
use kenygamer\Core\listener\MaintenanceListener;
use kenygamer\Core\survey\Survey;
use kenygamer\Core\survey\SurveyManager;
use kenygamer\Core\map\MapFactory;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\util\SQLiteConfig;
use kenygamer\Core\quest\QuestManager;
use kenygamer\Core\duel\DuelArena;
use kenygamer\Core\duel\DuelListener;
use kenygamer\Core\duel\DuelTask;
use kenygamer\Core\entity\HeadEntity; 
use kenygamer\Core\entity\ArmorStandEntity;
use kenygamer\Core\entity\Bandit;
use kenygamer\Core\entity\Goblin;
use kenygamer\Core\entity\Knight;
use kenygamer\Core\entity\Vampire;
use kenygamer\Core\entity\FishingHook;
use kenygamer\Core\util\PersonaSkinAdapter;
use kenygamer\Core\item\FireworksItem;
use kenygamer\Core\auction\AuctionTask;
use kenygamer\Core\auction\Auction;
use kenygamer\Core\bedwars\BedWarsManager;
use kenygamer\Core\account\AccountGroup;
use kenygamer\Core\command\XpBoostCommand;
use kenygamer\Core\command\QuestCommand;
use kenygamer\Core\command\DuelCommand;
use kenygamer\Core\command\BragHouseCommand;
use kenygamer\Core\command\KothCommand;
use kenygamer\Core\command\ChunkCommand;
use kenygamer\Core\command\WhitelistCommand;
use kenygamer\Core\command\CoinFlipCommand;
use kenygamer\Core\command\AutoSellCommand;
use kenygamer\Core\command\AFKCommand;
use kenygamer\Core\command\AliasCommand;
use kenygamer\Core\command\BedWarsCommand;
use kenygamer\Core\command\BreakUpCommand;
use kenygamer\Core\command\CosmeticCommand;
use kenygamer\Core\command\CeProgressCommand;
use kenygamer\Core\command\CeSplitCommand;
use kenygamer\Core\command\CeUpgradeCommand;
use kenygamer\Core\command\ClearChatCommand;
use kenygamer\Core\command\GiveawayCommand;
use kenygamer\Core\command\EasterEggCommand;
use kenygamer\Core\command\EUpgradeCommand;
use kenygamer\Core\command\ExecCommand;
use kenygamer\Core\command\GiveTokensCommand;
use kenygamer\Core\command\HelpMeCommand;
use kenygamer\Core\command\IgnoreCommand;
use kenygamer\Core\command\InviteCommand;
use kenygamer\Core\command\LastEnvoyCommand;
use kenygamer\Core\command\LoveCommand;
use kenygamer\Core\command\MaintenanceCommand;
use kenygamer\Core\command\PersonalMineCommand;
use kenygamer\Core\command\RaidCommand;
use kenygamer\Core\command\ReadFileCommand;
use kenygamer\Core\command\RenameCommand;
use kenygamer\Core\command\ReplyCommand;
use kenygamer\Core\command\ClipboardCommand;
use kenygamer\Core\command\SeeLoveCommand;
use kenygamer\Core\command\SetLanguageCommand;
use kenygamer\Core\command\AddNbtCommand;
use kenygamer\Core\command\SizeCommand;
use kenygamer\Core\command\SurveyCommand;
use kenygamer\Core\command\TagCommand;
use kenygamer\Core\command\ToggleLoveCommand;
use kenygamer\Core\command\TpXyzCommand;
use kenygamer\Core\command\TransferCommand;
use kenygamer\Core\command\TransferMoneyCommand;
use kenygamer\Core\command\TransferXpCommand;
use kenygamer\Core\command\TrashCommand;
use kenygamer\Core\command\WingsCommand;
use kenygamer\Core\command\InventoryCommand;
use kenygamer\Core\command\GiveCommand;
use kenygamer\Core\command\AuctionHouseCommand;
use kenygamer\Core\command\InvSeeCommand;
use kenygamer\Core\command\TradeCommand;
use kenygamer\Core\command\AccountCommand;
use kenygamer\Core\command\SpawnerCommand;
use kenygamer\Core\command\HeadhuntingCommand;
use kenygamer\Core\command\SetGroupCommand;
use kenygamer\Core\command\UnsetGroupCommand;
use kenygamer\Core\command\SetUPermCommand;
use kenygamer\Core\command\UnsetUPermCommand;
use kenygamer\Core\command\TimeOnlineCommand;
use kenygamer\Core\command\SetSuffixCommand;
use kenygamer\Core\command\ListUPermsCommand;
use kenygamer\Core\command\VaultCommand;
use kenygamer\Core\command\EShopCommand;
use kenygamer\Core\command\LinkCommand;
use kenygamer\Core\command\UnlinkCommand;
use kenygamer\Core\command\WarnCommand;
use kenygamer\Core\command\RulesCommand;
use kenygamer\Core\command\ReportCommand;
use kenygamer\Core\command\CaseCommand;
use kenygamer\Core\command\CasesCommand;
use kenygamer\Core\command\GmspcCommand;
use kenygamer\Core\command\StaffCommand;
use kenygamer\Core\command\LandCommand;
use kenygamer\Core\command\PgCommand;
use kenygamer\Core\command\ItemCaseCommand;
use kenygamer\Core\command\PayCommand;
use kenygamer\Core\command\GiveMoneyCommand;
use kenygamer\Core\command\TakeMoneyCommand;
use kenygamer\Core\command\TopMoneyCommand;
use kenygamer\Core\command\PlaceImageCommand;
use kenygamer\Core\command\VoteCommand;
use kenygamer\Core\command\VotePointsCommand;
use kenygamer\Core\command\Status2Command;
use kenygamer\Core\command\ResyncCommand;
use kenygamer\Core\command\MailCommand;
use kenygamer\Core\command\TimeZoneCommand;
use kenygamer\Core\command\SettingsCommand;
use kenygamer\Core\command\ScoreboardCommand;
use kenygamer\Core\command\BanListCommand;
use kenygamer\Core\command\VehicleCommand;
use kenygamer\Core\command\CEShopCommand;
use kenygamer\Core\vehicle\VehicleFactory;
use kenygamer\Core\vehicle\Vehicle;

/**
 * @class Main
 * @package kenygamer\Core
 */
final class Main extends PluginBase{
	/*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
                              Constants                         
     
	*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*/
	public const SECRET_KEY = "[REDACTED]";
	
	public const SETTING_SCOREBOARD = 0;
	
	public const SETTING_SCOREBOARD_NONE = 0;
	public const SETTING_SCOREBOARD_OLD = 1;
	public const SETTING_SCOREBOARD_REGULAR = 2;
	public const SETTING_SCOREBOARD_FACTION = 3;
	
	public const SETTING_HUD = 1;
	public const SETTING_TIME = 2;
	public const SETTING_COMPASS = 3;
	public const SETTING_CHUNKBORDERS = 4;

	/**
	 * Values ​​are intentionally tackled from index 1 to force PHP to
     * use stringy indexes.
	 */
	public const ENTRY_KILLS = 1; //int
	public const ENTRY_DEATHS = 2; //int
	public const ENTRY_BLOCKS_PLACED = 3; //int
	public const ENTRY_BLOCKS_BROKEN = 4; //int
	public const ENTRY_KILL_STREAK = 5; //int
	public const ENTRY_BOUNTY = 6; //float
	public const ENTRY_PRESTIGE = 7; //int
	public const ENTRY_LANG = 8; //string
	public const ENTRY_NICKNAME = 9; //string
	public const ENTRY_VOTEPOINTS = 10; //int
	public const ENTRY_COORDINATES = 11; //bool
	public const ENTRY_HEADHUNTING = 12; //int
	public const ENTRY_TOKENS = 13; //int
	public const ENTRY_DUEL_SCORE = 14; //array
	public const ENTRY_EXPERIENCE = 15; //float
	public const ENTRY_EXPERIENCE_LEVEL = 16; //int
	//{@see self::myMoney()}, {@see self::reduceMoney()}, {@see self::addMoney()}
	public const ENTRY_MONEY = 17; //string
	public const ENTRY_TIMEZONE = 18;
	public const ENTRY_FISHING_EXPERIENCE = 19;
	
	public const DUEL_TYPE_NORMAL = 0;
	public const DUEL_TYPE_VANILLA = 1;
	public const DUEL_TYPE_CUSTOM = 2;
	public const DUEL_TYPE_SPLEEF = 3;
	public const DUEL_TYPE_TNTRUN = 4;
	public const DUEL_TYPE_FRIENDLY = 5;
	
	public const PG_NOT_LOCKED = -1;
	public const PG_NORMAL_LOCK = 0;
	public const PG_PASSCODE_LOCK = 1;
	public const PG_PUBLIC_LOCK = 2;
	
	public const STAFF_RANK = "Builder";
	public const VIP_RANK = "Vip";
	
	/** @var string[] */
	public const PVP_WORLDS = [
	   "wild", "vipworld", "duels", "warzone"
	];
	
	/** @var string[] */
	public const TP_WORLDS = [
	   "wild", "vipworld", "warzone", "duels"
	];
	
	/** @var string[] */
	public const SETPOINT_WORLDS = [
	   "wild", "vipworld"
	];
	
	public const EFFECT_SAFE_MAX_DURATION = 0x7fffffff - (600 * 20);
	public const TOPMONEY_PER_PAGE_LIMIT = 5;
	private const CASE_FILE = "itemcasepe.txt";
	
	/*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*
                             Properties                         
     *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*/
     
    /** @var array */
	public $duelQueue = [
	    self::DUEL_TYPE_NORMAL => [],
	    self::DUEL_TYPE_FRIENDLY => [],
	    self::DUEL_TYPE_VANILLA => [],
	    self::DUEL_TYPE_CUSTOM => [],
	    self::DUEL_TYPE_SPLEEF => [],
	    self::DUEL_TYPE_TNTRUN => []
	];
	/** @var array */
	public $duelInfo = [
	    self::DUEL_TYPE_NORMAL => ["Use your OWN inventory to pvp in a 1v1.", "Soulbound"],
	    self::DUEL_TYPE_FRIENDLY => ["Use your OWN inventory to pvp in a 1v1. Keep inventory.", "Soulbound, Disarm, Disarming, Thief"],
	    self::DUEL_TYPE_VANILLA => ["Basic vanilla pvp kit to keep it simple."],
	    self::DUEL_TYPE_CUSTOM => ["Use a custom enchanted kit to fight your opponent."],
	    self::DUEL_TYPE_SPLEEF => ["Try to knock your opponent down to win."],
	    self::DUEL_TYPE_TNTRUN => ["Run on a surface that falls when you step on. If you fall, you lose."]
	];
	/** @var DuelArena[] */
	public $duelArenas = [];
	/** @var array */
	public $duelRequests = [];
	/** @var array */
	public $duelKits = [];
	
    /** @var array */
    public $readingFile = [];
	/** @var string[] */
	public $fileReadMode = [];
	/** @var bool */
	public $maintenanceMode = false;
    /** @var bool[] */
	public $trashMode = [];
	/** @var array Item[] */
	public $trashRollback = [];
	/**
	 * 0: giveaway active
	 * 1: winner list
	 * 2: awarded list
	 * 3: ???
	 * 4: giveaway items
	 * 5: player count
	 *
	 * @var array
	 */
	public static $giveawayStatus = [false, false, [], [], 0, [], 0];
	/** @var array */
	public static $last_move = [];
	/** @var array */
	public $eval = [];
	/** @var float[] */
	private $serverLoad = [0, 0, 0, 0, 0];
	/** @var array */
	public static $raiding = [];
	/** @var array */
	public static $bragHouse = [];
	/** @var string[] */
	public static $gambling = [];
	/** @var array */
	public $coinFlip = [];
	/** @var array string[] */
	public $loveRequests = [];
	/** @var Config */
	public $tags = null;
	/** @var string */
	/** @var string[] */
	public $tagList = null;
	/** @var string[] */
	public $ranks = [];
	/** @var Config */
	public $stats = null;
	/** @var Config */
	public $playerTrack = null;
	/** @var Config */
	public $playerEmails = null;
	/** @var Config */
	public $ignore = null;
	/** @var Config  */
	public $settings = null;
	/** @var Config */
	public $maze = null;
	/** @var Config */
	public $xpboost = null;
	/** @var Config */
	public $changelog;
	/** @var Config */
	public $skyjump = null;
	/** @var Config */
	public $autosell = null;
	/** @var Config */
	public $koth = null;
	/** @var Config */
	public $voteparty = null;
	/** @var Config */
	public $votes = null;
	/** @var Config */
	public $love = null;
	/** @var Config */
	public $tokens = null;
	/** @var Config */
	public $pg = null;
	/** @var Config */
	public $mail = null;
	/** @var Config */
	private $maintenanceCnf = null;
	/** @var Vector3[] */
    public $skyjumpSetpoints = [];
	
	/** @var array */
	public $models, $designs = [];
	/** @var Config */
	public $easterEggs = null;
	
	/** @var array */
	private $textureMap = [];
	/** @var SkinAdapterSingleton */
	private $originalSkinAdaptor = null;
	/** @var Auction[] */
	public $auctions = [];
	/** @var Auction[] */
	public $expiredAuctions = [];
	/** @var Config */
	public $auctionStats = null;
	/** @var BossBar[] */
	private $bossbars = [];
	/** @var AccountGroup[] */
	public $accountGroups = [];
	/** @var array */
	public $spawners = [];
	/** @var string [] */
	public $rainWorlds = [];
	/** @var int[] */
	public $worldDimensions = [];
	/** @var PermissionManager */
	public $permissionManager = null;
	
	/* @var SurveyManager|null */
	public $surveyManager = null;
	
	/** @var QuestManager */
	public $questManager = null;
	
	/** @var Config */
	public $bans = [];
	/** @var Config */
	public $warns = [];
	/** @var Config */
	public $sanctions = [];
	/** @var array */
	public $freezes = [];
	/** @var array */
	public $mutes = [];
	/** @var Config */
	public $cases = [];
	/** @var int[] */
	public $httpGetTasks = [];
	/** @var mixed[] */
	private $httpGetResults = [];
	/** @var int */
	public $linksTask = -1;
	/** @var int[] */
	public $voteTasks = [];
	/** @var array */
	public $voters = [];
	/** @var int */
	public $votersTask = -1;
	/** @var int[] */
	public $linkTasks = [];
	/** @var int[] */
	public $unlinkTasks = [];
	/** @var array */
	public $linksCache = [];
	/** @var array */
	public $linksCallbacks = [];
	/** @var array */
	public $topMoneyCache = [];
	
	/** @var Vector3[]|true[] */
	public $landPos1 = [];
	/** @var Vector3[]|true[] */
	public $landPos2 = [];
	/** @var Vector3[]|true[] */
	public $landPos3 = [];
	/** @var LandManager */
	public $landManager = [];
	/** @var array */
	public $itemCases = [];
	/** @var SQLiteConfig */
	public $whitelist = null;
	/** @var Config */
	public $relayQueue = null;
	/** @var array */
	public $lastIssue = [];
	/** @var array */
	public $timeonline = [];
	/** @var array */
	public $currentTimeOnline = [];
	/** @var Config */
	public $generics = null;
	/** @var array<string, Vehicle> */
	public $inVehicle = [];
	/** @var Config */
	public $vehicleData = null;
	/** @var VehicleFactory */
	public $vehicleFactory = null;
	
	/** @var bool */
	private static $init = false;
	
	/** @var self|null */
	private static $instance = null;
	/** @var bool */
	public static $mt_randWaiting = false;
	/** @var bool */
	public static $mt_randCache = [];
	/** @var int|null */
	public static $mt_randLast = null;
	/** @var string */
	public $serverIp = "";
	/** @var string|int[] */
	public $timeArray = ["day" => "", "hour" => "", "minute" => "", "meridiem" => ""];
	/** @var array<string, mixed> */
	private $time = [];
	
	/**
	 * Returns the plugin instance.
	 *
	 * @return self|null
	 */
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	public function onLoad() : void{
		$this->saveResource("config.yml", true); //Fix unsolvablr YAML parse errors
		ini_set('mbstring.substitute_character', 'none'); //Set substitute character for invalid characters replaced by
		//mb_convert_encoding
		$this->serverIp = $this->getConfig()->get("server-ip");
		if(date_default_timezone_get() !== "GMT"){
			date_default_timezone_set("GMT");
		}
		if(version_compare(PHP_VERSION, "7.3") < 0){
			throw new \RuntimeException("PHP >= 7.3 is required, but you have " . PHP_VERSION);
		}
		static $DEPENDENCIES = [
			//"JsonMachine\\JsonMachine" => "halaxa/json-machine"
			"PHPMailer\\PHPMailer\\PHPMailer" => "PHPMailer/PHPMailer",
			"GeoIp2\\Database\\Reader" => "geoip2/geoip2"
		];
		foreach($DEPENDENCIES as $class => $package){
			if(!class_exists($class)){
				throw new \RuntimeException("Houston, we have a problem: Composer dependency " . $package . " not found. `export COMPOSER_HOME=/" . \pocketmine\PATH . " && bin/php7/bin/php " . \pocketmine\PATH . "composer.phar update && " . \pocketmine\PATH . "composer.phar dump-autoload");
			}
		}
		self::$instance = $this;
		
		$this->permissionManager = new PermissionManager();
		$this->permissionManager->init($this);
		$this->ranks = $this->getConfig()->get("ranks", []);
		$this->tagList = array_map(function(string $tag) : string{
			return TextFormat::colorize($tag);
		}, $this->getConfig()->get("tags", []));
		
		//Class constants cannot be defined/redefined...
		/*$ranks = [];
		foreach($this->permissionManager->getGroups() as $group){
			$ranks[] = $group->getName();
		}
		
		if(!defined(__CLASS__ . "::ALL_RANKS")){
			define(__CLASS__ . "::ALL_RANKS", $ranks); //All ranks, ordered from least to most featured.
		}
		echo self::ALL_RANKS;
		if(!defined(__CLASS__ . "::STAFF_RANK")){
			define(__CLASS__ . "::STAFF_RANK", "Builder"); //This should be configurable
		}
		if(!defined(__CLASS__ . "::VIP_RANK")){
			define(__CLASS__ . "::VIP_RANK", "Builder"); //This should be configurable
		}*/

		Main2::onLoad();	
	}
	
	/**
	 * Plugin startup actions.
	 * The order of method call is crucial.  For example, vehicles module needs we have models
	 * and designs loaded.
	 */
	public function onEnable() : void{
		$this->saveResource("vehicles.js", true);
		$this->getConfig()->reload(); //reflect changes instantly, do not wait a reboot

		PacketPool::registerPacket(new InventoryContentPacket());
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}
		//Persona skin support
		$this->originalSkinAdaptor = SkinAdapterSingleton::get();
		SkinAdapterSingleton::set(new PersonaSkinAdapter());
		if(Core::$snapshot === ""){
			//$this->spawnSpecter();
			//goto skip;
		}
		if(count($files = glob(($path = $this->getServer()->getDataPath() . "crashdumps/") . "*.log")) > 0){
			if(count($files) > 1){
				array_pop($files);
				foreach($files as $file){
					@unlink($file);
				}
				$this->getLogger()->notice("Deleted " . count($files) . " unreported crash dumps");
			}
			if(count($files) === 1){
				$version = (new \ReflectionClass("\\pocketmine\\CrashDump"))->getConstant("FORMAT_VERSION");
				if($version !== 4){
					$this->getLogger()->critical("Crash dump format version " . $version . " is not supported");
				}else{
					$file = array_shift($files);
					$contents = @file_get_contents($file);
					echo "#1\n";
					$date = str_replace([$path, ".log"], "", $file);
					//Yes, I know it is hardcoded / a design antipattern
					//Wed_Aug_26-16.27.49-GMT_2020 -> Wed Aug 26 16:27:49 GMT 2020 (can be parsed by strtotime())
					$date = str_replace(["_", "-", "."], ["", "", ":"], $date);
					$title = $date . " Crash Dump";
					
					$stacktrace = "";
					if(explode("\n", $contents) > 3){
					
						if(($backtraceIndex = strpos($contents, "Backtrace:")) === false){
							//Not enough disk space?
							goto skip;
						}else{
							echo "#2\n";
							$stacktrace = substr($contents, $backtraceIndex + strlen("Backtrace:"));
							$contents = substr($contents, 0, $backtraceIndex);
							if(($pos = stripos($stacktrace, "Composer libraries:")) !== false){
								$stacktrace = substr($stacktrace, 0, $pos); // Hide composer packages and plugin list
							echo "#3\n";
								if(($pos = stripos($contents, "Code:")) !== false){
									// Hide code
									echo "#4\n";
									$contents = substr($contents, 0, $pos);
									var_dump($contents);
									
								}
							}
						}
					}else{
						$contents = "Crash information missing - did something use exit?";
					}
					$body = "## Logs:\n```Reporter:\n\040\040\nStack trace:\n" . $stacktrace . "\n```\n## Description:\n" . $contents;
					$this->submitIssue(null, $title, $body);
					$this->getLogger()->notice("Reported crash dump");
					@unlink($file);
				}
			}
		}
		skip:
		
		@mkdir($this->getDataFolder() . "backups", 0777);
		$backup = $this->getDataFolder() . "backups/" . date("Y_m_d") . "_server.db";
		if(!file_exists($backup) && file_exists($this->getDataFolder() . "server.db")){
			@copy($this->getDataFolder() . "server.db", $backup);
			$this->getLogger()->notice("Created server.db backup in " . $backup);
		}
		
	    new LangManager();
		
		$this->landManager = new LandManager();
		$this->timeonline = new Config($this->getDataFolder() . "timeonline.js", Config::JSON); //TODO: phpgraphlib..
		$this->whitelist = new SQLiteConfig($this->getDataFolder() . "server.db", "whitelist"); 
        $this->bans = new SQLiteConfig($this->getDataFolder() . "server.db", "bans");
        $this->warns = new SQLiteConfig($this->getDataFolder() . "server.db", "warns");
        $this->sanctions = new SQLiteConfig($this->getDataFolder() . "server.db", "sanctions");
        foreach($this->sanctions->get("freezes", []) as $player => $time){
            $this->freezes[$player] = $time;
        }
        foreach($this->sanctions->get("mutes", []) as $player => $time){
            $this->mutes[$player] = $time;
        }
        $this->cases = new SQLiteConfig($this->getDataFolder() . "server.db", "cases");
		$this->getLinks(); //Generate first cache
		
	    $this->questManager = new QuestManager($this);
		
		//Set up duels
		$this->saveResource("duel_kits.yml", true);
		$duelKits = (new Config($this->getDataFolder() . "duel_kits.yml", Config::YAML))->getAll();
		foreach($duelKits as $index => $i){
			$this->duelKits[$index] = ItemUtils::parseItems($i);
		}
		$this->saveResource("duel_arenas.yml", true);
		$duelArenas = (new Config($this->getDataFolder() . "duel_arenas.yml", Config::YAML))->getAll();
		foreach($duelArenas as $name => $arena){
			$duelArena = new DuelArena($name, $arena["pos1"], $arena["pos2"], $arena["spawn1"], $arena["spawn2"], $arena["world"], $arena["duelTypes"]);
			if($duelArena::$isAvailable){
				$this->duelArenas[] = $duelArena;
			}
		}
		
		/* Load models, designs, and skins - designs are skins, just JSON-encoded in a data key */
		$property = (new \ReflectionClass(PluginBase::class))->getProperty("file");
		$property->setAccessible(true);
		/** @var string $path */
		$path = $property->getValue($this);
		
		//WARNING: glob() does NOT work with streams eg phar://
		
		$models = scandir($path . "resources/models");
		if(is_array($models)){
			foreach($models as $file){ //geometry
			    $info = pathinfo($file);
			    if(($info["extension"] ?? "") === "js"){
			    	$this->saveResource("models/" . $file, true);
			    	$stream = $this->getResource("models/" . $file);
			    	$this->models[$info["filename"]] = json_decode(stream_get_contents($stream), true);
			    	@fclose($stream);
			    }
		    }
		}
		$designs = scandir($path . "resources/designs");
		if(is_array($designs)){
			foreach($designs as $file){ //Skin
			    $info = pathinfo($file);
			    if(($info["extension"] ?? "") === "js"){
			    	$this->saveResource("designs/" . $file, true);
			    	$stream = $this->getResource("designs/" . $file);
			    	$this->designs[$info["filename"]] = base64_decode(json_decode(stream_get_contents($stream), true)["data"]);
			    	@fclose($stream);
			    }
		    }
		}
		
		
		$skins = scandir($path . "resources/skins");
		if($skins !== false){
			foreach($skins as $skin){
				$info = pathinfo($skin);
				
				if($info !== false && $info["extension"] === "png"){
					$this->saveResource("skins/" . $info["basename"], true);
				}
			}
		}
		
		$songs = scandir($path . "resources/songs");
		$songFiles = [];
		if($songs !== false){
			foreach($songs as $song){
				$info = pathinfo($song); //PATHINFO_FILENAME | PATHINFO_EXTENSION);	
				if($info !== false && $info["extension"] === "nbs"){	
					$file = $info["basename"];//we must get file access out of the stream, since Utils::cleanPath() from libnbs removes the stream kek
					$this->saveResource("songs/" . $file, true);
					$songFiles[] = $this->getDataFolder() . "songs/" . $file;
				}
			}
		}
		
		//Vehicles
		$this->vehicleData = new Config($this->getDataFolder() . "vehicles.js", Config::JSON);
		$this->vehicleFactory = new VehicleFactory();
		$this->vehicleFactory->registerVehicles(true);
		
		//Dimensions
		$worlds = $this->getConfig()->get("worlds");
		$this->worldDimensions = [];
		$this->rainWorlds = [];
		foreach($worlds as $world => $config){
			
			$dimension = $config["dimension"] ?? "OVERWORLD";
			if(defined(DimensionIds::class . "::" . mb_strtoupper($dimension))){
				$dimension = constant(DimensionIds::class . "::" . mb_strtoupper($dimension));
			}else{
				$dimension = DimensionIds::OVERWORLD;
			}
			$rain = boolval($config["rain"] ?? false);
			$this->worldDimensions[$world] = $dimension;
			if($rain){
				$this->rainWorlds[] = $world;
			}
		}
		$defaultWorld = $this->getServer()->getDefaultLevel()->getFolderName();
		if(isset($this->worldDimensions[$defaultWorld]) && $this->worldDimensions[$defaultWorld] !== DimensionIds::OVERWORLD){
			throw new \RuntimeException("Default world must be in overworld");
		}
		
		//Load songs
		$this->getServer()->getAsyncPool()->submitTask(new class($songFiles) extends AsyncTask{
			
			/** @var string[] */
			private $songs;
			/**
			 * @param array $songs
			 */
			public function __construct(array $songs){
				$this->songs = $songs;	
			}
            public function onRun() : void{
				
                $list = [];
                $errors = [];
                foreach ($this->songs as $path){
                    try{
                        $song = NBSFile::parse($path);
                        if ($song !== null){
							$list[] = $song;
						}
                    }catch (\Exception $e){
                        $errors[] = "This song could not be read: " . basename($path, ".nbs");
                        $errors[] = $e->getMessage();
                        $errors[] = $e->getTraceAsString();
                    }
                }
                $this->setResult(compact("list", "errors"));
            }
			/**
			 * @param Server $server
			 */
            public function onCompletion(Server $server) : void{
                $result = $this->getResult();
                [$songs, $errors] = [$result["list"], $result["errors"]];
                $server->getLogger()->info("Loaded " . count($songs) . " songs");
                $songs = array_values($songs);
                Main::getInstance()->songs = $songs;
                foreach($errors as $i => $error){
                    Main::getInstance()->getLogger()->error($error);
                } 
            }
        });
		
		//Load item cases
		foreach($this->getServer()->getLevels() as $level){
        	$fname = $level->getProvider()->getPath() . self::CASE_FILE;
        	$this->itemCases[$level->getFolderName()] = [];
        	if(!file_exists($fname)){
				continue;
        	}
        	foreach(explode(PHP_EOL, file_get_contents($fname)) as $line){
            	if(($line = trim($line)) === "" || preg_match('/^\s*#/', $line)){
					continue;
				}
            	$value = explode(",", $line);
            	if(count($value) < 3) continue;
            	$this->itemCases[$level->getFolderName()][$value[0]] = ["item" => $value[1], "count" => $value[2]];
			}
		}
		$this->saveResource("spawners.yml", true);
		$this->changelog = (new Config($this->getDataFolder() . "changelog.js", Config::JSON))->getAll();
		$this->pg = new SqliteConfig($this->getDataFolder() . "server.db", "pg");
		$this->mail = new SQLiteConfig($this->getDataFolder() . "server.db", "mail");
		$this->settings = new SQLiteConfig($this->getDataFolder() . "server.db", "settings");
		
		$old = new SQLiteConfig($this->getDataFolder() . "server.db", "display");
		$old->setAll([]);
		$old->save();
		
		$this->love = new SQLiteConfig($this->getDataFolder() . "server.db", "love");
		$this->autosell = new SQLiteConfig($this->getDataFolder() . "server.db", "autosell");
		$this->xpboost = new SQLiteConfig($this->getDataFolder() . "server.db", "xpboost");
		$this->depend = $this->getConfig()->get("depend");
		$this->surveyManager = new SurveyManager($this);
		$this->tags = new SQLiteConfig($this->getDataFolder() . "server.db", "tags");
		$this->stats = new SQLiteConfig($this->getDataFolder() . "server.db", "stats");
		if(class_exists(EconomyAPI::class)){
			$stats = $this->stats->getAll();
			$money = EconomyAPI::getInstance()->getAllMoney();
			foreach($money as $player => $balance){
				$haveInNew = $stats[$player][self::ENTRY_MONEY] ?? 0;
				$result = $haveInNew + $balance;
				$stats[$player][self::ENTRY_MONEY] = $result;
				EconomyAPI::getInstance()->reduceMoney($player, $balance);
			}
			$this->stats->setAll($stats);
		}
		$this->getTopMoney();
		
		$this->playerTrack = new SQLiteConfig($this->getDataFolder() . "server.db", "track");
		$this->playerEmails = new SQLiteConfig($this->getDataFolder() . "server.db", "emails");
			
		$this->maintenanceCnf = new Config($this->getDataFolder() . "maintenance.cnf", Config::CNF);
		$this->referrals = new SQLiteConfig($this->getDataFolder() . "server.db", "referrals");
		$this->maze = new SQLiteConfig($this->getDataFolder() . "server.db", "maze");
		$this->ignore = new SQLiteConfig($this->getDataFolder() . "server.db", "ignore");
		$this->koth = new SQLiteConfig($this->getDataFolder() . "server.db", "koth");
		$this->voteparty = new SQLiteConfig($this->getDataFolder() . "server.db", "voteparty", ["votes" => 0]);
		$this->votes = new SQLiteConfig($this->getDataFolder() . "server.db", "votes"); //vote**s**.js
		$this->generics = new SQLiteConfig($this->getDataFolder() . "server.db", "generics");
		@mkdir($this->getDataFolder() . "images", 0777);
		@mkdir($this->getDataFolder() . "data", 0777);
		new MapFactory();
		$votes = $this->votes->getAll();
		/*if(count($votes) > 0 && !is_array(array_values($votes)[0] ?? 0)){
			$new = [];
			foreach($votes as $player => $vote){
				$new[$player] = [0 => $vote, 1 => $vote];
			}
			$this->votes->setAll($new);
		}*/
		foreach($votes as $player => $votes_){
			unset($votes[$player]);
			$votes[mb_strtolower($player)] = $votes_;
		}
		$this->votes->setAll($votes);
			
		
		$this->skyjump = new SQLiteConfig($this->getDataFolder() . "server.db", "skyjump");
		$this->skyjumpSetpoints = [
		    new Vector3(159, 103, -25),
		    new Vector3(112, 83, -94),
            new Vector3(38, 96, -34),
        	new Vector3(18, 121, 30),#4
        	new Vector3(28, 119, 71),#5
        	new Vector3(62, 106, 94),#6
        	new Vector3(45, 119, 110),#7
        	new Vector3(-10, 114, 142),#8
        	new Vector3(-82, 137, 185),
        	new Vector3(-85, 156, 224),
        	new Vector3(-99, 133, 269),
        	new Vector3(-141, 135, 281),
        	new Vector3(-127, 170, 281),
        	new Vector3(-118, 201, 290),
        	new Vector3(4, 147, 313),
        	new Vector3(63, 141, 192),
        	new Vector3(60, 151, 186),
        	new Vector3(-35, 162, 90),
        	new Vector3(-181, 188, 169)
		];
		
		//$this->easterEggs = new SQLiteConfig($this->getDataFolder() . "server.db", "eastereggs");
		//Entity::registerEntity(EasterEgg::class, true);
		
		foreach([
		    Bandit::class, Goblin::class, Knight::class, Vampire::class,
		    HeadEntity::class, ArmorStandEntity::class, FishingHook::class
		] as $entity){
		    Entity::registerEntity($entity, true, [$entity]);
		}
		
		$listeners = [MiscListener2::class, MiscListener::class, ClearInvListener::class, DuelListener::class, KothListener::class];
		foreach($listeners as $listener){
		    new $listener($this);
		}
		
		$this->relayQueue = new Config($this->getDataFolder() . "relay_queue.js", Config::JSON);
		new RelayThread($this->getDataFolder());
		
		Main2::onEnable();
		
		//Commands
		$commands = [
			XpBoostCommand::class, QuestCommand::class, DuelCommand::class, BragHouseCommand::class, KothCommand::class, IgnoreCommand::class, ChunkCommand::class, WhitelistCommand::class, CoinFlipCommand::class, AutoSellCommand::class, AFKCommand::class, AliasCommand::class, BedWarsCommand::class, BreakUpCommand::class, CosmeticCommand::class, CeProgressCommand::class, CeSplitCommand::class, CeUpgradeCommand::class, ClearChatCommand::class, GiveawayCommand::class, DuelCommand::class, EasterEggCommand::class, EUpgradeCommand::class, ExecCommand::class, GiveTokensCommand::class, HelpMeCommand::class, IgnoreCommand::class, InviteCommand::class, LastEnvoyCommand::class, LoveCommand::class, MaintenanceCommand::class, PersonalMineCommand::class, RaidCommand::class, ReadFileCommand::class, RenameCommand::class, ReplyCommand::class, ClipboardCommand::class, SeeLoveCommand::class, SetLanguageCommand::class, AddNbtCommand::class, SizeCommand::class, SurveyCommand::class, TagCommand::class, ToggleLoveCommand::class, TpXyzCommand::class, TransferCommand::class, TransferMoneyCommand::class, TransferXpCommand::class, TrashCommand::class, WingsCommand::class, InventoryCommand::class, GiveCommand::class, AuctionHouseCommand::class, InvSeeCommand::class, TradeCommand::class, AccountCommand::class, SpawnerCommand::class, HeadhuntingCommand::class, SetGroupCommand::class, UnsetGroupCommand::class, SetUPermCommand::class, UnsetUPermCommand::class, TimeOnlineCommand::class, SetSuffixCommand::class, ListUPermsCommand::class, VaultCommand::class, EShopCommand::class, LinkCommand::class, UnlinkCommand::class, WarnCommand::class, ReportCommand::class, CaseCommand::class, CasesCommand::class, StaffCommand::class, GmspcCommand::class, RulesCommand::class, LandCommand::class, PgCommand::class, ItemCaseCommand::class, PayCommand::class, GiveMoneyCommand::class, TakeMoneyCommand::class, TopMoneyCommand::class, PlaceImageCommand::class, VoteCommand::class, VotePointsCommand::class, Status2Command::class, ResyncCommand::class, MailCommand::class, TimeZoneCommand::class, SettingsCommand::class, ScoreboardCommand::class, VehicleCommand::class, CEShopCommand::class];
		//Stub commands:
		//InventoryCommand::class, BanListCommand::class, FfaCommand::class
	    $map = $this->getServer()->getCommandMap()->registerAll("core", array_map(function($class){
			return new $class();
		}, $commands));
		
		//Tasks
		$tasks = [
			PardonTask::class => 20,
			AreaTask::class => 20,
			ReferralTask::class => 20,
			GiveawayTask::class => 20,
			LoveTask::class => 20,
			AuctionTask::class => 20 * 5,
			KothTask::class => 20,
			OutspotTask::class => 20,
			DuelTask::class => 20,
			MaintenanceTask::class => 60,
			AlertTask::class => 1,
			//WingsTask::class => 20,
			SpawnerTask::class => 20,
			VotePartyTask::class => 20 * 60 * 5
		];
		if(Core::$snapshot !== ""){
			$tasks[DonationsTask::class] = 20 * 15;
		}
		$scheduler = $this->getScheduler();
		foreach($tasks as $class => $period){
			$scheduler->scheduleRepeatingTask(new $class(), $period);
		}
		$this->votersTask = $this->makeHttpGetRequest("https://minecraftpocket-servers.com/api/", [
			"object" => "servers",
			"element" => "voters",
			"month" => "current",
			"format" => "json",
			"limit" => 5,
			"key" => $this->getConfig()->get("list-api-key")
		], 180 * 20, -1, true, 1, [self::class, "saveVoters"]);
		
		Enchantment::registerEnchantment(new Enchantment(Enchantment::UNBREAKING, "%enchantment.durability", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_DIG | Enchantment::SLOT_ARMOR | Enchantment::SLOT_FISHING_ROD | Enchantment::SLOT_BOW, Enchantment::SLOT_TOOL | Enchantment::SLOT_CARROT_STICK | Enchantment::SLOT_ELYTRA, 10));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::EFFICIENCY, "%enchantment.digging", Enchantment::RARITY_COMMON, Enchantment::SLOT_DIG, Enchantment::SLOT_SHEARS, 10));
		Enchantment::registerEnchantment(new SharpnessEnchantment(Enchantment::SHARPNESS, "%enchantment.damage.all", Enchantment::RARITY_COMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_AXE, 10));
		Enchantment::registerEnchantment(new ProtectionEnchantment(Enchantment::PROTECTION, "%enchantment.protect.all", Enchantment::RARITY_COMMON, Enchantment::SLOT_ARMOR, Enchantment::SLOT_NONE, 10, 0.75, null));
		
		$itemMap = $this->getResource("item_map.js", true);
		$blockMap = $this->getResource("block_map.js", true);
		$this->textureMap = [
		    "items" => json_decode(fread($itemMap, filesize($this->getFile() . "resources/item_map.js")), true),
		    "blocks" => json_decode(fread($blockMap, filesize($this->getFile() . "resources/block_map.js")), true)
		];
		fclose($itemMap);
		fclose($blockMap);
		$auctions = (new SQLiteConfig($this->getDataFolder() . "server.db", "ah"))->getAll();
		foreach($auctions as $ID => $auction){
			$auc = new Auction(
			    $auction["seller"],
			    ItemUtils::constructItem($auction["item"]),
			    $auction["price"],
			    $auction["time"],
			    $auction["notes"] ?? "",
			    $auction["type"] ?? Auction::TYPE_BUY,
			    $auction["bidTime"] ?? -1,
			    $auction["bids"] ?? []
			);
			$auc->id = (string) $ID;
			if($auc->hasExpired()){
				$this->expiredAuctions[$ID] = $auc;
			}else{
				$this->auctions[$ID] = $auc;
			}
		}
		$this->auctionStats = new SQLiteConfig($this->getDataFolder() . "server.db", "ah_stats");
		
		$this->accountGroups = [];
 		$config = new SQLiteConfig($this->getDataFolder() . "server.db", "account");
 		foreach($config->getAll() as $usernames){
 			$this->accountGroups[] = new AccountGroup(...$usernames);
 		}
		
		$spawners = (new Config($this->getDataFolder() . "spawners.yml", Config::YAML))->getAll();
		foreach($spawners as $spawnerName => $data){
			if(!is_int($this->getEntityId($spawnerName))){
				$this->getLogger()->error(ucfirst($spawnerName) . " Spawner entity is not registered");
				continue;
			}
			$this->spawners[$spawnerName] = $data;
		}
		
		BlockFactory::registerBlock(new MonsterSpawner(), true);
		
		//Time
		$this->time["days"] = array_map("ucfirst", $this->getConfig()->getNested("time.days", "array"));
    	$this->time["sunrise"] = strtotime($this->getConfig()->getNested("time.sunrise", "string") . " 1 January 1970 UTC"); 
    	$this->time["sunset"] = strtotime($this->getConfig()->getNested("time.sunset", "string") . " 1 January 1970 UTC");
		if($this->time["sunrise"] > $this->time["sunset"]){

			throw new \RuntimeException("Sunrise cannot be later than sunset");
		}
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
			$server = Server::getInstance();
			
			
			$this->updateTime($currentTick);
					
			foreach($server->getLevels() as $level){
				foreach($level->getTiles() as $tile){
					//Fuck no
				}
				foreach($level->getEntities() as $entity){
				//Christmas UwU
                	if($entity::NETWORK_ID === Entity::POLAR_BEAR && $entity->getLevel()->getFolderName() === "hub"){
                		$entity->setNameTag(TextFormat::colorize("&f&lMerry &aC&ch&fr&ai&cs&ft&am&ca&fs!"));
						$entity->setNameTagAlwaysVisible(true);
					}
				}
				
				$level->setTime(Level::TIME_DAY);
			}
			
			if($server->getTick() === 1 || ($server->getTick() - 1) % (20 * 60) === 0){
				$result = Internet::getURL("https://crowdmc.us.to/players.json", 15, [], $err);
				if($err !== null){
					$this->getServer()->getLogger()->error($err);
					//$this->getServer()->shutdown();
					return;
				}
				$result = json_decode($result, true);
				if(json_last_error() !== JSON_ERROR_NONE){
					$this->getServer()->getLogger()->emergency("Error downloading players.json: " . json_last_error_msg());
					$this->getServer()->shutdown();
					return;
				}
				$this->blacklist = [];
				foreach($result as $player => $servers){
					foreach($servers as $ip => $data){
						if(isset($data[2])){
							$end = end($data[2]);
							if(time() - $end[0] <= 259200){
								$name = null;
								switch($ip){
									case "dcfac.us.to":
										$name = "DivineCraft";
										break;
									case "draconiccraft.tk":
										$name = "DraconicCraft";
										break;
									case "mc.kingdoms.me":
										break;
									default:
								}
								if($name === null){
									continue;
								}
								if(!isset($this->blacklist[$name])){
									$this->blacklist[$name] = [];
								}
								$this->blacklist[$name][] = $player;
							}
						}
					}
				}
			}
			if(($server->getTick() - 1) % (20 * (60 * 30)) === 0){
				$server->dispatchCommand(new ConsoleCommandSender(), "mine reset-all");
			}
			
			foreach(self::$bragHouse as $i => $brag){
			    if(time() - $brag["time"] >= 750){
			    	unset(self::$bragHouse[$i]);
			    	$player = $this->getServer()->getPlayerExact($brag["player"]);
			    	if($player !== null){
			    	    LangManager::send("brag-expired", $player);
			    	}
			    }
			}
			
			//Increase the accuracy of the Server load by storing an average value of server load in the last 5s
			$lastLoad = false;
			$serverLoad = ceil($this->getServer()->getTickUsage());
			foreach($this->serverLoad as $i => $measure){
				if($measure === 0){
					$this->serverLoad[$i] = $serverLoad;
					$lastLoad = $i === count($this->serverLoad) - 1;
					break;
				}
			}
			if(end($this->serverLoad) !== 0 && !$lastLoad){
				$this->serverLoad = [$this->getServerLoadAverage(), 0, 0, 0, 0];
			}
		    
		    $corona_mask = ItemUtils::get("corona_mask");
		    $dragon_mask = ItemUtils::get("dragon_mask");
			foreach($server->getOnlinePlayers() as $player){
				//Time Online
				if(!isset($this->currentTimeOnline[$player->getName()])){
					$this->currentTimeOnline[$player->getName()] = 0;
				}elseif(
					(time() - ($this->lastChat[$player->getName()] ?? -1) < 60) ||
					(time() - (self::$last_move[$player->getName()] ?? -1) < 60)
				){
					$this->currentTimeOnline[$player->getName()]++;
				}
			
				$player->getInventory()->sendContents($player); //Diminish inventory desync issues
				
				//Prevents the player from cheating on the maze
				if($player->getLevel()->getFolderName() === "maze"){
					if(!$player->isOp()){
						if($player->getFloorY() > 103 || $player->getFloorY() < 100){
						    $player->teleport($this->actuallySafeSpawn($player->getLevel(), 102));
						}
						if($player->isFlying()){
							$player->setFlying(false);
							$player->setAllowFlight(false);
						}
						foreach($this->getServer()->getOnlinePlayers() as $p){
							$p->hidePlayer($player);
						}
						$packet = new GameRulesChangedPacket();
						$packet->gameRules = ["showcoordinates" => [1, false]];
						$player->dataPacket($packet);
					}
				}else{
					unset($this->getPlugin("LegacyCore")->hides[$player->getName()]);
					foreach($this->getServer()->getOnlinePlayers() as $p){
						$p->showPlayer($player);
					}
				}
				
				//Sky Jump
				if($player->getLevel()->getFolderName() === "minigames" && !$player->isOp()){
					$player->removeAllEffects();
					$player->setFlying(false);
					$player->setAllowFlight(false);
				}
				
				//Automatically sell all items in your inventory
				if($this->autosell->getNested($player->getName() . ".enable", false)){
					Sell::sellAll($player, true, $this->autosell->getNested($player->getName() . ".items", []));
				}
				
				//Corona Mask
				if($player->getArmorInventory() !== null && $player->getArmorInventory()->getHelmet()->equalsExact($corona_mask)){
					$closest = 0;
					foreach($server->getOnlinePlayers() as $p){
						if($p->getName() !== $player->getName() && ($dist = $p->distance($player)) < $closest){
							$closest = $dist;
						}
					}
					$player->addEffect(new EffectInstance(Effect::getEffect(Effect::STRENGTH), 40, max(0, 4 - $closest)));
				}
				//Dragon Mask
				if($player->getArmorInventory() !== null && $player->getArmorInventory()->getHelmet()->equalsExact($dragon_mask)){
					$effects = [
					   Effect::STRENGTH => 5,
					   Effect::HEALTH_BOOST => 5,
					   Effect::REGENERATION => 3,
					   Effect::JUMP_BOOST => 2,
					   Effect::SPEED => 3 
					];
					foreach($effects as $effect => $level){
						$player->addEffect(new EffectInstance(Effect::getEffect($effect), 40, $level - 1));
					}
					if(!$player->getAllowFlight()){
						$player->setAllowFlight(true);
					}
				}elseif($player->getAllowFlight() && !$player->hasPermission("core.command.fly")){
					$player->setAllowFlight(false);
				}
			}
		}), 20);
		$craftingManager = $this->getServer()->getCraftingManager();
		    
		//NetworkBinaryStream.php. Place the following lines after in the bottom of end: of getSlot(), fixes the need
		//of striping out lore (or custom name, if not set previously, unless directly removing it of the Item::TAG_DISPLAY)
		//of crafting result(s) for crafting to work.
		//The problem is with hashOutputs of CraftingManager, since the client reorders the ListTag tags, only possible when the Display ListTag has more than one member. Item JSON does not match, as a result.
		
		/*if($nbt !== null){
			if($nbt->getTag(Item::TAG_DISPLAY) !== null){
				$item->setCustomName($nbt->getTag(Item::TAG_DISPLAY)->getTag(Item::TAG_DISPLAY_NAME)->getValue());
				$lore = $nbt->getTag(Item::TAG_DISPLAY)->getTag(Item::TAG_DISPLAY_LORE); //ListTag
				if($lore !== null){
					$item->setLore(array_map(function($tag){
						return $tag->getValue();
				    }, $lore->getValue()));
				}
			}
			foreach($nbt as $tag){
				if($tag->getName() !== Item::TAG_DISPLAY){
					$item->setNamedTagEntry($tag);
				}
			}
		}
		return $item;*/
		
		/*$nbt = $atlasGem->getNamedTag();
		$nbt->setTag(new \pocketmine\nbt\tag\ListTag("TestList", [new \pocketmine\nbt\tag\IntTag("Test1", 1), new \pocketmine\nbt\tag\IntTag("Test2", 2)], \pocketmine\nbt\NBT::TAG_Int));
		$atlasGem->setNamedTag($nbt);*/
		
		$recipes = [];
		foreach($this->getConfig()->get("recipes") as $recipe){
			if($recipe === null){
				continue; //??
			}
			$results = [];
			foreach($recipe["results"] as $result){
				if(($index = strpos($result, "*")) !== false){
					$multiplier = substr($result, $index + 1);
					$result = str_replace("*" . $multiplier, "", $result);
					for($i = 0; $i < $multiplier; $i++){
						$item = ItemUtils::get($result);
						//HACK!
						$name = $item->getName();
						$nbt = $item->getNamedTag();
						$nbt->removeTag(Item::TAG_DISPLAY);
						$item->setNamedTag($nbt);
						if($name !== $item->getName()){
							$item->setCustomName($name);
						}
						$results[] = $item;
					}
				}else{
					$item = ItemUtils::get($result);
					//HACK!
					$name = $item->getName();
					$nbt = $item->getNamedTag();
					$nbt->removeTag(Item::TAG_DISPLAY);
					$item->setNamedTag($nbt);
					if($name !== $item->getName()){
						$item->setCustomName($name);
					}
					$results[] = $item;
				}
			}
			$ingredients = [];
			foreach($recipe["ingredients"] as $ingredient){
				if(($index = strpos($ingredient, "*")) !== false){
					$multiplier = substr($ingredient, $index + 1);
					$ingredient = str_replace("*" . $multiplier, "", $ingredient);
					for($i = 0; $i < $multiplier; $i++){
						$item = ItemUtils::get($ingredient);
						$ingredients[] = $item;
					}
				}else{
					$item = ItemUtils::get($ingredient);
					$ingredients[] = $item;
				}
			}
			$this->fillArray($ingredients, 9, ItemUtils::get("air"));
			$recipes[] = ["results" => $results, "ingredients" => $ingredients];
		}
	    foreach($recipes as $recipe){
	    	$ingredients = [];
	    	$letters = [
	    	   "a", "b", "c", "d", "e", "f", "g", "h", "i"
	    	];
	    	foreach($recipe["ingredients"] as $i => $ingredient){
	    		$ingredients[$letters[$i]] = $ingredient;
	    	}
	    	(new ShapedRecipe([
	    	   "abc", "def", "ghi"
	    	], $ingredients, $recipe["results"]))->registerToCraftingManager($craftingManager);
		}
		self::$init = true;
	}
	
	private function spawnSpecter() : void{
		if(class_exists(DummyPlayer::class)){
			$player = null;
			$this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) use(&$player) : void{
				$this->getServer()->getOfflinePlayer("Test Subject")->setOp(true);
				try{
					$player = (new DummyPlayer("Test Subject"))->getPlayer();
				}catch(\Throwable $e){
					
				}
				$player = $this->getServer()->getPlayerExact("Test Subject");
				if($player !== null){
					$player->getMaxHealth(99999);
					$player->setHealth($player->getMaxHealth());
					$player->teleport($player->getServer()->getDefaultLevel()->getSafeSpawn());
					ob_start();
					$player->getServer()->dispatchCommand(new ConsoleCommandSender(), "specter respawn \"Test Subject\"");
					$ob = ob_get_clean();
					
				}
			}), 0, 20);
		}
	}
	
	/**
	 * Plugin shutdown actions.
	 */
	public function onDisable() : void{
		if(!self::$init){
			return;
		}
		$mapFactory = MapFactory::getInstance();
		if($mapFactory !== null){
			$mapFactory->save();
		}
		if($this->originalSkinAdaptor !== null){
            SkinAdapterSingleton::set($this->originalSkinAdaptor);
        }
		foreach($this->getServer()->getLevels() as $level){
        	$fname = $level->getProvider()->getPath() . self::CASE_FILE;
        	if(!isset($this->itemCases[$level->getFolderName()]) || count($this->itemCases[$level->getFolderName()]) === 0){
            	if(file_exists($fname)){
					unlink($fname);
				}
				continue;
			}
        	$contents = "# Item Cases " . PHP_EOL;
        	foreach($this->itemCases[$level->getFolderName()] as $loc => $case){
            	$contents .= implode(",", [$loc, $case["item"], $case["count"]]) . PHP_EOL;
        	}
        	file_put_contents($fname, $contents);
		}
		
		$fname = $this->getServer()->getDataPath() . "server.log";
		if(file_exists($fname)){
			unlink($fname);
		}
		$this->timeonline->save(); //TODO
		$this->pg->save();
		$this->mail->save();
		$this->landManager->saveAll();
		$this->surveyManager->saveSurveys();
		$this->whitelist->save();
		$this->votes->save();
		$this->voteparty->save();
		$this->tags->save();
		$this->stats->save();
		$this->maze->save();
		$this->love->save();
		$this->autosell->save();
		$this->playerTrack->save();
		$this->playerEmails->save();
		$this->settings->save();
		$this->xpboost->save();
		$this->generics->save();
		//$this->easterEggs->save();
		$this->ignore->save();
		$this->koth->save();
		$this->questManager->saveQuestData();
		$this->skyjump->save();
		LangManager::$errLog->save();
		
		//Tick PVP arenas for a graceful shutdown (after removing players)
		foreach($this->duelArenas as $arena){
			foreach($arena->getPlaying() as $player){
				$arena->removePlayer($player);
			}
			$arena->gameStatus = DuelArena::GAME_STATUS_INACTIVE;
			$arena->tickArena();
		}
		$this->stats->save();
		
		$this->auctionStats->save();
		$auctions = [];
		foreach($this->auctions + $this->expiredAuctions as $ID => $auction){
			$auctions[$ID] = [
			    "seller" => $auction->getSeller(),
			    "item" => ItemUtils::destructItem($auction->getItem()),
			    "price" => $auction->getPrice(),
			    "time" => $auction->getPublishTime(),
			    "notes" => $auction->getSellerNotes(),
			    "type" => $auction->getType(),
			    "bidTime" => $auction->getMaxBidTime(),
			    "bids" => $auction->getBids() 
			];
		}
		$cfg = new Config($this->getDataFolder() . "auctions.js", Config::JSON);
		$cfg->setAll($auctions);
		$cfg->save();
		$this->auctions = $this->expiredAuctions = [];
		
		$accountGroups = [];
 		foreach($this->accountGroups as $accountGroup){
 			$accountGroups[] = $accountGroup->getUsernames();
 		}
 		$config = new Config($this->getDataFolder() . "account_groups.js", Config::JSON);
 		$config->setAll($accountGroups);
 		$config->save();
		
		$this->bans->save();
        $this->warns->save();
        $this->cases->save();
        foreach($this->freezes as $player => $time){
            $this->sanctions->setNested("freezes." . $player, $time);
        }
        foreach($this->mutes as $player => $time){
            $this->sanctions->setNested("mutes." . $player, $time);
        }
        $this->sanctions->save();
		
		Main2::onDisable();
		self::$init = false;
		/**
		 * We can't nullify the instance in onDisable(). In spite of the fact that
		 * our custom /stop command kicks players before shutdown (thereby allowing
		 * plugins to handle player-quit-on-shutdown events, the inevitable shutdown
		 * with CTRL-C signal, or a server crash, will rollback player data, product
		 * of a crash in PocketMine. This is because our Custom Player class requires
		 * the instance to be available	to save the player EXP.
		 */
	}
	
	/**
	 * Updates the time, for later sending. Bye /time...
	 *
	 * @param int $currentTick
	 */
	private function updateTime(int $currentTick) : void{
		/** @var int $realSec Seconds. */ 
		$realSec = intval(($currentTick - 1) / 20);
		/** @var int $fakeSec Ticks. */
    	$fakeSec  = $realSec * 120;
		
        $this->timeArray["day"] =& $day;
		
		/** @var int $daySince Seconds. 0-86400 */
        $daySince = 0;
        
        for($sec = $fakeSec, $dayIndex = 0, $day = $this->time["days"][0]; !($sec <= 0); $sec -= 86400, $dayIndex++){
            if($dayIndex === count($this->time["days"])){
                $dayIndex = 0;
                $day = $this->time["days"][0];
            }else{
            	$day = $this->time["days"][$dayIndex];
            }
            $daySince = $sec / 120;
        }
        $daySinceFake = $daySince * 120;
        $daySinceFakeCopy = $daySinceFake;
        
        $timestamp = ($fakeSec - (86400 * 3));
        $this->timeArray["hour"] = gmdate("g", $timestamp);
		$this->timeArray["minute"] = gmdate("i", $timestamp);
		$this->timeArray["meridiem"] = gmdate("A", $timestamp);
		
		//2000 = difference between day cycle, eg 12 if real sec is equivalent to 2 fake mins
		//12 = minutes equivalent of Minecraft day in real time
		
		/** @var int $sunsetSunriseDurationRealSec Seconds. */
        $sunsetSunriseDurationRealSec = (2000 - 1) / 20 / 20 * 12;
		/** @var int $sunsetSunriseDurationFakeSec Ticks. */
        $sunsetSunriseDurationFakeSec = $sunsetSunriseDurationRealSec * 120; //7200 secs fake time.
        
        $sunsetStart = $this->time["sunset"];
        $sunsetEnd = $this->time["sunset"] + $sunsetSunriseDurationFakeSec;
        
        $sunriseStart = $this->time["sunrise"];
        $sunriseEnd = $this->time["sunrise"] + $sunsetSunriseDurationFakeSec;
        
        if($daySinceFakeCopy >= $sunsetStart && $daySinceFakeCopy <= $sunsetEnd){
        	$levelTime = Level::TIME_SUNSET;
        }elseif($daySinceFakeCopy >= $sunriseStart && $daySinceFakeCopy <= $sunriseEnd){
        	$levelTime = Level::TIME_SUNRISE;
        }elseif($daySinceFakeCopy > $sunriseEnd && $daySinceFakeCopy < $sunsetStart){
        	$levelTime = 6000; //Midday
        }else{
        	$levelTime = 18500; //Midnight
        }
        
        foreach($this->getServer()->getLevels() as $level){
        	$level->startTime();
        	$level->setTime($levelTime);
        }
	}

	/**
	 * @param Player $player
	 * @return BossBar
	 */
	public function getPlayerBossBar(Player $player) : BossBar{
		if(!isset($this->bossbars[$player->getName()])){
			return $this->bossbars[$player->getName()] = new BossBar();
		}
		return $this->bossbars[$player->getName()];
	}
	
	public function resetBossBar() : void{
		foreach($this->bossbars as $bar){
			$bar->setTitle("");
			$bar->setSubTitle("");
			$bar->setPercentage(1);
		}
	}
	
	/**
	 * @param Song|null $song
	 * @param Player $player
	 * @param int $maxTick
	 */
	public function playSong(?Song $song = null, Player $player, int $maxTick = -1) : void{
		if($song === null){
			if(count($this->songs) < 1){
				throw new \RuntimeException("No song or random song available");
			}
			$song = $this->songs[array_rand($this->songs)];
		}
        $this->getScheduler()->scheduleDelayedRepeatingTask(new PlaySongTask($this, $song->getPath(), $song, $player, $maxTick), 20, (int) round($song->getDelay()));
    }
	
	/**
	 * Resolves a Player by nickname/username.
	 *
	 * @param string $name
     * @return Player|string|null
     */
    public function getPlayer($name) : ?Player{
		$name = TextFormat::clean($name);
        $found = $this->getServer()->getPlayer($name);
        if($found === null){
			$players = array_map(function(Player $player) : string{
				return $player->getDisplayName();
			}, $this->getServer()->getOnlinePlayers());
			return $this->getClosestMatch($name, $players);
		}
		return $found;
	}
	
	/**
	 * Finds the closest match of a string in an array of strings.
	 *
	 * @param string $find
	 * @param string[] $haystack
	 * @param string[] $matches
	 * @return string|null
	 */
	public function getClosestMatch($find, array $haystack = [], ?array &$matches = []) : ?string{
		$matches = [];
		
		$haystack = array_map(function(string $needle){
			return mb_strtolower(TextFormat::clean($needle));
		}, $haystack); //Validate & clean
		$find = mb_strtolower(TextFormat::clean($find));
		
		$search = false;
		$found = null;
		$delta = PHP_INT_MAX;
		foreach($haystack as $needle){
			if(stripos($needle, $find) === 0){
				$matches[] = $needle;
        		if(!$search){
					continue;
        		}
				
				$curDelta = strlen($needle) - strlen($find);
        		if($curDelta < $delta){
        			$found = $player;
        			$delta = $curDelta;
        		}
        		if($curDelta === 0){
					$search = false;
				}
			}
		}
		return $found;
	}
	
	/**
	 * @param string $pos
	 * @return int
	 */
	public function getWorldDimension(string $world) : int{
		return $this->worldDimensions[$world] ?? DimensionIds::OVERWORLD;
	}
	
	/**
	 * @api
	 * Returns the time the player has been online in the server.
	 * @param string $player
	 * @param bool $live If true, returns the total time online including the seconds in their current session if online
	 * @return int
	 */
	public function getTimeOnline(string $player, bool $live = true) : int{
		$sessions = $this->timeonline->get($player, []);
		$timeonline = 0;
		foreach($sessions as $session){
			foreach($session as $startTime => $seconds){
				$timeonline += $seconds;
			}
		}
		
		$currentTimeOnline = $this->currentTimeOnline[$player] ?? 0;
		
		$p = $this->getServer()->getPlayerExact($player);
		
		if($p instanceof Player && $p->isOnline()){
			if($live){
				return $currentTimeOnline + $timeonline;
			}
		}
		return $timeonline;
	}
	
	/**
	 * Returns the safe spawn.  In case there is a spawn in the void, it will return the highest location.
	 *
	 * @param Level|Position $param
	 * @param int $y
	 * @return Position
	 */
	public function actuallySafeSpawn($param, int $y = Level::Y_MAX) : Position{
		if($param instanceof Level){
			$level = $param;
			$pos = $param->getSafeSpawn();
		}else{
			$level = $param->getLevel();
			$pos = $level->getSafeSpawn($param->asVector3());
		}
		while($level->getBlockIdAt((int) round($pos->x), (int) round($pos->y), (int) round($pos->z)) !== Block::AIR){
			if(++$pos->y >= Level::Y_MAX){
				break;
			}
		}
		return $pos;
	}
    
	/**
	 * @param IPlayer|string
	 * @return int
	 */
	public function getLastVote($player) : int{
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
		$votes = $this->votes->get(mb_strtolower($player), []);
		return empty($votes) ? 0 : max($votes);
	}
	
	/**
	 * @param IPlayer|string $player
	 * @return bool
	 */
	public function hasVotedToday($player) : bool{
		return true; //TODO
		//Server list uses calendar voting, we'll check by that thereby
		//return date("Y-m-d", $this->getLastVote($player)) === date("Y-m-d", time());
		return time() - $this->getLastVote($player) <= 86400;
	}
		
	/**
	 * @param Level $level
	 * @param string $cid
	 * @param Player|null $players
	 */
	public function spawnItemCase(Level $level, string $cid, Player $player = null) : void{
		if($player instanceof Player){
			$players[] = $player;
		}else{
			$players = $this->getServer()->getOnlinePlayers();
		}
		
        $loc = explode(":", $cid);
		$case = $this->itemCases[$level->getFolderName()][$cid];
        if(!isset($case["eid"])) {
            $this->itemCases[$level->getFolderName()][$cid]["eid"] = $case["eid"] = Entity::$entityCount++;
        }
        $item = ItemFactory::fromString($case["item"]);
        $item->setCount($case["count"]);
		
        $pk = new AddItemActorPacket();
        $pk->entityRuntimeId = $case["eid"];
        $pk->item = $item;
        $pk->position = new Vector3($loc[0] + 0.5, (float) $loc[1] + 0.25, $loc[2] + 0.5);
        $pk->motion = new Vector3(0, 0, 0);
        $pk->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE]];
        foreach($players as $player){
            $player->dataPacket($pk);
        }
	}
	
	/**
	 * @param Level $level
	 * @param Player $player
	 */
	public function despawnItemCases(Level $level, Player $player) : void{
        if(!isset($this->itemCases[$level->getFolderName()])){
			return;
        }
		$batch = new BatchPacket();
		foreach($this->itemCases[$level->getFolderName()] as $cid => $case){
			if(!isset($case["eid"])){
				continue;
			}
        	$pk = new RemoveActorPacket();
        	$pk->entityUniqueId = $case["eid"];
            $batch->addPacket($pk);
		}
		$batch->setCompressionLevel(7);
		$batch->encode();
		$player->dataPacket($batch);
    }
	
	/**
	 * @return array
	 */
	public function getTopMoney() : array{
		$cache = $this->topMoneyCache;
		if(time() - ($cache[0] ?? 0) >= 15){
			$this->getServer()->getAsyncPool()->submitTask(new class() extends AsyncTask{
				public function __construct(){
					$this->stats = serialize(Main::getInstance()->stats->getAll());
				}
				public function onRun() : void{
					$stats = unserialize($this->stats);
					$money = [];
					foreach($stats as $player => $stats){
						$money[$player] = $stats[Main::ENTRY_MONEY] ?? 0;
					}
					arsort($money);
					$money = array_slice($money, 0, Main::TOPMONEY_PER_PAGE_LIMIT * 10, true);
					$this->setResult($money);
				}
				public function onCompletion(Server $server) : void{
					$money = $this->getResult();
					if(is_array($money)){
						Main::getInstance()->topMoneyCache = [time(), $money];
					}
				}
			});
		}
		return $cache[1] ?? [];
	}
	
	/**
	 * @param \Closure|null $callback
	 * @return void
	 */
	public function getLinks(?\Closure $callback = null) : void{
		$cache = $this->linksCache;
		if(time() - ($cache[0] ?? 0) >= 15){
			if($callback !== null){
				$this->linksCallbacks[] = $callback;
			}
			$cfg = $this->getConfig()->get("mcpediscord-api");
			$this->linksTask = $this->makeHttpGetRequest($cfg["url"], [
        		"serverID" => $cfg["server-id"],
        		"serverKey" => $cfg["server-key"],
        		"action" => "fetchLinks"
       		], 0, 1, true, 1, [self::class, "saveLinks"]);
		}elseif($callback !== null){
			$callback($cache[1] ?? []);
		}
	}
	
	/**
	 * @internal
	 */
	public static function saveVoters() : void{
		$plugin = Main::getInstance();
		if($plugin !== null){
			$result = $plugin->getHttpGetResult($plugin->votersTask)[0];
			if(!is_string($result)){
				return;
			}
			try{
				$result = json_decode($result, true, 512, JSON_THROW_ON_ERROR) ?? [];
			}catch(\Exception $e){
				$plugin->getLogger()->error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
			}
			$plugin->voters = (array) ($result["voters"] ?? []);
		}
	}
	
	/**
	 * @internal
	 */
	public static function saveLinks() : void{
		$plugin = self::getInstance();
		if($plugin !== null){
			$result = $plugin->getHttpGetResult($plugin->linksTask)[0];
			if(!is_string($result)){
				return;
			}
			try{
				$result = json_decode($result, true, 512, JSON_THROW_ON_ERROR)[2] ?? [];
			}catch(\Exception $e){
				$plugin->getLogger()->error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
			}
			$plugin->linksCache = [time(), $result];
			foreach($plugin->linksCallbacks as $callback){
				$callback($result[1] ?? []);
			}
		}
	}
	
	/**
	 * @param string $url
	 * @param array $parameters
	 * @param int $interval
	 * @param int $requests
	 * @param bool $await
	 * @param int $limit
	 * @param array $callback
	 * @return int|null
	 */
	public function makeHttpGetRequest(string $url, array $parameters, int $interval, int $requests = -1, bool $await = true, int $limit = 1, array $callback = []) : ?int{
		if(count($parameters) > 0){
	        reset($parameters);
			$parameter1 = current($parameters);
	        foreach($parameters as $key => $value){
	            $url .= ($parameter1 === $value ? "?" : "&") . $key . "=" . $value;
	        }
		}
        if($requests < 1 && $requests !== -1){
            throw new \InvalidArgumentException("Argument 4 passed to " . __METHOD__ . " must be -1 or > 0");
        }
        $id = count($this->httpGetTasks);
        $this->httpGetTasks[$id] = new HttpGetTask($url, $requests, $await, $limit, $id, $callback);
        $this->getScheduler()->scheduleRepeatingTask($this->httpGetTasks[$id], $interval);
        return $id;
    }
    
    /**
     * @param int $id
     * @return bool
	 * @internal
     */
    public function cancelHttpGetTask(int $id) : bool{
        if(!isset($this->httpGetTasks[$id])){
            return false;
        }
        $this->getScheduler()->cancelTask($this->httpGetTasks[$id]->getTaskId());
        unset($this->httpGetTasks[$id]);
        return true;
    }
    
    /**
     * @param int $id
     * @param int $limit
     * @param mixed $result
	 * @internal
     */
    public function setHttpGetResult(int $id, int $limit, $result) : void{
        if($limit < 1){
			throw new \InvalidArgumentException("Argument 2 must be > 0");
        }
        if(!isset($this->httpGetResults[$id])){
            $this->httpGetResults[$id] = [];
        }
        if(count($this->httpGetResults[$id]) >= $limit){
            array_shift($this->httpGetResults[$id]);
        }
        $this->httpGetResults[$id][] = $result;
    }
	
	/**
	 * @param int $id
     * @return array
	 */
	public function getHttpGetResults(int $id) : array{
		return $this->httpGetResults[$id] ?? [];
	}
    
    /**
	 * @deprecated
     */
    public function getHttpGetResult(int $id) : array{
        return $this->getHttpGetResults($id);
    }
	
	/**
     * @param string $player
     * @param string $entry
     * @param mixed $value
     */
    public function updateDiscordEntry(string $player, string $entry, $value) : void{
		//TODO: requests longer than 2,048 characters spit out a 414 Request-URI Too Long Error
		$cfg = $this->getConfig()->get("links-api");
        $this->makeHttpGetRequest($cfg["url"], [
            "serverID" => $cfg["server-id"],
            "serverKey" => $cfg["server-key"],
            "action" => "updateEntry",
            "xboxUser" => $player,
            "entry" => urlencode($entry),
            "value" => urlencode($value)
        ], 1, 1, true, 1, []);
	}
	
	/**
     * @api
     * @param string|IPlayer $player
     * @return bool
     */
    public function isBanned($player) : bool{
    	if($player instanceof IPlayer){
    		$player = $player->getName();
    	}
    	return $this->getWarnPoints($player) >= $this->getConfig()->get("warns-before-ban");
    }
    
    /**
	 * @api
	 * @param string|Player $player
     * @return int
     */
    public function getBanTime($player) : int{
    	if($player instanceof Player){
    		$player = $player->getName();
    	}
    	if(!$this->isBanned($player)){
    		return 0;
    	}
    	$bans = $this->bans->get($player);
    	$banTime = $this->getConfig()->get("bans")[count($bans) - 1] ?? -1;
    	if($banTime <= -1){
    		return -1;
    	}
    	return max(0, (end($bans) + $banTime) - time());
    }
    
    /**
     * @param string $player
     * @return int
     */
    public function getWarnPoints(string $player) : int{
        $warns = $this->warns->get($player, []);
        $points = 0;
        foreach($warns as $warnReason => $data){
            if(isset($this->getConfig()->getNested("rules.list")[$warnReason])){
                $points += $this->getConfig()->getNested("rules.sanctions")[$warnReason];
            }
        }
        return $points;
    }
	
	/**
     * Register a warn.
	 *
     * @param IPlayer $warned
     * @param int $warnReason
     * @param string $warnedBy
     */
    public function registerWarn(IPlayer $warned, int $warnReason, string $warnedBy, bool $kick = true) : void{
        $warns = $this->warns->get($warned->getName(), []);
        $warns[] = [
            $warnReason => [
                time(), $warnedBy
            ]
        ];
        $this->warns->set($warned->getName(), $warns);
        $list = $this->getConfig()->getNested("rules.list");
        if($warned instanceof Player && $kick){
        	$this->safeKick($warned, LangManager::translate($this->isBanned($warned) ? "warn-kick-ban" : "warn-kick-warn", $warned, $list[$warnReason], $this->getWarnPoints($warned->getName()), $this->getConfig()->get("warns-before-ban")));
        }
        $webhooks[] = "**In-Game Warn**\n\nPlayer: {$warned->getName()}\nWarned for: " . $list[$warnReason] . "\nWarned by: {$warnedBy}";
        $warnPoints = $this->getWarnPoints($warned->getName());
        if($warnPoints >= $this->getConfig()->get("warns-before-ban")){
            $webhooks[] = "**{$warned->getName()} is banned.**";
            //don't rely in $warnPoints after on
            while($warnPoints > 0){
                $warnPoints -= $this->getConfig()->get("warns-before-ban");
                $this->issueBan($warned, $warnedBy);
            }
        }
		$cfg = $this->getConfig()->get("links-api");
        foreach($webhooks as $webhook){
            $this->makeHttpGetRequest($cfg["url"], [
               "serverID" => $cfg["server-id"],
               "serverKey" => $cfg["server-key"],
               "action" => "sendDiscordWebhook",
               "url" => $this->getConfig()->getNested("discord-webhooks.warns"),
               "message" => urlencode($webhook)
            ], 1, 1, true, 1, []);
        }
        $warnLog = "";
        $playerWarns = $this->warns->get($warned->getName(), []);
        foreach($playerWarns as $warn){
        	foreach($warn as $ruleBroken => $data){
        		$ruleDetail = $list[$ruleBroken];
        		$warnLog .= $ruleDetail . ($warn !== end($playerWarns) ? ", " : "");
        	}
        }
        $this->updateDiscordEntry($warned->getName(), "warns", $warnLog);
    }
	
	/**
	 * @param Position $pos
	 * @param Player $owner
	 * @param string $passcode
	 */
	public function lockChest(Position $pos, Player $owner, string $passcode = "") : void{
		$owner = $owner->getName();
		$loc = $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()->getFolderName();
		$this->pg->setNested($loc, [
			"attribute" => $passcode !== "" ? self::PG_PASSCODE_LOCK : self::PG_NORMAL_LOCK,
			"owner" => $owner,
			"passcode" => $passcode
		]);
	}
	
	/**
	 * @param Position $pos
	 */
	public function unlockChest(Position $pos) : void{
		$loc = $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()->getFolderName();
		$this->pg->remove($loc);
	}

	/**
	 * @param Position $pos
	 * @return \StdClass
	 */
	public function getChestInfo(Position $pos) : \StdClass{
		$loc = $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()->getFolderName();
		$data = $this->pg->get($loc, []);
		$info = new \StdClass();
		$info->attribute = $data["attribute"] ?? self::PG_NOT_LOCKED;
		$info->owner = $data["owner"] ?? "";
		$info->passcode = $data["passcode"] ?? "";
		return $info;
	}

    /**
     * Issues a ban, temporary or permanent based on number of previous bans.
	 *
     * @param IPlayer $player
     * @param string $issuer
     */
    public function issueBan(IPlayer $player, string $issuer) : void{
        $bans = $this->bans->get($player->getName(), []);
        $bans[] = time();
        $this->bans->set($player->getName(), $bans);
        if($player instanceof Player){
        	$this->safeKick($player, "You have been banned");
        }
    }
	
	/**
	 * Get the Auction by its ID.
	 *
	 * @param string $ID
	 * @return Auction|bool false if not found.
	 */
	public function getAuction(string $ID) : ?Auction{
		return $this->auctions[$ID] ?? false;
	}
	
	/**
	 * Get the ID of an Auction.
	 *
	 * @param Auction $auction
	 * @return string|bool false if not found.
	 */
	public function getAuctionID(Auction $auction){
		foreach($this->auctions as $ID => $auc){
			if($auc->getSeller() === $auction->getSeller() && $auc->getPublishTime() === $auction->getPublishTime()){
				return $ID;
			}
		}
		return false;
	}
	
	/**
	 * Parses time string to seconds.
	 *
	 * @param string $time Format: Hours:Minutes:Seconds
	 * @return int
	 */
	public function parseTime(string $time) : int{
		if(count(explode(":", $time)) !== 3){
			return -1;
		}
		list($hr, $min, $sec) = explode(":", $time);
		foreach([$hr, $min, $sec] as $var){
			$$var = (int) round($var);
		}
		return (int) round($hr * 3600 + $min * 60 + $sec);
	}
	
	
	/**
	 * Returns the texture path for the Item given.
	 *
	 * @param Item $item
	 * @return string
	 */
	public function getTexturePath(Item $item) : string{
		foreach($this->textureMap as $type => $data){
			$path = "textures/" . $type . "/";
			foreach($data as $i => $texture_name){
				$id = explode(":", (string) $i)[0];
				$damage = explode(":", (string) $i)[1] ?? 0;
				if($id == $item->getId()){
					if($item instanceof Durable || (!($item instanceof Durable) && $item->getDamage() === 0)){
						return $path . $texture_name;
					}
					foreach($data as $ii => $ttexture_name){
						$iid = explode(":", (string) $ii)[0];
						$ddamage = explode(":", (string) $ii)[1] ?? 0;
						if($iid === $item->getId() && $ddamage === $item->getDamage()){
							return $path . $ttexture_name;
						}
					}
					return $path . $texture_name;
				}
			}
		}
		return "";
	}
	
	/**
 	 * Retrieve the account group in which an username is.
 	 * @param string $username
 	 * @return AccountGroup|null
 	 */
 	public function getAccountGroup(string $username) : ?AccountGroup{
 		$username = $username;
 		foreach($this->accountGroups as $accountGroup){
 			if($accountGroup->contains($username)){
 				return $accountGroup;
 			}
 		}
 		return null;
 	}
 	
 	/**
 	 * Create an account group starting off two usernames.
 	 * @param string $username1
 	 * @param string $username2
 	 * @return bool
 	 */
 	public function createAccountGroup(string $username1, string $username2) : bool{
 		if($this->getAccountGroup($username1) || $this->getAccountGroup($username2)){
 			return false;
 		}
 		$this->accountGroups[] = new AccountGroup($username1, $username2);
 		return true;
 	}
 	
 	/**
 	 * Remove the specified account group.
 	 * @param AccountGroup $group
 	 * @return bool
 	 */
 	public function removeAccountGroup(AccountGroup $group) : bool{
 		$usernames = $group->getUsernames();
 		$rand_username = $usernames[array_rand($usernames)];
 		foreach($this->accountGroups as $key => $accountGroup){
 			if($accountGroup->contains($rand_username)){
 				unset($this->accountGroups[$key]);
 				return true;
 			}
 		}
 		return false;
 	}
	
	
	/**
	 * Returns the spawner name by entity ID.
	 *
	 * @param string $entityId
	 * @return null|string
	 */
	public function getSpawnerName(int $entityId) : ?string{
		foreach($this->spawners as $spawnerName => $data){
			$id = $this->getEntityId($spawnerName);
			if($id === $entityId){
				return $spawnerName;
			}
		}
		return null;
	}
	
	/**
	 * Returns entity ID by name.
	 *
	 * @param string $entityName
	 * @return null|int
	 */
	public function getEntityId(string $entityName) : ?int{
		return @constant("\\pocketmine\\entity\\EntityIds::" . mb_strtoupper(str_replace(" ", "_", $entityName)));
	}
	
	/**
	 * @param Player $player
	 * @return float
	 */
	public function getHeadHuntingXp(Player $player) : float{
		return (float) $this->getEntry($player, self::ENTRY_HEADHUNTING);
	}
	
	/**
	 * @param Player $player
	 * @param float $xp
	 * @param int &$level New level after adding XP
	 *
	 * @return bool Whether the player leveled up or not
	 */
	public function addHeadHuntingXp(Player $player, float $xp, ?int &$level) : bool{
		$totalXp = $this->getHeadHuntingXp($player);
		$oldLevel = $this->getHeadHuntingLevel($player);
		if($xp <= 0){
			return false;
		}
		$totalXp += $xp;
		$this->registerEntry($player, self::ENTRY_HEADHUNTING, $totalXp);
		$level = $this->getHeadHuntingLevel($player);
		return $level > $oldLevel;
	}
	
	/**
	 * Get the player's headhunting level.
	 *
	 * @param Player $player
	 *
	 * @return int
	 */
	public function getHeadHuntingLevel(Player $player) : int{
		$xpIncrease = 50;
		$baseXp = 1000;
		$level = 1;
		
		$remainderXp = $this->getHeadHuntingXp($player);
		while(($remainderXp - ($sub =  ($baseXp + ($xpIncrease * ($level - 1)))   )) >= 0){
			$level++;
			$remainderXp -= $sub;
		}
		return $level;
	}
	
	/**
	 * @param Player $player
	 *
	 * @return int Numeric index
	 */
	public function getAffordableSpawner(Player $player) : int{
		return min(count($this->spawners) - 1, (int) floor($this->getHeadHuntingLevel($player) / 5));
	}
	
	/**
	 * @param Player $player
	 * @param string $spawner
	 *
	 * @return bool 
	 */
	public function canAffordSpawner(Player $player, string $spawner) : bool{
		return array_search($spawner, array_keys($this->spawners)) <= $this->getAffordableSpawner($player);
	}
	
	
	/**
	 * @param int $boosters Bitwise flags
	 * @return array
	 */
	public function parseBoosters(int $boosters) : array{
		$arr = [];
		if($boosters & MobSpawner::BOOSTER_X4_RATE){
			$arr[] = "x4 Spawn Rate";
		}
		if($boosters & MobSpawner::BOOSTER_X3_RATE){
			$arr[] = "x3 Spawn Rate";
		}
		if($boosters & MobSpawner::BOOSTER_X2_RATE){
			$arr[] = "x2 Spawn Rate";
		}
		
		if($boosters & MobSpawner::BOOSTER_X4_COUNT){
			$arr[] = "x4 Spawn Count";
		}
		if($boosters & MobSpawner::BOOSTER_X3_COUNT){
			$arr[] = "x3 Spawn Count";
		}
		if($boosters & MobSpawner::BOOSTER_X2_COUNT){
			$arr[] = "x2 Spawn Count";
		}
		return $arr;
	}
	
	/**
	 * @param string $name
	 * @param int $spawnerPrice
	 * @return int
	 */
	public function getBoosterPrice(string $name, int $spawnerPrice) : int{
		if(strpos($name, "2") !== false){
			return (int) round($spawnerPrice / 3); //33% spawner price
		}
		if(strpos($name, "3") !== false){
			return (int) round($spawnerPrice / 2); //50% spawner price
		}
		if(strpos($name, "4") !== false){
			return (int) round($spawnerPrice); //100% spawner price
		}
		throw new \InvalidArgumentException("Argument 1 passed to " . __METHOD__ . " must be a valid booster constant from " . MobSpawner::class);
	}
	
	/**
	 * Returns the maintenance reason.
	 *
	 * @return string
	 */
	public function getMaintenanceReason() : string{
		return $this->maintenanceCnf->get("reason", LangManager::translate("maintenance-generic"));
	}
	
	/**
	 * @param string|Player $player Case sensitive if string
	 * @param string|Player $player2 Case sensitive if string
	 *
	 * @return int 0 none, 1 $player by $player2, 2 $player2 by $player1
	 */
	public function isIgnored($player, $player2) : int{
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
		if($player2 instanceof IPlayer){
			$player2 = $player2->getName();
		}
		if(in_array(mb_strtolower($player), $this->ignore->get($player2, []))){
		    return 1;
		}
		if(in_array(mb_strtolower($player2), $this->ignore->get($player, []))){
		    return 2;
		}
		return 0;
    }
    
	/**
	 * Substitutes all Mill. and Bill. units in the string with a program-identifiable (stringy) float.
	 *
	 * @param string &$str
	 */
	public function interpretUnits(string &$str) : void{
		preg_match_all("/\d+(\.\d*)?|\.\d+M/", $str, $mill, PREG_PATTERN_ORDER);
		foreach($mill[0] as $number){
			$str = preg_replace("/" . $number . "M/", $number * 1000000, $str);
		}
		preg_match_all("/\d+(\.\d*)?|\.\d+B/", $str, $bill, PREG_PATTERN_ORDER);
		foreach($bill[0] as $number){
			$str = preg_replace("/" . $number . "B/", $number * 1000000000, $str);
		}
	}
	
	/**
	 * Returns the player's kill death ratio.
	 *
	 * @param Player|string $player
	 *
	 * @return string
	 */
	public function getKDR($player) : string{
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
		$kills = $this->getEntry($player, self::ENTRY_KILLS);
		$deaths = $this->getEntry($player, self::ENTRY_DEATHS);
		$kdr = sprintf("%0.2f", @($kills / $deaths)); //@ to dismiss INF & NaN
		return !in_array($kdr, ["INF", "NaN"]) ? $kdr : "0.00";
	}
	
	/**
	 * Get the Greenwich Meridian Time (GMT) converted to given timezone.
	 *
	 * @param string $timezone 
	 * @param int $time
	 * @return int
	 */
	public function getTimeOnTimezone(string $timezone, int $time = null) : int{
		if($time === null){
			$time = time(); //Now
		}
		//\DateTime class only takes a string as first argument, so we'll hack it
		$date = date("Y-m-d h:i:s", $time);
		
		$date = new \DateTime(strtotime($date), new \DateTimeZone($time));
		return strtotime($date->format("Y-m-d H:i:s"));
	}
	
	/**
	 * Get the time ellapsed.
	 *
	 * @param int $pastTime
	 *
	 * @return array<int, int int>|int
	 */
	public function getTimeEllapsed(int $pastTime) : array{
		return $this->getTimeLeft(time() + (time() - $pastTime));
	}
	
	/**
	 * Returns hours, minutes and seconds left for that time.
	 *
	 * @param int $time
	 * @param string $unityWanted
	 * @return array<int, int, int>
	 */
	public function getTimeLeft(int $time, string $unityWanted = "") : array{
		$diff = $time - time();
		if($diff < 1){
			return [0, 0, 0, 0];
		}
		
		$min = 60;
		$hr = 3600;
		$day = 86400;
		
		if($unityWanted !== ""){
			switch($unityWanted){
				case "seconds":
				case "s":
				    return $diff;
				    break;
				case "minutes":
				case "m":
				    return intval(round($diff / $min));
				    break;
				case "hours":
				case "h":
				    return intval(round($diff / $hr));
				    break;
				case "days":
				case "d":
				    return intval(round($diff / $day));
				    break;
				default:
					return $diff;
			}
		}
		$days = floor($diff / $day);
		$remainder = $diff - ($days * $day);
		$hours = floor($remainder / $hr);
		$remainder = $diff - ($hours * $hr);
		$minutes = floor($remainder / $min);
		$remainder = $remainder - ($minutes * $min);
		return [
			$days, $hours, $minutes, $remainder
		];
	}
	
	/**
	 * @api
	 *
	 * This is used for formatting the self::getTimeLeft() | self::getTimeEllapsed() output
	 * into a human readable time string.
	 * @param string $mainColor
	 * @param string $digitColors
	 * @return string
	 */
	public function formatTime(array $time, string $mainColor = TextFormat::RESET, string $digitColor = TextFormat::RESET) : string{
		if(count($time) < 4){
			throw new \InvalidArgumentException("Argument 1 must be an array of 4 elements: days, hours, minutes and seconds");
		}
		if($time[0] >= 1){
			$format = $digitColor . $time[0] . "d" . $mainColor;
			if($time[1] > 0){
				$format .= " and " . $digitColor . $time[1] . "h" . $mainColor;
			}
		}elseif($time[1] >= 1){
			$format = $digitColor . $time[1] . "h" . $mainColor;
			if($time[2] > 0){
				$format .= " and " . $digitColor . $time[2] . "m" . $mainColor;
			}
		}elseif($time[2] >= 1){
			$format = $digitColor . $time[2] . "m" . $mainColor;
			if($time[3] > 0){
				$format .= " and " . $digitColor . $time[3] . "s" . $mainColor;
			};
		}else{
		    $format = $digitColor . $time[3] . "s" . $mainColor;
		}
		return $format;
	}
	
	/**
	 * @aram Player $player
	 * @return Vehicle|null
	 */
	public function getVehicle(Player $player) : ?Vehicle{
		return $this->inVehicle[$player->getRawUniqueId()] ?? null;
	}
    
    /**
     * @param Player $player
     * @param int $windowId
     */
    public function closeWindow(Player $player, int $windowId) : void{
    	$pk = new ContainerClosePacket();
	    $pk->windowId = $windowId;
    	$player->dataPacket($pk);
    }
    
    /**
     * Wrapper function to kick a player in the next tick.
     *
     * @param Player $player
     * @param string $kickMessage
     */
    public function safeKick(Player $player, string $kickMessage) : void{
    	$this->scheduleDelayedCallbackTask(function(Player $player, string $kickMessage){
    	    if($player->isOnline()){
    	    	$player->kick($kickMessage, false);
    		}
    	}, 2, $player, $kickMessage);
    }
	
	/**
	 * Creates an AxisAlignedBB starting off two vectors.
	 *
	 * @param Vector3 $v1
	 * @param Vector3 $v2
	 *
	 * @return AxisAlignedBB
	 */
	public static function createBB(Vector3 $v1, Vector3 $v2) : AxisAlignedBB{
		$minX = min($v1->getX(), $v2->getZ());
		$minY = min($v1->getY(), $v2->getY());
		$minZ = min($v1->getZ(), $v2->getZ());
		$maxX = max($v1->getX(), $v2->getX());
		$maxY = max($v1->getY(), $v2->getY());
		$maxZ = max($v1->getZ(), $v2->getZ());
		return new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
	}
	
	/**
	 * @param float $yaw
	 *
	 * @return float
	 */
	public function getDirection(float $yaw) : float{
		$rotation = $yaw % 360;
		if($rotation < 0){
			$rotation += 360;
		}
		if((0 <= $rotation && $rotation < 22.5) || (337.5 <= $rotation && $rotation < 360)){
			return 180;
		}elseif(22.5 <= $rotation && $rotation < 67.5){
			return 225;
		}elseif(67.5 <= $rotation && $rotation < 112.5){
			return 270;
		}elseif(112.5 <= $rotation && $rotation < 157.5){
			return 315;
		}elseif(157.5 <= $rotation && $rotation < 202.5){
			return 0;
		}elseif(202.5 <= $rotation && $rotation < 247.5){
			return 45;
		}elseif(247.5 <= $rotation && $rotation < 292.5){
			return 90;
		}elseif(292.5 <= $rotation && $rotation < 337.5){
			return 135;
		}else{
			return 0;
		}
	}
	
	/**
	 * @param callable $callable
	 * @param int $delay
	 * @param mixed ...$params Passed to the callable
	 */
	public function scheduleDelayedCallbackTask(callable $callable, int $delay = 11, ...$params) : void{
		$this->getScheduler()->scheduleDelayedTask(new class($callable, $params) extends Task{
			/** @var callable */
			private $callable;
			/** @var array */
			private $params;
			
			public function __construct(callable $callable, $params = []){
				$this->callable = $callable;
				$this->params = $params;
			}
			
			public function onRun(int $currentTick) : void{
				call_user_func_array($this->callable, $this->params);
			}
		}, $delay);
	}
	
	/**
	 * Accessed by \CustomEnchants\CustomListener
	 *
	 * @return Item a random tag
	 */
	public function getRandomTag() : Item{
	    $tag = $this->tagList[array_rand($this->tagList)];
	    
		$item = ItemFactory::get(Item::NAMETAG, 0, 1);
		$item->setCustomName(TextFormat::colorize("&r&l&a" . $tag . " Tag"));
		$item->setLore([
		    "",
		    TextFormat::colorize("&r&7When used, unlocks the &b" . $tag . " &7tag"),
		    TextFormat::colorize("&r&7You can then change to that tag from &e/tag")
		]);
		$nbt = $item->getNamedTag();
		$nbt->setString("TagName", $tag);
		$nbt->setInt("RandToNotStack", mt_rand(-2147483648, 2147483647));
		$item->setNamedTag($nbt);
		return $item;
	}
	
	/**
	 * @return bool true if player can add $slots to their inventory
	 */
	public function testSlot(Player $player, int $slots = 1) : bool{
		$slots_used = $player->getInventory()->getContents(false);
		if(count($slots_used) + $slots > $player->getInventory()->getSize()){
			LangManager::send("inventory-nospace", $player);
			return false;
		}
		return true;
	}
	
	/**
	 * @return int server load average
	 */
	public function getServerLoadAverage() : int{
		return intval(ceil(array_sum($this->serverLoad) / count($this->serverLoad)));
	}
	
	/**
	 * @return Config
	 */
	public function getMaintenanceConfig() : Config{
		return $this->maintenanceCnf;
	}
	
	/**
	 * @return SurveyManager|null
	 */
	public function getSurveyManager() : ?SurveyManager{
		return $this->surveyManager;
	}
	
	/**
	 * Finds a unocuppied arena.
	 * @param int $duelType
	 * @return DuelArena|null
	 */
	public function findFreeArena(int $duelType) : ?DuelArena{
		$freeArenas = [];
		foreach($this->duelArenas as $arena){
			if($arena->gameStatus !== DuelArena::GAME_STATUS_INACTIVE || !in_array($duelType, $arena->getDuelTypes()) || count($arena->getPlaying()) > 0){
				continue;
			}
			$freeArenas[] = $arena;
		}
		if(empty($freeArenas)){
			return null;
		}
		return $freeArenas[array_rand($freeArenas)];
	}
	
	/**
	 * Returns the duel name by its id. Also used to check validity of a duel gamemode.
	 * @param mixed $duelType
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getDuelName($duelType) : string{
		$constants = (new \ReflectionClass($this))->getConstants();
		foreach($constants as $n => $value){
			if($value === $duelType && strpos($n, "DUEL_TYPE_") !== false){
				return $name = (implode(" ", array_map(function($w){
					return ucfirst($w);
			    }, array_slice(explode("_", mb_strtolower($n)), 2)))) . " Duel";
			}
		}
		throw new \InvalidArgumentException("Could not find a local constant for duel type " . $duelType);
	}
	
	/**
	 * Joins the player to the duel queue.
	 * @param Player $player
	 * @param int $type
	 * @return bool
	 */
	public function joinQueue(Player $player, int $type) : bool{
		if(!$this->quitQueue($player) && ($duelName = $this->getDuelName($type))){
			$this->duelQueue[$type][] = $player->getName();
			LangManager::send("duel-queued", $player, $duelName);
			if($type === self::DUEL_TYPE_SPLEEF){
				$player->teleport($this->getServer()->getLevelByName("duels")->getSpawnLocation());
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Removes the player from the duel queue.
	 * @param Player $player
	 * @return bool
	 */
	public function quitQueue(Player $player) : bool{
		foreach($this->duelQueue as $type => $players){
			if(in_array($player->getName(), $players)){
				unset($this->duelQueue[$type][array_search($player->getName(), $players)]);
				LangManager::send("duel-queuequit", $player);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Checks if the player is in the duel queue.
	 * @param Player $player
	 * @return bool
	 */
	public function inQueue(Player $player) : bool{
		foreach($this->duelQueue as $type => $players){
			if(in_array($player->getName(), $players)){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns the duel in which the player is (basically the arena)
	 * @return DuelArena|null
	 */
	public function getPlayerDuel(Player $player) : ?DuelArena{
		foreach($this->duelArenas as $arena){
			if($arena->isPlaying($player) !== false){
			    return $arena;
			}
		}
		return null;
	}
	
	/**
	 * Returns the arena of the game the player is spectating, or null if not spectating.
	 * @param DuelArena|null
	 */
	public function getPlayerSpectating(Player $player) : ?DuelArena{
		foreach($this->duelArenas as $arena){
			if($arena->isSpectating($player) !== false){
				return $arena;
			}
		}
		return null;
	}
	
	/**
	 * @param Skin $skin
	 * @param string $player
	 * @return Item skinned player head
	 */
	public function getPlayerHead(Skin $skin, string $player) : Item{
		$item = ItemFactory::get(Item::MOB_HEAD, 3);
		$item->setCustomBlockData(new CompoundTag("Skin", [
		    new StringTag("Name", $player),
		    new ByteArrayTag("Data", $skin->getSkinData())
		]));
		$item->setCustomName(TextFormat::colorize("&r&6&l" . $player . " Head"));
		return $item;
	}
	
	
	/**
	 * @param float $deg
	 * @return float
	 */
	public function clampDegrees(float $deg) : float{
		$deg %= 360;
		if($deg < 0){
			$deg += 360;
		}
		return $deg;
	}
	
	/**
	 * Determine bearing in degrees.
	 * @param float $deg
	 * @return string
	 */
	public function getCompassDirection(float $deg) : string{
		$deg = $this->clampDegrees($deg);
		if(22.5 <= $deg and $deg < 67.5){
			return "Northwest";
		}elseif(67.5 <= $deg and $deg < 112.5){
			return "North";
		}elseif(112.5 <= $deg and $deg < 157.5){
			return "Northeast";
		}elseif(157.5 <= $deg and $deg < 202.5){
			return "East";
		}elseif(202.5 <= $deg and $deg < 247.5){
			return "Southeast";
		}elseif(247.5 <= $deg and $deg < 292.5){
			return "South";
		}elseif(292.5 <= $deg and $deg < 337.5){
			return "Southwest";
		}else{
			return "West";
		}
	}

	/**
	 * Resets a player entry.
	 * @param Player|string $player
	 * @param int $entry {@see self::ENTRY_*} constants
	 */
	public function resetEntry($player, int $entry) : void{
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
		$stats = $this->stats->get(mb_strtolower($player));
		unset($stats[$entry]);
		$this->stats->set(mb_strtolower($player), $stats);
	}
	
	/**
	 * Registers a player entry. Unless a custom $value is set, the current entry will be incremented by +1
	 * @param Player|string $player
	 * @param int $entry {@see self::ENTRY_*} constants
	 * @param mixed $value
	 */
	public function registerEntry($player, int $entry, $value = null) : void{
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
		$entry = mb_strtolower($player) . "." . $entry;
		$c = $this->stats->getNested($entry, 0);
		if($entry === self::ENTRY_KILLS){
			$this->questManager->getQuest("serial_killer")->progress($player, 1);
			$this->questManager->getQuest("kill_savage")->progress($player, 1);
		}
		if($entry === self::ENTRY_DEATHS){
			$this->questManager->getQuest("kill_savage")->progress($player, -1);
		}
		if($entry === self::ENTRY_BLOCKS_BROKEN){
		    $this->questManager->getQuest("maniac_miner")->progress($player, 1);
		}
		if($entry === self::ENTRY_BLOCKS_PLACED){
		    $this->questManager->getQuest("builder")->progress($player, 1);
		}
		$this->stats->setNested($entry, $value !== null ? $value : ($c + 1));
	}
	
	/**
	 * @deprecated
	 * @see self::getPlayerEntry
	 * @param Player|string $player
	 * @param int $entry {@see self::ENTRY_*} constants
	 * @return mixed
	 */
	public function getEntry($player, int $entry){
		if($player instanceof IPlayer){
			$player = $player->getName();
		}
	    return $this->stats->getNested(mb_strtolower($player) . "." . $entry, 0);
	}
	
	public function getPlayerEntry($player, int $entry){
		return $this->getEntry($player, $entry);
	}
	
	/**
	 * Returns the player money.
	 *
	 * @param string|IPlayer $player
	 * @return int
	 */
	public function myMoney($player) : int{
		return (int) $this->getEntry($player, self::ENTRY_MONEY);
	}
	
	/**
	 * Subtracts $money from player balance.
	 *
	 * @param string|IPlayer $player
	 * @param string|int|float $money
	 *
	 * @return bool
	 */
	public function reduceMoney($player, $money) : bool{
		return $this->addMoney($player, -$money);
	}
	
	/**
	 * Adds $money to player balance.
	 *
	 * @param string|IPlayer $player
	 * @param string|int|float $money
	 *
	 * @return bool
	 */
	public function addMoney($player, $money) : bool{
		$balance = $this->myMoney($player);
		if($money < 0){
			$result = bcsub((string) $balance, (string) abs($money));
		}else{
			$result = bcadd((string) $balance, (string) $money);
		}
		if($result < 0 || $result > PHP_INT_MAX){
			return false;
		}
		$this->registerEntry($player, self::ENTRY_MONEY, $result);
		return true;
	}
	
	/**
	 * Returns the player tokens.
	 *
	 * @param IPlayer $player
	 * @return int
	 */
	public function getTokens(IPlayer $player) : int{ 
	    return $this->getEntry($player, self::ENTRY_TOKENS);
	}
	
	/**
	 * Adds $tokens tokens to player balance.
	 *
	 * @param IPlayer $player
	 * @param int $tokens
	 */
	public function addTokens(IPlayer $player, int $tokens) : void{
		$this->registerEntry($player, self::ENTRY_TOKENS, $this->getTokens($player) + $tokens);
		if($player->isOnline()){
			LangManager::translate("tokens-given", $player, $tokens);
		}
	}
	
	/**
	 * Subtracts $tokens tokens from player balance.
	 *
	 * @param IPlayer $player
	 * @param int $tokens
	 * @return bool
	 */
	public function subtractTokens(IPlayer $player, int $tokens) : bool{
		$result = $this->getTokens($player) - $tokens;
		if($result < 0){
			return false;
		}
		$this->registerEntry($player, self::ENTRY_TOKENS, $result);
		if($player->isOnline()){
			LangManager::translate("tokens-taken", $player, $tokens);
		}
		return true;
	}
	
	/**
	 * @param Player $player
	 * @param bool $toggle
	 * @return bool New status
	 */
	public function isAFK(Player $player, bool $toggle = false) : bool{
		$afkTag = TextFormat::colorize("&e[AFK] &r");
		if(strpos($player->getDisplayName(), "[AFK]") === false){
			if($toggle){
				$player->setDisplayName($afkTag . $player->getDisplayName());
			}
			return false;
		}
		if($toggle){
			$player->setDisplayName(str_replace($afkTag, "", $player->getDisplayName()));
		}
		return true;
	}
	
	/**
	 * Wrapper function to check if player has premium rank.
	 * Without the $strict parameter, Staff accounts will be accounted as premium.
	 * NOTE: Operators always return `true`
	 *
	 * @param IPlayer $player
	 * @param bool $strict Without not account for Staff accounts
	 *
	 * @return bool
	 */
	public function isVip(IPlayer $player, bool $strict = false) : bool{
		if($strict){
			return $this->hasPlayerRank($player, "Vip");
		}else{
			return $this->rankCompare($player, self::VIP_RANK) >= 0 || $player->isOp();
		}
	}
	
	/**
	 * @internal
	 * NOTE: Operators always have all ranks.
	 *
	 * @param IPlayer $player
	 * @param string|Group $group
	 * @return bool
	 */
	public function hasPlayerRank(IPlayer $player, $group) : bool{
		if(!is_string($group)){
			$group = $group->getName();
		}
		if($this->rankCompare($group, self::STAFF_RANK) >= 0){
			//I'd throw an exception but some rank checks are hardcoded
			return $player->isOp();
		}
		$groups = $this->permissionManager->getPlayerGroups($player);
		foreach($groups as $aGroup){
			if($this->rankCompare($aGroup, $group) >= 0 && $this->rankCompare($aGroup, self::STAFF_RANK) < 0){
				return true;
			}
		}
		return $player->isOp();
	}
	
	/**
	 * Wrapper function to check if the player is a staff.
	 *
	 * @param IPlayer $player
	 * @return bool
	 */
	public function isStaff(IPlayer $player) : bool{
		return $this->rankCompare($player, $this->getConfig()->get("staff-rank")) >= 0 || $player->isOp();
	}
	
	public function getStaffRanks() : array{
		return array_slice($this->ranks, array_search($this->getConfig()->get("staff-rank"), $this->ranks));
	}
	
	/**
	 * @param Player|string $rank1 The Player object, the player's name or the group name
	 * @param Player|string $rank2 The Player object, the player's name or the group name
	 *
	 * @return int < 0 if $rank1 is lower than $rank2; > 1 if $rank2 is greater than $rank1 and 0 if they are equal
	 */
	public function rankCompare($rank1, $rank2) : int{
		if(is_string($rank1) && array_search($rank1, $this->ranks) === false){
			$rank1 = $this->getServer()->getOfflinePlayer($rank1);
		}
		if(is_string($rank2) && array_search($rank2, $this->ranks) === false){
			$rank2 = $this->getServer()->getOfflinePlayer($rank2);
		}
		if($rank1 instanceof IPlayer){
			$rank1 = $this->permissionManager->getPlayerGroup($rank1)->getName();
		}
		if($rank2 instanceof IPlayer){
			$rank2 = $this->permissionManager->getPlayerGroup($rank2)->getName();
		}
		$index1 = array_search($rank1, $this->ranks);
		$index2 = array_search($rank2, $this->ranks);
		if($index1 === false || $index2 === false){
		   return -2;
		}
		if($index1 < $index2){
			return -1;
		}
		if($index1 > $index2){
			return 1;
		}
		return 0;
	}
	
	/**
	 * Wrapper function to get a plugin's instance.
	 * @param string $name
	 * @return Plugin|null
	 */
	public function getPlugin(string $name) : ?Plugin{
		return $this->getServer()->getPluginManager()->getPlugin($name);
	}
	
	/**
	 * @param array $arr
	 * @param int $numElements
	 * @param mixed $fill
	 */
	public function fillArray(array &$arr, int $numElements, $fill){
		if(count($arr) < $numElements){
			$fill = array_fill(count($arr), $numElements - count($arr), $fill);
			$arr = array_merge($arr, $fill);
		}
	}
	
	/**
	 * @param int $limit
	 * @return array
	 */
	public function getTopFactions($limit = 5) : array{
		$params = [];
		
		$fp = $this->getPlugin("FactionsPro");
		foreach($fp->getTopFactions($limit) as $fac){
			$faction = $fac[0];
			$str = $fac[1];
			$players = $fp->getNumberOfPlayers($faction);
			
			$params[] = $faction;
			$params[] = $str;
			$params[] = $players;
		}
		
		$this->fillArray($params, $limit * 3, "");
		return $params;
	}
	
	/**
	 * @param int $limit
	 * @return array
	 */
	public function getTopOnline(int $limit = 5) : array{
		$params = [];
		
		$allsessions = $this->timeonline->getAll();
		$online = [];
		foreach($allsessions as $player => $sessions){
			$online[$player] = 0;
			foreach($sessions as $session){
				foreach($session as $startTime => $seconds){
					$online[$player] += $seconds;
				}
			}
		}
		arsort($online); //Sort in descending order
		$i = 1;
		$text = "";
		foreach($online as $player => $secs){
			if($i > $limit) break;
			$p = $this->getServer()->getPlayerExact($player);
			if($p !== null){
				$secs = $this->getTimeOnline($player, true);
			}
			$time = $this->formatTime($this->getTimeEllapsed(time() - $secs), TextFormat::GOLD, TextFormat::GOLD);
			
			$params[] = $player;
			$params[] = $time;
		}
		
		$this->fillArray($params, $limit * 2, "");
		return $params;
	}
	
	/**
	 * @return array
	 */
	public function getTopVoters() : array{ 
	    $params = [];
	    foreach($this->voters as $voter){
	    	$player = $voter["nickname"] ?? "";
	    	$votes = $voter["votes"] ?? 0;
	    		
	    	$params[] = $player;
	    	$params[] = $votes;
	    }
	    
	    $this->fillArray($params, count($this->voters) * 2, "");
		return $params;
	}
	
	/**
	 * @param int $limit
	 * @return array
	 */
	public function getTopKdr(int $limit = 5) : array{ 
	    $params = [];
	    
	    $stats = $this->stats->getAll();
	    $players = array_keys($stats);
	    $kdrs = [];
	    foreach($stats as $player => $stats){
	    	$kdrs[$player] = $this->getKDR($player);
	    }
	    arsort($kdrs); //Works perfectly, sadly natsort() will sort oppositely and won't work with array_reverse()
	    $i = 0;
	    foreach($kdrs as $player => $kdr){
	    	if(++$i > $limit) break;
	    	$kills = $this->getEntry($player, self::ENTRY_KILLS);
	    	$deaths = $this->getEntry($player, self::ENTRY_DEATHS);
	    	
	    	$params[] = $player;
	    	$params[] = $kdr;
	    	$params[] = $kills;
	    	$params[] = $deaths;
		}
		
		$this->fillArray($params, $limit * 4, "");
		return $params;
	}
	
	/**
	 * @param Player $player
	 * @return int
	 */
	public function getLastSkyjumpSetpoint(Player $player) : int{
		$data = $this->skyjump->get($player->getName(), []);
		$keys = array_keys($data);
		$lastSetpoint = !empty($data) ? end($keys) : 0;
		return $lastSetpoint;
	}
	
	/**
	 * @internal
	 * @param Player $player
	 * @param string $title
	 * @param string $body
	 */
	public function submitIssue(?Player $player, string $title, string $body) : void{
		$this->getServer()->getAsyncPool()->submitTask(new class($player, $title, $body) extends AsyncTask{
			private $player;
			/** @var string */
			private $title;
			/** @var string */
			private $body;
			/** @var string */
			private $username;
			/** @var string */
			private $password;
			
			public function __construct(?Player $player, string $title, string $body){
				$this->storeLocal([$player]);
				$this->title = $title;
				$this->body = $body;
				$github = Main::getInstance()->getConfig()->get("github");
				$this->endpoint = $github["issues-endpoint"];
				$this->repository = $github["repository"];
				[$this->username, $this->password] = explode("@", $github["credentials"]);
			}
			
			public function onRun(){
				$this->result = Internet::getURL(str_replace(["{username}", "{repository}"], [$this->username, $this->repository], $this->endpoint), 10, [
				    "User-Agent: " . $this->username,
				    "Content-Type: application/json",
				    "Authorization: token " . $this->password
				]);
			}
			public function onCompletion(Server $server){
				$reported = false;
				
				if($this->result !== false && is_array($response = json_decode($this->result, true))){
					foreach($response as $issue){
						if($issue["title"] === $this->title){
							$reported = true;
						}
					}
				}
				list($player) = $this->fetchLocal();
				if($reported){
					
					if($player !== null){
						Main::getInstance()->lastIssue[$player->getName()] = [-1, time()];
					}
				}else{
					/* */
					Main::getInstance()->getServer()->getAsyncPool()->submitTask(new class($player, $this->title, $this->body) extends BulkCurlTask{
						public function __construct(?Player $player, string $title, string $body){
							$github = Main::getInstance()->getConfig()->get("github");
							parent::__construct([
							    [
							        "page" => str_replace(["{username}", "{repository}"], [explode("@", $github["credentials"])[0], $github["repository"]], $github["issues-endpoint"]),
							        "extraOpts" => [
							            CURLOPT_HTTPHEADER => [
							                "User-Agent: kenygamer",
							                "Content-Type: application/json",
							                "Authorization: token " . explode("@", $github["credentials"])[1],
							            ],
							            CURLOPT_POST => true,
							            CURLOPT_POSTFIELDS => json_encode(["title" => $title, "body" => $body, "labels" => ["bug"]])
							        ]
							    ]
							], [$player]);
						}
						public function onCompletion(Server $server){
							$complexData = $this->fetchLocal();
							list($player) = $complexData;
							$result = $this->getResult()[0];
							if($result instanceof InternetException){
								$server->getLogger()->logException($result);
								return;
							}
							if(isset($result[0]) && is_array($response = json_decode($result[0], true)) && isset($response["number"])){
								$number = $response["number"];
								if($player !== null){
									Main::getInstance()->lastIssue[$player->getName()] = [$number, time()];
									if($player->isOnline()){
										$player->sendMessage(TextFormat::RED . "[Bug] #" . $response["number"]);
									}
								}
							}
						}
					});
				} /* */
			}
		});
	}
	
	/**
	 * Translate text using the Google Translate API.
	 *
	 * @param $sl Source language (ISO 639-1)
	 * @param $tl Target language (ISO 639-1)
	 * @param string $text
	 * @param string $sl Detected source language
	 * @return string
	 */
	public static function translate(string $sl = "auto", string $tl, string $text, ?string &$sl_ = "") : string{
		if(trim($sl) === "" || trim($tl) === "" || trim($text) === ""){
			return $text;
		}
		//Fix SSLv3 issue: failed to enable crypto.. (cannot use copy())
		$url = "https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" .$tl . "&hl=hl&q=". urlencode($text);
		$response = Internet::getURL($url);
		$res = json_decode(@$response, true);
		if(isset($res[0][0][0])){
			$sl_ = $res[2];
			$ret = "";
			foreach($res[0] as $arr){
				$ret .= $arr[0];
			}
			return $ret;
		}
		return $text;
	}
	
	/**
	 * Recursively copy all files of directory $src to $dest
	 *
	 *
	 * @param string $src
	 * @param string $dest
	 * @param bool $iterator
	 * @return bool
	 */
	public function recursiveCopy(string $src, string $dest, bool $iterator = true) : bool{
		$src = rtrim($src, DIRECTORY_SEPARATOR);
		$dest = rtrim($dest, DIRECTORY_SEPARATOR);
		
		@mkdir($dest, 0777);
		if($iterator){
			foreach($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item){
				$file = $iterator->getFileName();// =$item->getFileName()
				
				$dest_folder = str_replace($iterator->getFileName(), "", $iterator->getPathName()); //Keeps trailing slash
				
				if($item->isDir()){
					mkdir($dest_folder . $file, fileperms($src . DIRECTORY_SEPARATOR . $file));
				}else{
					copy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
				}
			}
			return true;
		}else{ //Without RecursiveDirectoryIterator
			if(!is_dir($src) || !is_dir($dst)){
				return false;
			}
			foreach($this->recursiveSearch($src) as $file){
				$path = str_replace($src, "", $file) . "/"; //Add trailing slash
				if(is_dir($file)){
					@mkdir($dst . $path, fileperms($file));
				}else{
					if(copy($file, $dst . $path)){
						@unlink($file);
					}
				}
			}
			return true;
		}
	}
	
	/**
	 * Updates access time and modification of a file.
	 *
	 * @param string $file
	 */
	public function touchFile(string $file) : void{
		$this->buildTree($file);
		touch($file);
	}
	
	/**
	 * Build all the walkthrough necessary to create the specified
	 * directory or file.
	 *
	 * @param string $file
	 * @param string $home
	 *
	 * @return string The tree created, without ending slash.
	 */
	public function buildTree(string $file, string $home = "") : string{
		$home = rtrim($home);
		$parts = explode(DIRECTORY_SEPARATOR, $file);
		$tree = "";
		while(count($parts) > 0){
			$part = array_shift($parts);
			if(trim($part) === ""){
				continue;
			}
			if(stripos($part, ".") !== false && count($parts) === 0){
				break;
			}
			$tree .= DIRECTORY_SEPARATOR . $part;
		}
		@mkdir($tree, 0777);
		return $tree;
	}
	
	/**
	 * Performs a recursive search on the given directory, skipping current directory (.) and parent directory (..).
	 *
	 * @param string $dir
	 * @param array $results
	 * @return array
	 *
	 */
	public function recursiveSearch(string $dir, array &$results = []) : array{
		$dir = rtrim($dir, "/");
    	foreach(\scandir($dir) as $key => $value){
        	$path = \realpath($dir . DIRECTORY_SEPARATOR . $value);
        	if(!\is_dir($path)){
            	$results[] = $path;
        	}elseif($value !==  "." && $value !== ".."){
            	$this->recursiveSearch($path, $results);
				$results[] = $path;
			}
        }
		return $results;
    }
	
	/**
	 * @param Player $player
	 * @return int
	 */
	public function getFishingExp(Player $player): int{
		return (int) $this->getPlayerEntry($player, self::ENTRY_FISHING_EXPERIENCE);
	}
	
	/**
	 * @param int $exp
	 * @param Player $player
	 */
	public function addFishingExp(int $exp, Player $player) : void{
		$previousLevel = $this->getFishingLevel($player);
		$currentExp = $this->getFishingExp($player);
		$this->setPlayerEntry($player, self::ENTRY_FISHING_EXPERIENCE, $currentExp = $exp);
		if ($previousLevel != $this->getFishingLevel($player)){
			$player->sendMessage("fishing-levelup");
			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_SOUND_TOTEM;
			$pk->position = $player->add(0, $player->eyeHeight, 0);
			$pk->data = 0;
			$player->dataPacket($pk);
		}else{
			$this->sendFishingRemainingPopup($player);
		}
	}
	
	/**
	 * @param Player $player
	 * @return int
	 */
	public function getFishingLevel(Player $player) : int{
		$currentExp = $this->getFishingExp($player);
		$scale = 2;
		$level = (int) (pow($currentExp / 100, 1 / $scale)) + 1;
		if ($level > 10){
			$level = 10;
		}
		return $level;
	}
	
	/**
	 * @todo
	 * @param Player $player
	 */
	public function sendFishingRemainingPopup(Player $player) : void{
		$prevLvlExpNeeded = $this->getFishingLevelExpNeeded($this->getFishingLevel($player));
		$nextLvlExpNeeded = $this->getFishingLevelExpNeeded($this->getFishingLevel($player) + 1);
		$player->sendMessage($this->getFishingProgress($this->getFishingExp($player) - $prevLvlExpNeeded, $nextLvlExpNeeded - $prevLvlExpNeeded));
	}
	
	/**
	 * @param int $level
	 * @return int
	 */
	public function getFishingLevelExpNeeded(int $level) : int{
		return (100 * ($level) **2) - (200 * ($level)) + 100;
	}
	
	/**
	 * @todo Return an array instead.
	 *
	 * @param int $progress
	 * @param int $size
	 * @return string
	 */
	public function getFishingProgress(int $progress, int $size) : string{
		$divide = 27201030 + (7.578379 - 27201030) / (1 + ($size / 129623) ** 2.597146);
		$percentage = number_format(($progress / $size) * 100, 2);
		$progress = (int) ceil($progress / $divide);
		$size = (int) ceil($size / $divide);
		return TextFormat::GRAY . "[" . TextFormat::GREEN . str_repeat("|", $progress) .
			TextFormat::RED . str_repeat("|", $size - $progress) . TextFormat::GRAY . "] " .
			TextFormat::AQUA . "{$percentage} %%";
	}
	
	/**
	 * Safe logging to console from all contexts.
	 *
	 * @param string $psr
	 * @param string $msg
	 */
	public static function log(string $psr, string $msg) : void{
		$server = null;
		try{
			$server = Server::getInstance();
		}catch(\BadMethodCallException $e){
		}finally{
			if($server === null){
				echo "[" . ucfirst($psr) . "] " . $msg . PHP_EOL;
			}else{
				switch($psr){
					case "debug":
						$server->getLogger()->debug($msg);
						break;
					case "info":
						$server->getLogger()->info($msg);
						break;
					case "notice":
						$server->getLogger()->notice($msg);
						break;
					case "warning":
						$server->getLogger()->warning($msg);
						break;
					case "error":
						$server->getLogger()->error($msg);
						break;
					case "critical":
						$server->getLogger()->critical($msg);
						break;
					case "emergency":
						$server->getLogger()->emergency($msg);
						break;
					default:
						throw new \RuntimeException("Invalid PSR logging level " . $psr);
				}
			}
		}
	}
	
	/**
	 * Your best source for cryptographic randomness.
	 *
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public static function mt_rand(int $min = INT32_MIN, int $max = INT32_MAX) : int{
		if(class_exists(Core::class) && Core::$snapshot === ""){
			return \mt_rand($min, $max);
		}
		//The limits per request are 2 min seed count and 2,000,000,000 max seed count
		//With 100 API keys, the API use limit is of 17,361 bits per minute (25,000,000 total daily use)
			
		//2 - 500 range uses 900 ~ bits for 300 seeds, while
		//2 - 2000000 range use 3,000 ~ bits for 100 seeds
		
		static $MIN = 2;
		static $MAX = 2000000000;
		static $SEED_COUNT = 100;
		try{
			$server = Server::getInstance();
		}catch(\BadMethodCallException $e){
		}
		if($min < $MIN || $max > $MAX || $server === null || Main::getInstance() === null){
			$seed = null;
			goto generate;
		}
		if(empty(self::$mt_randCache) && Main::getInstance() !== null && file_exists(Main::getInstance()->getDataFolder() . "mt_rand.js")){
			$data = @json_decode(file_get_contents(Main::getInstance()->getDataFolder() . "mt_rand.js"), true);
			self::$mt_randCache = $data["seeds"] ?? null;
			self::$mt_randLast = $data["time"] ?? null;
			if(self::$mt_randCache === null || time() - $data["time"] > 60){
				self::$mt_randCache = [];
				@unlink(Main::getInstance()->getDataFolder() . "mt_rand.js");
			}
		}
		$seed = array_shift(self::$mt_randCache);
		if($seed === null && !Main::$mt_randWaiting && (self::$mt_randLast === null || time() - self::$mt_randLast > 60)){
			self::log("debug", $SEED_COUNT . " seeds are being generated");
			self::$mt_randWaiting = true;
			//Maybe make demand-based?
			$server->getAsyncPool()->submitTask(new RandomNumberTask($MIN, $MAX, $SEED_COUNT, function(int $resCode, $numbers) use($max){
				Main::$mt_randWaiting = false;
				if($resCode === RandomNumberTask::RESULT_SUCCESS){
					if(Main::getInstance() !== null){
						@unlink(Main::getInstance()->getDataFolder() . "mt_rand.json");
						file_put_contents(Main::getInstance()->getDataFolder() . "mt_rand.js", json_encode([
							"seeds" => $numbers,
							"time" => time()
						]));
					}
					Main::$mt_randCache = $numbers;
					Main::$mt_randLast = time();
				}
			}));
		}
		//Get footprint of variable
		//$mem = Process::getAdvancedMemoryUsage()[0] / 1024 / 1024; //Memory used in Megabytes
		generate:
		$random = new Random($seed === null ? (int) (microtime(true) * 1000) : $seed);
		if($seed !== null){
			//self::log("debug", count(self::$mt_randCache) . " seeds left");
		}
		return $random->nextRange($min, $max);
	}
	
	/**
	 * Get the enchant rarity. Helper for LegacyCore.
	 * 
	 * @param int $damage
	 * @return int Rarity
	 */
	public function getRarityByDamage(int $damage) : int{
		switch($damage){
			case 100:
	    		$rarity = Enchantment::RARITY_COMMON;
	    		break;
    		case 101:
    		    $rarity = Enchantment::RARITY_UNCOMMON;
    		    break;
    		case 102:
    		    $rarity = Enchantment::RARITY_RARE;
    		    break;
    		case 103:
    		    $rarity = Enchantment::RARITY_MYTHIC;
    		    break;
			default:
				$rarity = -1;
		}
		return $rarity;
	}
	
	/**
	 * Get the rarity integer. Helper for CustomEnchants.
	 *
	 * @param string $rarity
	 * @return int
	 */
	public function getRarityByName(string $rarity) : int{
		$rarity = mb_strtolower($rarity);
		switch($rarity){
			case "common":
				return Enchantment::RARITY_COMMON;
				break;
			case "uncommon":
				return Enchantment::RARITY_UNCOMMON;
				break;
			case "rare":
				return Enchantment::RARITY_RARE;
				break;
			case "mythic":
				return Enchantment::RARITY_MYTHIC;
				break;
			default:
				return -1;
		}
	}
	
	/**
	 * @param string|Player $player
	 * @param int $setting
	 * @param mixed $value
	 */
	public function setSetting($player, int $setting, $value) : void{
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = mb_strtolower($player);
		switch($setting){
			case self::SETTING_HUD:
			case self::SETTING_TIME:
			case self::SETTING_COMPASS:
			case self::SETTING_CHUNKBORDERS:
				if(!is_bool($value)){
					throw new \InvalidArgumentException("Setting " . self::SETTING_HUD . " only accepts a boolean value");
				}
				break;
			case self::SETTING_SCOREBOARD:
				if(!is_int($value) || $value < self::SETTING_SCOREBOARD_NONE || $value > self::SETTING_SCOREBOARD_FACTION){
					throw new \OutOfBoundsException("Setting " . self::SETTING_SCOREBOARD . " only accepts an int between range " . self::SETTING_SCOREBOARD_NONE . "-" . self::SETTING_SCOREBOARD_FACTION);
				}
				break;
			default:
				throw new \InvalidArgumentException("Unrecognized setting " . $setting);
		}
		$this->settings->setNested($player . "." . $setting, $value);
	}
	
	/**
	 * @param string|Player $player
	 * @param int $setting
	 * @return mixed
	 */
	public function getSetting($player, int $setting){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = mb_strtolower($player);
		$ret = $this->settings->getNested($player . "." . $setting, null);
		if($ret === null){ // fallback
			switch($setting){
				case self::SETTING_CHUNKBORDERS:
					$ret = false;
					break;
				case self::SETTING_HUD:
					$ret = false;
					break;
				case self::SETTING_TIME:
					$ret = false; //TODO
					break;
				case self::SETTING_SCOREBOARD:
					$ret = self::SETTING_SCOREBOARD_REGULAR;
					break;
				case self::SETTING_COMPASS:
					$ret = false;
					break;
			}
		}
		return $ret;
	}
	
}
