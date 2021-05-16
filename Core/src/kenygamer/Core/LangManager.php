<?php

declare(strict_types=1);

namespace kenygamer\Core;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

use GeoIp2\Database\Reader as GeoIpReader;

final class LangManager{
	/** @var string[] */
	private const PATTERN_TAGS = [
		self::PATTERN_RAINBOW => "rainbow", self::PATTERN_CHRISTMAS => "christmas"
	];
	public const PATTERN_RAINBOW = 0;
	public const PATTERN_CHRISTMAS = 1;
	
	/** @var string[] */
	private static $PATTERN_CHRISTMAS = [
		TextFormat::GREEN, TextFormat::RED, TextFormat::WHITE
	];
	/** @var string[] */
	private static $PATTERN_RAINBOW = [
	    /*TextFormat::DARK_RED, TextFormat::RED, TextFormat::GOLD, TextFormat::YELLOW, TextFormat::GREEN,
	    TextFormat::DARK_GREEN, TextFormat::AQUA, TextFormat::DARK_AQUA, TextFormat::DARK_BLUE, TextFormat::BLUE,
	    TextFormat::LIGHT_PURPLE, TextFormat::DARK_PURPLE*/
		TextFormat::RED, TextFormat::GOLD, TextFormat::YELLOW, TextFormat::GREEN, TextFormat::AQUA, TextFormat::LIGHT_PURPLE
	];
	
	private static $instance = null;
	
	/** @var array */
	private $lang;
	/** @var string[] */
	private $ipLangCache = [];
	/** @var GeoIpReader|null */
	private $geoIpReader = null;
	private const MAXMIND_DB_RESOURCE = "GeoLite2-Country.mmdb";
	
	//639-1
	private const LANG_ENGLISH = "en";
	private const LANG_SPANISH = "es";
	private const LANG_HINDI = "hi";
	private const LANG_PORTUGUESE = "pt";
	private const LANG_CHINESE = "zh";
	private const LANG_RUSSIAN = "ru";
	private const LANG_FRENCH = "fr";
	private const LANG_GERMAN = "de";
	private const LANG_ARABIC = "ar";
	private const LANG_JAPANESE = "ja";
	
	public const LANG_DEFAULT = self::LANG_ENGLISH; //Should not be changed from English, {@see self::getCountryCode()}
	
	public const ALL_ISO_CODES = [
	    self::LANG_ENGLISH, self::LANG_SPANISH, self::LANG_HINDI, self::LANG_PORTUGUESE, self::LANG_CHINESE,
	    self::LANG_RUSSIAN, self::LANG_FRENCH, self::LANG_GERMAN, self::LANG_ARABIC, self::LANG_JAPANESE
	];
	
	/** @var Config|null */
	public static $errLog = null;
	private const LOG_TYPE_NOT_CASTEABLE = 0;
	private const LOG_NO_ISO_MESSAGE = -1;
	
	private const DEFAULT_COLOR = TextFormat::WHITE;
	
	public static function getInstance() : ?self{
		return self::$instance;
	}
	
	public function __construct(){
		self::$instance = $this;
		$this->prepare();
	}
	
	/**
	 * @param int $type
	 * @param string $key
	 * @param string $iso
	 */
	private function logError(int $type, string $key, string $iso) : void{
		$exists = false;
		foreach(array_keys(self::$errLog->getAll()) as $error){
			/** @var string $ttype */
			list($ttype, $kkey, $iiso, $count) = explode(":", $error);
			if($ttype == $type && $kkey === $key && $iiso === $iso){
				$exists = true;
				break;
			}
		}
		self::$errLog->set($type . ":" . $key . ":" . $iso . ":" . ($exists ? (($count ?? 0) + 1) : 1));
	}
	
	private function prepare() : void{
		$plugin = Main::getInstance();
		self::$errLog = new Config($plugin->getServer()->getDataPath() . "lang_err.log", Config::ENUM);
		
		$plugin->saveResource(self::MAXMIND_DB_RESOURCE, true);
		
		if(class_exists("\\GeoIp2\\Database\\Reader")){
			if(file_exists($plugin->getDataFolder() . self::MAXMIND_DB_RESOURCE)){
				$this->geoIpReader = new GeoIpReader($plugin->getDataFolder() . self::MAXMIND_DB_RESOURCE);
			}else{
				Main::getInstance()->getLogger()->warning(get_class($this) . ": geoip is loaded but MaxMind db is not found. Multi-language support is disabled");
			}
		}else{
			Main::getInstance()->getLogger()->warning(get_class($this) . ": geoip lib not found. Multi-language support is disabled");
		}
		foreach(self::ALL_ISO_CODES as $iso){
			$plugin->saveResource("lang/" . $iso . ".ini", true);
			$this->lang[$iso] = (array) parse_ini_file($plugin->getDataFolder() . "lang/" . $iso . ".ini", false, INI_SCANNER_NORMAL);
			
			/*$file = $plugin->getDataFolder() . "en.txt";
			$result = "";
			$i = 0;
			$lines = file($file, FILE_IGNORE_NEW_LINES);*/
			foreach($this->lang["en"] as $key => $str){
				$key = str_replace("*", "", $key); //Fix for yes, no, null, true, false, on off, none and ?{}|&~!()^"
				$this->lang[$iso][$key = str_replace("*", "", $key)] = $str;
				/*$result .= $key . " = \"" . ($str = str_replace("\"", "'", $lines[$i++] ?? "")) . "\"" . PHP_EOL;
				if(trim($str) === ""){
					echo $key . " [ERROR]" . PHP_EOL;
					sleep(1);
				}
				/*foreach(range("A", "Z") as $element){
					$line = str_replace("§" . $element, "§" . mb_strtolower($element), $line);
				}
				$line = str_replace(" / ", "/", $line);
				$line = str_replace("{% ", "{%", $line);
				$line = str_replace("ZZZ", "{LINE}", $line);
				$result .= $key . ' = ' . '"' . $line . '"' . "\n";*/
				
			}
			/*$result = "";
			foreach($this->lang["en"] as $key => $str){
				$result .= str_replace("\n", "ZZZ", $this->translateContainer($key, "{%0}")) .  "\n";
			}
			var_dump($result);
			file_put_contents("/home/keys.txt", $result);*/
		}
	}
	
	/**
	 * @api
	 * @param string $key
	 * @param string $iso
	 *
	 * @return bool
	 */
	public function langExists(string $key, string $iso) : bool{
		$exists = isset($this->lang[$iso][$key]);
		if(!$exists){
			return isset($this->lang[self::LANG_DEFAULT][$key]);
		}
		return $exists;
	}
	
	/**
	 * @api
	 * @param string $key
	 * @param mixed ...$params Casteables to string
	 *
	 * Sends a message to all the server.
	 */
	public static function broadcast($key, ...$params) : void{
		$recipients = Server::getInstance()->getOnlinePlayers();
		$recipients[] = new ConsoleCommandSender();
		foreach($recipients as $recipient){
			$msg = self::translate($key, $recipient, ...$params);
			$recipient->sendMessage(self::translate("broadcast-prefix") . " " . $msg);
		}
	}
	
	/**
	 * @api
	 * @param string $key
	 * @param mixed ...$params First of ...$params MUST be a CommandSender,
	                           the subsequent param(s) MUST casteable to string
	 *
	 * Sends a message straight to player.
	 */
	public static function send($key, ...$params) : void{
		$msg = self::translate($key, ...$params);
		if(count($params) > 0 && $params[0] instanceof CommandSender){
			$params[0]->sendMessage($msg);
		}
	}
	
	/**
	 * @api
	 * @param string $key
	 * @param mixed ...$params First of ...$params COULD be a Player (other senders will pass gracefully),
	 *                         the subsequent param(s) MUST be casteable to string
	 * @return string
	 *
	 * Returns the translated string.
	 */
	public static function translate(string $key, ...$params) : string{
		if(self::$instance instanceof self){
			return self::$instance->translateContainer($key, ...$params);
		}
		Server::getInstance()->getLogger()->error("LangManager is not instantiated");
		return $key;
	}

    /**
     * @internal
     * @param string $key
     * @param mixed ...$params
     * @return string
     */
	private function translateContainer(string $key, ...$params) : string{
		if(Main::getInstance() === null){
			self::$instance = null;
			Server::getInstance()->getLogger()->error("LangManager is not instantiated");
			return $key;
		}
		
		$player = array_shift($params);
		if(!($player instanceof Player) || !$player->isOnline()){
			$iso = self::LANG_DEFAULT;
			if(!($player instanceof CommandSender)){ //Discard its not RemoteConsoleCommandSender or ConsoleCommandSender
			    array_unshift($params, $player);
			}
			$str = $this->translateString($key, $iso, ...$params);
		}else{
			$iso = $this->getPlayerLanguage($player);
			$str = $this->translateString($key, $iso, ...$params);
			$str = $this->translatePlayerVars($str, $player);
		}
		return $str;
	}
	
	/**
	 * @param Player $player
	 * @return string
	 */
	public function getPlayerLanguage(Player $player) : string{
		$iso = Main::getInstance()->getEntry($player, Main::ENTRY_LANG);
		if(!is_string($iso) || !in_array($iso, self::ALL_ISO_CODES)){
			$iso = $this->getLangByAddress($player->getAddress());
		}
		return $iso;
	}
	
	/**
	 * @internal
	 * @param string $str
	 * @param Player $player
	 * @return string
	 */
	public function translatePlayerVars(string $str, Player $player) : string{
		return str_replace([
		   "{X}", "{Y}", "{Z}", "{WORLD}", "{LEVEL}", "{HEALTH}", "{MAX_HEALTH}", "{PING}", "{NAME}", "{DISPLAY_NAME}"
		], [
		   strval($player->getFloorX()),
		   strval($player->getFloorY()),
		   strval($player->getFloorZ()),
		   strval($player->getLevel()->getFolderName()),
		   strval($player->getLevel()->getFolderName()),
		   strval(round($player->getHealth())),
		   strval(round($player->getMaxHealth())),
		   strval($player->getPing()),
		   $player->getName(),
		   $player->getDisplayName()
		], $str);
	}
	
	/**
	 * @internal
	 * @param string $str
	 * @return string
	 */
	public function translateServerVars(string $str) : string{
		if(Main::getInstance() === null){
			self::$instance = null;
			Server::getInstance()->getLogger()->error("LangManager is not instantiated");
			return $str;
		}
		
		return str_replace([
		    "{ONLINE}", "{MAX}", "{AVERAGE_TPS}", "{LOAD}" 
		], [
		   strval(count(Server::getInstance()->getOnlinePlayers())),
		   strval(Server::getInstance()->getMaxPlayers()),
		   strval(ceil(Server::getInstance()->getTicksPerSecondAverage())),
		   strval(Main::getInstance()->getServerLoadAverage())
		], $str);
	}
	
	/**
	 * @internal
	 * @param string $key
	 * @param string $iso
	 * @param mixed ...$params
	 * @return string
	 */
	private function translateString(string $key, string $iso, ...$params) : string{
		if(!isset($this->lang[$iso][$key])){
			$this->logError(self::LOG_NO_ISO_MESSAGE, $key, $iso);
			if(isset($this->lang[self::LANG_DEFAULT][$key])){
				$iso = self::LANG_DEFAULT;
			}
		}
		
		$keyData = "[" . $iso . "][" . $key . "]";

		
		$str = $this->lang[$iso][$key] ?? $keyData;
		preg_match_all("/(&[0-9a-fk-or])/u", $str, $matches, PREG_OFFSET_CAPTURE);
		if(isset($matches[0][0]) && $matches[0][0][1] === 0){
			$default = $matches[0][0][0];
		}else{
			$default = self::DEFAULT_COLOR;
		}
		
		$colorIndex = null;
		//Use {%0}, {%1}, {%2} or {%}, {%}, {%}...
		foreach($params as $i => $param){
			if(!isset(self::PATTERN_CHRISTMAS[$colorIndex])){
				//assert(count(self::PATTERN_CHRISTMAS) > 0);
				$colorIndex = 0;
			}
			$color = self::PATTERN_CHRISTMAS[$colorIndex++];
			
			if(!is_string($param) && !is_float($param) && !is_int($param) && !($i === 0 && $param === null)){
				$this->logError(self::LOG_TYPE_NOT_CASTEABLE, $key, $iso);
				$param = "";
			}
			$str = str_replace("{%" . $i . "}", $color . strval($param) . self::DEFAULT_COLOR, $str);
			$str = preg_replace('/' . preg_quote('{%}') . '/', strval($param), $str, 1);
			
			unset($params[$i]);
		}
		
		preg_match_all("/{/", $str, $haystack, PREG_OFFSET_CAPTURE); //Lang keys: {...}. Params not supported and will not be
		$originalStr = $str;
		foreach($haystack[0] as $needle){
			$substr = substr($originalStr, $needle[1] + 1);
			if(stripos($substr, "}") === false){
				continue;
			}
			$start = $needle[1] + 1;
			$pointer = $start;
			while($originalStr[$pointer] !== "}"){
				$pointer++;
			}
			$kkey = substr($originalStr, $start, $pointer - $start);
			if(isset($this->lang[$iso][$kkey]) && $kkey !== $key){
				$str = str_replace("{" . $kkey . "}", $this->translateString($kkey, $iso), $str);
			}
		}
		
		$str = $this->translateServerVars($str); //{ONLINE}, {MAX}, {AVERAGE_TPS}, {LOAD}
		$str = str_replace("{LINE}", TextFormat::EOL, $str); //{LINE}: Newline
		$str = $this->replaceHTMLTags($str); //<rainbow>, etc.
		return TextFormat::colorize($str);
	}
	
	/**
	 * self::rainbowize()
	 * @api
	 * @param string $str
	 * @param int $patern
	 * @param int $offset
	 * @return string
	 */
	public static function patternize(string $str, int $pattern, int &$offset = 0): string{
		switch($pattern){
			case self::PATTERN_RAINBOW:
				$colors = self::$PATTERN_RAINBOW;
				break;
			case self::PATTERN_CHRISTMAS:
				$colors = self::$PATTERN_CHRISTMAS;
				break;
			default:
				throw new \InvalidArgumentException("Invalid pattern " . $pattern);
		}
		if($str === ""){
			return " ";
        }
        $substrings = explode(" ", $str);
        if(count($substrings) > 1){
        	$strings = [];
            foreach($substrings as $sub){
                $strings[] = self::patternize($sub, $pattern, $offset);
			}
            return implode(" ", $strings);
        }else{
            $color = $pattern[0];
            $msg = "";
            for($i = 0; $i < strlen($str); $i++){
            	if($i + $offset !== 0){
            		$color = $colors[($i + $offset) % count($colors)];
				}
		        $msg .= $color . $str[$i];
			}
            $offset += $i;
            return $msg;
		}
	}
	
	/**
	 * @internal
	 * @param string $str
	 * @param string $tag
	 * @return array
	 */
	private function getAllHTMLTagValues(string $str, string $tag) : array{
		$regex = "#<\s*?" . $tag . "\b[^>]*>(.*?)</" . $tag . "\b[^>]*>#s";
		preg_match_all($regex, $str, $tags);
		return $tags;
	}
	
	/**
	 * @internal
	 * @param string $str
	 * @return string
	 */
	private function replaceHTMLTags(string $str) : string{
		foreach(self::PATTERN_TAGS as $pattern => $tag){
			if(extension_loaded("xml")){
		 		$dom = new \DomDocument();
		 		@$dom->loadHTML($str); 
		 		$elements = $dom->getElementsByTagName($tag);
		 		foreach($elements as $element){
		 			$str = str_replace("<" . $tag . ">" . $element->nodeValue . "</" . $tag . ">", self::patternize($element->nodeValue, $pattern) . "&r", $str);
		 		}
		 	}else{
		 		$tags = $this->getAllHTMLTagValues($str, $tag);
		 		if(!empty($tags[0]) && !empty($tags[1])){
		 			for($i = 0; $i < (count($tags) - 1); $i++){
		 				$str = str_replace($tags[0][$i], self::patternize($tags[1][$i], $pattern) . "&r", $str);
		 			}
		 		}
		 	}
		}
	 	return $str;
	}
	
	/**
	 * Returns the player's country code. This method fetches data directly from the GeoIp2 Country Database.
	 * @api
	 * @param string $ip
	 * @return string ISO 3166-1 string (two-letter country code)
	 */
	public function getCountryCode(string $ip) : string{
		if(isset($this->countryCodeCache[$ip])){ //Inherent of player address
			return $this->countryCodeCache[$ip];
		}
		if($this->geoIpReader === null){
	    	return "US"; //US population speaks English, self::LANG_DEFAULT
		}
		
		try{
			$record = $this->geoIpReader->country($ip);
		}catch(\Exception $e){ //InvalidArgumentException
			return "US";
		}
		return $record->country->isoCode ?? self::LANG_DEFAULT; //ISO 3166-1
	}
	
	/**
	 * @internal
	 * @param string $ip
	 * @return string
	 */
	public function getLangByAddress(string $ip) : string{
		if(isset($this->ipLangCache[$ip])){
			return $this->ipLangCache[$ip];
		}
		$country = $this->getCountryCode($ip);
		switch($country){
			case "DJ":
			case "ER":
			case "ET":
			    $lang = "aa";
			    break;
			case "AE":
			case "BH":
			case "DZ":
			case "EG":
			case "IQ":
			case "JO":
			case "KW":
        	case "LB":
        	case "LY":
        	case "MA":
        	case "OM":
        	case "QA":
        	case "SA":
        	case "SD":
	        case "SY":
        	case "TN":
        	case "YE":
            	$lang = "ar";
            	break;
        	case "AZ":
            	$lang = "az";
            	break;
            case "BY":
            	$lang = "be";
            	break;
            case "BG":
            	$lang = "bg";
            	break;
            case "BD":
            	$lang = "bn";
            	break;
        	case "BA":
            	$lang = "bs";
            	break;
            case "CZ":
            	$lang = "cs";
            	break;
			case "DK":
	            $lang = "da";
            	break;
       		case "AT":
        	case "CH":
        	case "DE":
        	case "LU":
            	$lang = "de";
            	break;
            case "MV":
            	$lang = "dv";
            	break;
        	case "BT":
           		$lang = "dz";
            	break;
            case "GR":
            	$lang = "el";
            	break;
        	case "AG":
        	case "AI":
        	case "AQ":
        	case "AS":
        	case "AU":
        	case "BB":
        	case "BW":
        	case "CA":
	        case "GB":
        	case "IE":
        	case "KE":
        	case "NG":
	        case "NZ":
        	case "PH":
        	case "SG":
        	case "US":
        	case "ZA":
        	case "ZM":
        	case "ZW":
            	$lang = "en";
            	break;
        	case "AD":
        	case "AR":
        	case "BO":
        	case "CL":
        	case "CO":
        	case "CR":
        	case "CU":
        	case "DO":
        	case "EC":
        	case "ES":
        	case "GT":
        	case "HN":
        	case "MX":
        	case "NI":
        	case "PA":
        	case "PE":
	        case "PR":
        	case "PY":
        	case "SV":
        	case "UY":
        	case "VE":
            	$lang = "es";
            	break;
        	case "EE":
            	$lang = "et";
            	break;
        	case "IR":
            	$lang = "fa";
            	break;
        	case "FI":
            	$lang = "fi";
            	break;
        	case "FO":
            	$lang = "fo";
            	break;
     		case "BE":
        	case "FR":
        	case "SN":
            	$lang = "fr";
            	break;
        	case "IL":
            	$lang = "he";
            	break;
        	case "IN":
            	$lang = "hi";
            	break;
       		case "HR":
            	$lang = "hr";
            	break;
        	case "HT":
            	$lang = "ht";
            	break;
        	case "HU":
            	$lang = "hu";
            	break;
        	case "AM":
            	$lang = "hy";
            	break;
        	case "ID":
            	$lang = "id";
            	break;
            case "IS":
            	$lang = "is";
            	break;
        	case "IT":
            	$lang = "it";
            break;
        	case "JP":
           		$lang = "ja";
            	break;
        	case "GE":
           		$lang = "ka";
            	break;
       		case "KZ":
            	$lang = "kk";
            	break;
      		case "GL":
            	$lang = "kl";
            	break;
        	case "KH":
            	$lang = "km";
            	break;
        	case "KR":
            	$lang = "ko";
            	break;
        	case "KG":
           		$lang = "ky";
            	break;
        	case "UG":
            	$lang = "lg";
            	break;
        	case "LA":
            	$lang = "lo";
            	break;
        	case "LT":
            	$lang = "lt";
            	break;
        	case "LV":
            	$lang = "lv";
            	break;
        	case "MG":
            	$lang = "mg";
            	break;
        	case "MK":
            	$lang = "mk";
            	break;
        	case "MN":
            	$lang = "mn";
            	break;
        	case "MY":
            	$lang = "ms";
            	break;
        	case "MT":
            	$lang = "mt";
            	break;
            case "MM":
            	$lang = "my";
            	break;
        	case "NP":
          		$lang = "ne";
            	break;
      		case "AW":
        	case "NL":
           		$lang = "nl";
            	break;
       		case "NO":
            	$lang = "no";
            	break;
        	case "PL":
            	$lang = "pl";
            	break;
        	case "AF":
            	$lang = "ps";
            	break;
        	case "AO":
        	case "BR":
        	case "PT":
            	$lang = "pt";
            	break;
            case "RO":
            	$lang = "ro";
            	break;
       		case "RU":
        	case "UA":
            	$lang = "ru";
            	break;
        	case "RW":
            	$lang = "rw";
            	break;
        	case "AX":
          		$lang = "se";
            	break;
        	case "SK":
            	$lang = "sk";
            	break;
       		case "SI":
           		$lang = "sl";
            	break;
        	case "SO":
            	$lang = "so";
            	break;
        	case "AL":
            	$lang = "sq";
            	break;
        	case "ME":
        	case "RS":
            	$lang = "sr";
            	break;
        	case "SE":
            	$lang = "sv";
            	break;
        	case "TZ":
            	$lang = "sw";
            	break;
        	case "LK":
            	$lang = "ta";
            	break;
            case "TJ":
            	$lang = "tg";
            	break;
            case "TH":
                $lang = "th";
                break;
            case "TM":
            	$lang = "tk";
            	break;
        	case "CY":
        	case "TR":
            	$lang = "tr";
            	break;
        	case "PK":
            	$lang = "ur";
           		break;
        	case "UZ":
           		$lang = "uz";
            	break;
        	case "VN":
           		$lang = "vi";
           		break;
        	case "CN":
        	case "HK":
        	case "TW":
            	$lang = "zh";
            	break;
            default:
            	$lang = self::LANG_DEFAULT;
        }
        if(in_array($lang, self::ALL_ISO_CODES)){
			$this->ipLangCache[$ip] = $lang;
        	return $lang;
        }
		$this->ipLangCache[$ip] = $def = self::LANG_DEFAULT;
        return $def;
    }
    
} 