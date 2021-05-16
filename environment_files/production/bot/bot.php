<?php

use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use Discord\Parts\User\Member;
use Discord\Parts\Channel\Overwrite;
use Discord\Discord;
use Discord\Helpers\Collection; //Inspired by Laravel
use Discord\Repository\GuildRepository;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Author;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Embed\Footer;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use Carbon\Carbon;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/vendor2/autoload.php";
require_once "/var/www/html/VerifyAPI/functions.php";

//Validate Xbox Live username: strlen($ign) >= 1 && strlen($ign) < 16 && preg_match("/[a-z0-9 ]/", $ign) === 1
//Needs Administrator (permissions=8)
//Needs Guild Members Intent

@mkdir(__DIR__ . "/bot_data", 0777);

date_default_timezone_set("UTC");

//Config
define("MAIN_GUILD", "774297440705576980");
define("DISCORD_BOT_TOKEN", "Njg1NTYwMTU5NzAyODc2MjAy.XmKbpw.X-LetVTR1k9MlHoGIosPrTPtueE");
define("TRANSLATE_COOLDOWN", 10);
define("MEME_COOLDOWN", 15);
define("DMALL_COOLDOWN", 60 * 30);
define("RECRUITMENT_COOLDOWN", 3600);
define("BUG_REPORT_COOLDOWN", 300); //This is the same as ingame
define("SUGGESTION_EXPIRY_TIME", 86400);
define("COMMENT_POST_COOLDOWN", 30);
define("STATUS_COOLDOWN", 15);
define("CHECKOUT_COOLDOWN", 60 * 10);
define("SUGGESTION_COOLDOWN", 60 * 5);
define("TICKET_COOLDOWN", 60 * 3);
define("TICKET_PERMISSIONS", ["view_channel", "send_messages", "embed_links", "attach_files", "read_message_history"]);

define("SERVER_IP", "147.135.118.178");
define("SERVER_PORT", 19132);
define("SERVER_RCON_PASSWORD", "yvYrZuXwKHQfHLF6LtJQf4SP9sxy6LdQ");
define("MOD_APPLY_QUESTIONS", [
    "What is your full Discord username?" => ["regex" => '^[^#]{2,32}#\d{4}$', "message" => "Enter a valid Discord username."],
    "What is your real name?" => [],
    "What is your age? (Spoofing your age is not allowed)" => ["regex" => '[1-9][0-9]', "message" => "Enter a valid age."],
    "What country do you live in?" => [],
    "What is your native language?" => [],
    "What other languages do you speak?" => [],
    "What is your time zone?" => [],
    "Are you okay in call with people?" => [],
    "Since when have you been playing Minecraft?" => [],
    "How much hours can you spend per week?" => [],
    "Have you worked for other servers? If so so name them:" => [],
    "From 1-10, rate how good moderator you think you will be:" => ["regex" => '^(?:[1-9]|0[1-9]|10)$', "message" => "Enter a number 1-10."],
    "Describe yourself in detail:" => ["regex" => '^.{300,}$', "message" => "Answer is too short."],
    "Why are you interested in working at our server?" => ["regex" => '^.{150,}$', "message" => "Answer is too short."],
    "What makes you different from the rest of moderators out there?" => ["regex" => '^.{150,}$', "message" => "Answer is too short."],
    "Anything else that you would like to include in your application:" => []
]);
define("BUILDER_APPLY_QUESTIONS", [
    "What your full Discord username?" => ["regex" => '^[^#]{2,32}#\d{4}$', "message" => "Enter a valid Discord username."],
    "What is your real name?" => [],
    "What is your age? (Spoofing your age is not allowed)" => [],
    "What country do you live in?" => [],
    "What is your native language?" => [],
    "What other languages do you speak?" => [],
    "What is your time zone?" => [],
    "Are you okay in call with people?" => [],
    "Since when have you been playing Minecraft?" => [],
    "How much hours can you spend per week?" => [],
    "Have you worked for other servers? If so so name them:" => [],
    "What build styles are you good at? (Medieval, Rustic, Fantasy, Modern:)" => ["regex" => '(.*Medieval|.*Rustic|.*Fantasy|.*Modern)', "message" => "Medieval, Rustic, Fantasy, Modern"],
    "Describe yourself in detail:" => ["regex" => '^.{300,}$', "message" => "Answer is too short."],
    "Why are you interested in working at our server?" => ["regex" => '^.{150,}$', "message" => "Answer is too short."],
    "What makes you different from the rest of builders out there?" => ["regex" => '^.{150,}$', "message" => "Answer is too short."],
    "Are you looking to be paid or free builder?" => [],
    "Anything else that you would like to include in your application:" => []
]);

define("UNALLOWED_CONDUCTS", [
   "Offensive language", "Unrelated links", "Incorrect channel", "Swearing", "Spamming", "Advertising",
   "Trolling", "Inappropiate username/profile picture", "Tagging staff", "Bullying", "Toxic behavior", "Alternate accounts"
]);
define("PARTNER_COUPON_CODE", "L6ZQufa3U3ZEyCrL");
define("COUPON_CODES", [
	PARTNER_COUPON_CODE => 15, //DO NOT REMOVE
	"CHRISTMAS" => 35
]); //All coupon codes ~~SHALL~~ MUST be written in uppercase and no spaces
define("VERIFYCODE_EXPIRY", 60 * 15);
define("PREMIUM_RANKS", ["Vip", "Mvp", "JuniorYT", "Ultra", "Legend", "Ultimate", "YouTuber", "Universe", "Nightmare", "Trial", "Builder", "Mod", "HeadMod", "Admin", "HeadAdmin", "Owner"]);
//PayPal SDK
define("PAYPAL_SDK_CLIENT_ID", "Aa1S7QMRb0hrL6NeVfRiwOB6K9_Q3NxWRuBDMn3ADMvI7xaNoZsCEvfc6IX4Q7eCwsSJ39_j78Hb3D-_");
define("PAYPAL_SDK_CLIENT_SECRET", "ELpuRo7udlmxTKrVSXlMn7pWuDKOtiKcFoi61RmH25dwx5iOWY6Lm_B5lEAQCOyDLVKUf0D64iYUwPSa");

define("GITHUB_API_ENDPOINT", "https://api.github.com/repos/kenygamer/EliteStar/"); //With ending trailing slash
define("GITHUB_ACCESS_TOKEN", "8f8d590f5241cdc4de1f10096c36b9b5f80e00b1");
	
//File paths
define("BOT_BEAT_FILE", "/var/www/html/store/bot_beat.js");
define("STORE_PAYMENTS_FILE", "/var/www/html/store/payments.js");
define("LANG_MESSAGES_FILE", __DIR__ . "/bot_data/messages.js");
define("STORE_PACKAGES_FILE", __DIR__ . "/bot_data/packages.js");
define("STORE_TERMS_FILE", __DIR__ . "/bot_data/tos.txt");
define("STORE_STOREFRONT_FILE", __DIR__ . "/bot_data/storefront.txt");
define("MODERATOR_DESCRIPTION_FILE", __DIR__ . "/bot_data/moderator_description.txt");
define("BUILDER_DESCRIPTION_FILE", __DIR__ . "/bot_data/builder_description.txt");
define("STATS_FILE", __DIR__ . "/bot_data/stats.js");
define("WARNINGS_FILE", __DIR__ . "/bot_data/warnings.js");
define("VERIFICATION_FILE", __DIR__ . "/bot_data/verification.js");
define("INVITES_FILE", __DIR__ . "/bot_data/invites.js");
define("TICKETS_FILE", __DIR__ . "/bot_data/tickets.js");
define("GIVEAWAYS_FILE", __DIR__ . "/bot_data/giveaways.js");
define("APPLICATIONS_FILE", __DIR__ . "/bot_data/applications.js");
define("TICTACTOE_GRID_PATH", __DIR__ . "/bot_data/");
//APIs
define("SUBREDIT_JSON_URL", "https://www.reddit.com/r/pics/search.json?q=meme&sort=new"); //Meme API
define("VERIFYAPI_URL", "http://localhost/VerifyAPI/");
define("VERIFYAPI_ENDPOINT", "MainController.php");
define("VERIFYAPI_SERVERID", 0);
define("VERIFYAPI_SERVERKEY", "gF53Azjpwzf8r5q7atySEefEEzg9Eh8kNW3CFy7cZWkFAdNs5N6BqWjWwuXR5pK3");
define("SKINAPI_URL", "https://crowdmc.us.to/SkinAPI.php");
define("COMPONENTS_ENDPOINT", "https://status.crowdmc.us.to/api/v1/components"); //TODO
define("MAIN_SERVER_COMPONENT", "Main Server");

//Channels
//define("MEMBER_COUNT_CHANNEL", "");
//define("ONLINE_PLAYERS_CHANNEL", "");
define("MCPE_RELAY_CHANNEL", "774337020930949131");
define("STORE_COMMANDS_CHANNEL", "774331190840393758");
define("LINK_CHANNEL", "776240720830267423");
define("MAIN_CHAT_CHANNEL", "774299237408047124");
define("SUGGESTIONS_CHANNEL", "774300798967480320");
define("APPROVED_SUGGESTIONS_CHANNEL", "776496318176296960");
define("HIGH_TIER_CHANNEL", "781173618675023912");
define("WELCOME_CHANNEL", "774299237408047124"); //MAIN_CHAT_CHANNEL
define("TICKET_LOG_CHANNEL", "776241989158830080");
define("GIVEAWAY_CHANNEL", "774302348372738050");
define("TICKET_CATEGORY_CHANNEL", "776241115841298444");
define("WARN_LOG_CHANNEL", "776241399942610944");
define("RECRUITMENTS_CHANNEL", "774333355025236009");
define("REACTION_ROLES_CHANNEL", "774336766424907867");

//Roles
define("BOOSTER_ROLE", "776242433696333874");
define("PREMIUM_ROLE", "774712631280795668");
define("LINKED_ROLE", "776243480162336798");
define("SUPPORTTEAM_ROLE", "776242567946960906"); //Can warn users
define("TESTER_ROLE", "776242527229181952");
define("VERIFIED_ROLE", "780578668762103809");
define("MAIN_OWNER_ROLE", "774350907906326549"); //Can deploy tests, purge messages
define("ADMIN_ROLE", "774710213911642183"); //(HeadMod) Can make giveaways, use application admin
define("GUEST_ROLE", "776242509067321355");
define("EVERYONE_ROLE", "776242509067321355"); //This role is for everyone without a reaction role
define("REACTION_ROLES", [ //Mapped role => reaction. TODO: verify they are existent. It will currently omit checks (matches _ROLE)
	"774300463989260299" => "\xf0\x9f\x94\x94", //Announcements
	"774337791852412988" => "\xe2\x9a\x99", //Changelog
	"774302372113285181" => "\xf0\x9f\x97\xb3", //Polls
	"774331531824988200" => "\xf0\x9f\x8e\xa5", //Featured videos
	"774330483534200873" => "\xf0\x9f\x9b\x92", //Store pins
	"774302348372738050" => "\xf0\x9f\x8e\x81", //Discord giveaways
	"774302348372738050" => "\xf0\x9f\x8e\x8a", //Ingame events
	""
]);
//Tic Tac Toe
define("TICTACTOE_GRID_IMAGE", __DIR__ . "/bot_data/tictactoe_grid.png");
define("TICTACTOE_X_IMAGE", __DIR__ . "/bot_data/tictactoe_cross.png");
define("TICTACTOE_CIRCLE_IMAGE", __DIR__ . "/bot_data/tictactoe_circle.png");
define("TICTACTOE_WINS", [
	[
		1, 1, 1,
		0, 0, 0,
		0, 0, 0
	], [
		0, 0, 0,
		1, 1, 1,
		0, 0, 0
	], [
		0, 0, 0,
		0, 0, 0,
		1, 1, 1
	], [
		1, 0, 0,
		1, 0, 0,
		1, 0, 0
	], [
		0, 1, 0,
		0, 1, 0,
		0, 1, 0
	], [
		0, 0, 1,
		0, 0, 1,
		0, 0, 1
	], [
		1, 0, 0,
		0, 1, 0,
		0, 0, 1
	], [
		0, 0, 1,
		0, 1, 0,
		1, 0, 0
	]
]);
define("TICTACTOE_POSITIONS", [
	[36, 20], [304, 20], [576, 20],
	[36, 265], [304, 265], [576, 265],
	[36, 500], [304, 500], [576, 500]
]);
define("TICTACTOE_SQUARE_SIZE", 180);

$lang = (array) json_decode(@file_get_contents(LANG_MESSAGES_FILE), true);
$packages = (array) json_decode(@file_get_contents(STORE_PACKAGES_FILE), true);
$warnings = (array) json_decode(@file_get_contents(WARNINGS_FILE), true);
$verification = (array) json_decode(@file_get_contents(VERIFICATION_FILE), true);
$invites = (array) json_decode(@file_get_contents(INVITES_FILE), true);
$stats = (array) json_decode(@file_get_contents(STATS_FILE), true);
$tickets = (array) json_decode(@file_get_contents(TICKETS_FILE), true);
$giveaways = (array) json_decode(@file_get_contents(GIVEAWAYS_FILE), true);
$suggestions = (array) json_decode(@file_get_contents(SUGGESTIONS_FILE), true);
$applications = (array) json_decode(file_get_contents(APPLICATIONS_FILE), true);
$tos = (string) @file_get_contents(STORE_TERMS_FILE);
$moderatorDescription = (string) @file_get_contents(MODERATOR_DESCRIPTION_FILE);
$builderDescription = (string) @file_get_contents(BUILDER_DESCRIPTION_FILE);
$storefront = (string) @file_get_contents(STORE_STOREFRONT_FILE);

$memes = [null, []];
$ttt = [];
$author = null;
$sessions = [];
$cooldown = [];
$storeRedirect = [];
/* Start - Not saved in filesystem. */
$linksCache = [];
$uidsCache = []; 
$codeCache = []; 
$invitesCache = [];
$applicationsCache = [];
$messageAction = [];
/** End */
$rcon = new Rcon(SERVER_IP, SERVER_PORT, SERVER_RCON_PASSWORD, 3);
$start = time();
foreach(["mbstring", "bcmath"] as $ext){
	if(!extension_loaded($ext)){
		echo "[Error] " . $ext . " extension is required. Pro-Tip: use php7.2-" . $ext . " (run with /usr/bin/php7.2 " . $_SERVER["SCRIPT_NAME"] . ")" . PHP_EOL;
		exit(1);
	}
}
//TODO: custom emoji support
const EMOJI_AGREE = "✅"; //:greenTick: (Discord Testers) 709165905363468321
const EMOJI_DISAGREE = "❌"; //:redTick: (Discord Testers)709165862086639616
const EMOJI_PARTY = "\xf0\x9f\x8e\x89";
const EMOJI_ONE = "\x31\xef\xb8\x8f\xe2\x83\xa3";
const EMOJI_TWO = "\x32\xef\xb8\x8f\xe2\x83\xa3";
const EMOJI_UNDO = "\xf0\x9f\x94\x84";
const EMOJI_LINK = "\xf0\x9f\x94\x97";

const COLOR_SUCCESS = 0x90ee90; const COLOR_GREEN = COLOR_SUCCESS;
const COLOR_WARNING = 0xffcc00; const COLOR_YELLOW = COLOR_WARNING;
const COLOR_INFO = 0x1861BA; const COLOR_BLUE = COLOR_INFO;
const COLOR_ERROR = 16764107; const COLOR_RED = COLOR_ERROR;

/**
 * @param resource $board
 * @param int $number
 * @param int $circle X 0: Circle, 1: X
 */
function makeTttMove(&$board, int $number, int $circleX) : void{
	if(!is_resource($board)){
		throw new \InvalidArgumentException("Argument 1 must be of the type resource");
	}
	if(($maxNumber = count(TICTACTOE_POSITIONS)) < $number){
		throw new \OutOfRangeException("Argument 2 must be a number between 1-" . $maxNumber);
	}
	list($x, $y) = TICTACTOE_POSITIONS[$number - 1];
	/*$white = imagecolorallocate($board, 255, 255, 255);
	imagefilledrectangle($board, $x, $y, $x + TICTACTOE_SQUARE_SIZE, $y + TICTACTOE_SQUARE_SIZE, $white);*/
	
	$move = imagecreatefromstring(file_get_contents($circleX ? TICTACTOE_X_IMAGE : TICTACTOE_CIRCLE_IMAGE));
	$move = imagescale($move, TICTACTOE_SQUARE_SIZE, TICTACTOE_SQUARE_SIZE, IMG_NEAREST_NEIGHBOUR);
	imagecopy($board, $move, $x, $y, 0, 0, imagesx($move), imagesy($move));
}

/**
 * Sends an embed to the DM channel.
 * @param Channel|Member|User $channel
 * @param string $title
 * @param string $description
 * @param ?int $color Decimal embed color
 * @param ?string $image_url Image URL
 * @param string[] $reactions
 * @param array $files
 * @param \Closure $callback On success
 */
function sendEmbed($channel, string $title, string $description, ?int $color = null, ?string $image_url = null, array $reactions = [], array $files = [], \Closure $callback = null, $split = true) : void{
	global $client, $author;
	$title = substr($title, 0, 256); //Fix
	
	/*
	 * Fix "Unable to encode json. Error: Malformed UTF-8 characters, possibly incorrectly encoded in /home/bot/vendor2/charlottedunois/yasmin/src/UtilHelpers.php:195"
	ils/URLHelpers.php:195
	 */
	$title = mb_convert_encoding($title, "UTF-8", "UTF-8");
	$description = mb_convert_encoding($description, "UTF-8", "UTF-8");
	
	if(!in_array(true, [$channel instanceof Channel, $channel instanceof User, $channel instanceof Member])){
		throw new \Exception("Argument 1 passed to sendEmbed() must be of the type Channel|User, " . (gettype($channel) === "object" ? get_class($channel) : gettype($channel)) . " given");
	}
	$embed = $client->factory(Embed::class);
	$embed->title = $title;
	$array = str_split($description, 1997);
	if(count($array) > 1 && $split){
		foreach($array as $i => $desc){
			if(end($array) === $desc){
				sendEmbed($channel, $title, $desc, $color, $image_url, $reactions, $files, $callback, false);
			}else{
				sendEmbed($channel, $title, $desc, $color, null, [], [], null, false);
			}
		}
		return;
	}
	if(is_int($color)){
		$embed->color = $color;
	}
	if(is_string($image_url)){
		$image = $client->factory(Image::class);
		$image->url = $image_url;
		$embed->image = $image;
	}
	$embed->description = $description;
	$embed->timestamp = Carbon::now();
	if($channel instanceof Channel){
		$author_ = $client->factory(Author::class);
		$author_->name = $author !== null ? ($author->username . "#" . $author->discriminator) : $channel->guild->name;
		$author_->icon_url = $author !== null ? $author->avatar : \str_replace("cdn.discord.com", "cdn.discordapp.com", $channel->guild->icon);
		$embed->author = $author_;
	}
	$footer = $client->factory(Footer::class);
	$footer->name = "Crafted with \xe2\x9d\xa4\xef\xb8\x8f by Kevin.#1995 | 🎮 " . SERVER_IP . ":" . SERVER_PORT . " | !status";
	
	if($channel instanceof Channel && in_array($channel->type, [Channel::TYPE_TEXT, Channel::TYPE_NEWS])){
		$channel->sendMessage("", false, $embed)->done(function(Message $message) use($callback, $reactions){
			if($callback !== null){
				$callback($message);
			}
			foreach($reactions as $reaction){
				$message->react($reaction)->done();
			}
		});
	}else{
		if($channel instanceof Member){
			$channel = $channel->user;
			if($channel === null){
				throw new \RuntimeException("Member does not have a User");
			}
		}
		if($channel instanceof User){	
			$channel->sendMessage("", false, $embed)->done(function($message) use($callback, $reactions){
				if($callback !== null){
					$callback($message);
				}
				foreach($reactions as $reaction){
					$message->react($reaction)->done();
				}
			});
		}else{
			throw new \RuntimeException("Argument 1 must be an instance of Member|User|Channel, " . gettype($channel) . " given");
		}
	}
	foreach($files as $file){
		$channel->sendFile($file)->done();
	}
}

/**
 * Translates the language entry with the passed parameters.
 * @param string $key
 * @param mixed ...$params
 * @return string
 */
function getLang(string $key, ...$params) : string{
	global $lang;
	$msg = $lang[$key] ?? "";
	foreach($params as $i => $param){
		$msg = str_replace("{%" . $i . "}", $param, $msg);
	}
	
	preg_match("/{/", $msg, $untranslated, PREG_OFFSET_CAPTURE);
	foreach($untranslated as $needle){
		$start = $needle[1] + 1;
		$pointer = $start;
		while($msg[$pointer] !== "}"){
			$pointer++;
		}
		$key = substr($msg, $start, $pointer - $start);
		if(isset($lang[$key])){
			$msg = str_replace("{" . $key . "}", $lang[$key], $msg);
		}
	}
	
	return $msg;
}

/**
 * @param int $id
 * @return ?array package with features inherited
 */
function getPackageById(int $id) : ?array{
	global $packages;
	$list = [];
	foreach($packages as $category){
		foreach($category as $name => $item){
			$item["name"] = $name;
			
			$inherited = [];
			$currentKey = array_search($name, array_keys($category));
			$inherit = $item["inherit_features"] ?? "";
			if($inherit !== ""){
				$inherit = preg_replace("/[^\w]+/", "", $inherit);
				while($currentKey){
					$currentKey--;
					$lowerName = array_keys($category)[$currentKey];
					$lowerPackage = $category[$lowerName];
					if($inherit === preg_replace("/[^\w]+/", "", $lowerName)){
						$inherited = array_merge($inherited, $lowerPackage["features"] ?? []);
						$inherit = $lowerPackage["inherit_features"] ?? "";
					}
				}
			}
			$inherited = array_reverse($inherited, true);
			$item["features"] = array_merge($inherited, $item["features"] ?? []);
			
			$list[] = $item;
		}
	}
	return $list[$id - 1] ?? null;
}

/**
 * Returns the package by ID.
 * @param array $package
 * @return ?int
 */
function getPackageId(array $package) : ?int{
	global $packages;
	$i = 0;
	foreach($packages as $category){
		foreach($category as $item){
			++$i;
			if($item["description"] === $package["description"]){ //TODO: add an unique identifier to each package
				return $i;
			}
		}
	}
	return -1;
}

/**
 * Calculates the basket price for this session.
 * @param array $session
 * @param bool $applyCoupons
 * @return float
 */
function getBasketPrice(array $session, bool $applyCoupons = false) : float{
	$total = 0;
	foreach($session["items"] as $id => $quantity){
		$price = getPackageById($id)["price"];
		$total += $price * $quantity;
	}
	if($applyCoupons){
		$discount = 0;
		foreach($session["coupons"] as $coupon){
			$discount += COUPON_CODES[$coupon];
		}
		if($discount > 99){
			$discount = 99;
		}
		$total -= $total * $discount / 100;
	}
	return round($total, 2);
}

/**
 * @param string $refId
 * @param string $orderId Used by payments.php to capture the payment
 * @return bool
 */
function setPendingPaymentOrderId(string $refId, string $orderId){
	/** @var array */
	$payments = json_decode(file_get_contents(STORE_PAYMENTS_FILE), true);
	if(!is_array($payments)){
		$payments = [];
	}
	if(isset($payments[$refId])){
		$payments[$refId]["orderId"] = $orderId;
		file_put_contents(STORE_PAYMENTS_FILE, json_encode($payments));
		return true;
	}
	return false;
}

/**
 * @param array $session
 * @return string reference ID
 */
function addPendingPayment(array $session) : string{
	/** @var array */
	$payments = json_decode(file_get_contents(STORE_PAYMENTS_FILE), true);
	if(!is_array($payments)){
		$payments = [];
	}
	$ref_id = bin2hex(openssl_random_pseudo_bytes(10));
	$onlineCommands = [];
	$instantCommands = [];
	$slots = 0;
	$orders = [];
	foreach($session["items"] as $id => $quantity){
		$package = getPackageById($id);
		$slots += ($package["slots"] ?? 0) * $quantity;
		for($i = 0; $i < $quantity; $i++){
			foreach($package["onlineCommands"] ?? [] as $cmd){
				$onlineCommands[] = ["cmd" => $cmd, "order" => getPackageById($id)["commandOrder"] ?? 0];
			}
			foreach($package["instantCommands"] ?? [] as $cmd){
				$instantCommands[] = ["cmd" => $cmd, "order" => getPackageById($id)["commandOrder"] ?? 0];
			}
		}
	}
	foreach(["onlineCommands", "instantCommands"] as $var){
		uasort($$var, function(array $a, array $b) : int{
			return $a["order"] > $b["order"] ? 1 : -1;
		});
		$$var = array_map(function(array $command){
			return $command["cmd"];
		}, $$var);
	}
	
	$payments[$ref_id] = [
	    "username" => $session["username"],
	    "completed" => false,
	    "onlineCommands" => $onlineCommands,
	    "slots" => $slots,
	    "instantCommands" => $instantCommands,
	    "timestamp" => time(),
	    "amount" => $session["amount"]
	];
	\file_put_contents(STORE_PAYMENTS_FILE, \json_encode($payments));
	return $ref_id;
}

$func = function(int $signal) use(&$tickets, &$giveaways, &$applications, &$verification, &$stats, &$func){
	global $client, $socket, $socket2;
	$files = glob(TICTACTOE_GRID_PATH . "*+14.png");
	foreach($files as $file){
		@unlink($file);
	}
	@socket_close($socket);
	
	global $loop;
	file_put_contents(STATS_FILE, json_encode($stats, JSON_UNESCAPED_UNICODE));
	file_put_contents(TICKETS_FILE, json_encode($tickets, JSON_UNESCAPED_UNICODE));
	file_put_contents(VERIFICATION_FILE, json_encode($verification, JSON_UNESCAPED_UNICODE));
	file_put_contents(GIVEAWAYS_FILE, json_encode($giveaways, JSON_UNESCAPED_UNICODE));
	file_put_contents(SUGGESTIONS_FILE, json_encode($suggestions, JSON_UNESCAPED_UNICODE));
	file_put_contents(APPLICATIONS_FILE, json_encode($applications, JSON_UNESCAPED_UNICODE));
	//invites.js
	$loop->removeSignal($signal, $func);
	@socket_close($socket);
	@socket_close($socket2);
	exit(0);
};

	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP) or die("Ping socket: Could not create socket" . PHP_EOL); //SOL_TCP = 0
	socket_bind($socket, "0.0.0.0", 45007) or die("Ping socket: Could not bind to socket" . PHP_EOL);
	
	$socket2 = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP) or die("Relay socket: Could not create socket" . PHP_EOL);
	socket_bind($socket2, "0.0.0.0", 45008) or die("Relay socket: Could not bind to socket" . PHP_EOL);
	
	$t = microtime(true);
	
	$loop = LoopFactory::create();
	$client = new Discord([
		"loop" => $loop,
		"loadAllMembers" => true,
		"token" => DISCORD_BOT_TOKEN,
		//"loggerLevel" => \Monolog\Logger::DEBUG
	]);
	$loop->addSignal(SIGINT, $func); //Kill (CTRL+C)
	$loop->addSignal(SIGHUP, $func); //Signal hang up
	$loop->addSignal(SIGTERM, $func); //Termination
	//Timers
	$client->getLoop()->addPeriodicTimer(0.1, function() use(&$t, $socket, $socket2, $client){
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;}
		if(@socket_recvfrom($socket2, $buffer, 32000, MSG_DONTWAIT, $ip, $port)){
			$guild = getGuild();
			$channel = $guild->channels->get("id", MCPE_RELAY_CHANNEL);
			if($channel !== null){
				$messages = json_decode($buffer, false, $depth = 512); //Not an associative array
				if(is_array($messages)){
					foreach($messages as $message){
						sendEmbed($channel, "", !empty($message) ? "**" . $message . "**" : $message, COLOR_INFO);
					}
				}
			}
		}
		if(@socket_recvfrom($socket, $buffer, 32000, MSG_DONTWAIT, $ip, $port)){
			//var_dump($buffer);
			if(substr($buffer, 0, 4) !== "PING"){
				echo "Expected request starting with PING, got " . $buffer . PHP_EOL;
			}
			if(count($parts = explode(":", $buffer)) === 2){
				list($_, $requestT) = $parts;
				//echo $buffer . PHP_EOL;
				//echo $ip . ":" . $port . PHP_EOL;
				socket_sendto($socket, "PONG:" . (microtime(true) - $requestT), 100, 0, $ip, $port);
			}else{
				echo "Expected 2 parts cut by colon, got " . count($parts) . ". Buffer: " . $buffer . PHP_EOL;
			}
			$t = microtime(true);
		}
	});
	$loop->addPeriodicTimer(5, function() use($client){
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;}
		$guild = getGuild();
		$result = getURL(COMPONENTS_ENDPOINT);
		if(is_array($result)){
			foreach($result["data"] ?? [] as $component){
				if(stripos($component["name"], MAIN_SERVER_COMPONENT) !== false){
					//var_dump($component);
					$name = "\xF0\x9F\x8C\x88 Online Players: " . (substr($component["description"], 0, strpos($component["description"], "Online Players:"))) . " | \xf0\x9f\x91\xa5 Member Count: " . $guild->member_count;
					$activity = $client->factory(\Discord\Parts\User\Activity::class);
					$activity->type = \Discord\Parts\User\Activity::TYPE_PLAYING;
					$activity->name = $name;
					$client->updatePresence($activity, false, "online", false);
					break;
				}
			}
		}
	});
	$loop->addPeriodicTimer(5, "updateInviteCache");
	
	$loop->addPeriodicTimer(15, $timer = function() use(&$linksCache, &$giveaways, &$suggestions, $client){
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;}
		$guild = getGuild();
		
		//Suggestions
		$channel = getGuild()->channels->get("id", SUGGESTIONS_CHANNEL);
		foreach($suggestions as $suggestion){
			if(time() - $suggestion["time"] >= SUGGESTION_EXPIRY_TIME){
				$channel->messages->fetch($suggestion["message"])->done(function(Message $message) use($suggestion){
					$approveNo = 0;
					$denyNo = 0;
					foreach($message->reactions as $reaction){
						switch($reaction->emoji->name){
							case EMOJI_AGREE:
								$approveNo += $reaction->count;
								break;
							case EMOJI_DISAGREE:
								$denyNo += $reaction->count;
								break;
						}
					}
					foreach($suggestions as $index => $suggestion_){
						if($suggestion === $suggestion_){
							unset($suggestions[$index]);
							break;
						}
					}
					if($approveNo >= $denyNo){
						$channel = getGuild()->channels->get("id", APPROVED_SUGGESTIONS_CHANNEL);
						$embed = $message->embeds->first();
						if($embed === null){
							echo "Embed should never be null" . PHP_EOL;
							return;
						}
						sendEmbed($channel, $embed->title, $embed->description);
						$message->channel->editMessage($message, translate("suggestion_welcome"))->done();
					}else{
						$message->channel->message->delete($message)->done();
					}
				}, function(\Exception $e) use($sugg){
					//Message was deleted perhaps
					foreach($suggestions as $index => $suggestion_){
						if($suggestion_ === $sugggestion){
							unset($suggestions[$index]);
							break;
						}
					}
					echo $e->getMessage() . PHP_EOL;
				});
			}
		}
		
		//Giveaways
		$gChannel = $guild->channels->get("id", GIVEAWAY_CHANNEL);
		if($gChannel !== null){
			$messages = $gChannel->getMessageHistory(["limit" => 10])->done(function($collection) use(&$giveaways, $gChannel){
				foreach($giveaways as $i => $giveaway){
					list($end, $prize, $messageId) = $giveaway;
					$endDate = date("F jS, Y H:i:s", $end); //F = Month, j = day, S = ordinal suffix, Y = year
				  
				    $message = $collection->get("id", $messageId);
				    if($message === null){
				    	unset($giveaways[$i]);
				    	continue;
				    }
				    
				    $users = [];
				    foreach($message->reactions as $reaction){
				    	if($reaction->emoji->name === EMOJI_PARTY){
				    		foreach($reaction->users as $user){
				    			if(!$user->bot){
				    				$users[] = $user;
				    			}
				    		}
				    	}
				    }
				    if(time() >= $end){
				    	if(count($users) < 1){
							echo "ENDDDDDDDIIIINGG because Users none and the time is > " . $end . " end\n";
				    		endNoWinner: {
				    			$gChannel->editMessage($message, getLang("giveawayTitle", $prize) . "\n\n" . getLang("giveawayInactive.1"))->done();
				    			unset($giveaways[$i]);
				    		}
				    	}else{
				    		roll: {
				    			if(empty($users)){
									echo "ENDDDDDDDIIIINGG because ROLL none\n";
				    				goto endNoWinner;
				    			}else{
				    				$winner = $users[array_rand($users)];
				    			}
				    		}
				    		$member = $message->channel->guild->members->get("id", $winner);
				    		if($member === null){
				    			//Remove to not loop through again
				    			foreach($users as $i => $user){
				    				if($user->id === $winner->id){
				    					unset($users[$i]);
										echo "Removed winner to not loop\n";
				    				}
				    			}
				    			$gChannel->sendMessage(getLang("giveawayWinnerLeft", $prize))->done();
				    			goto roll;
				    		}
				    		
				    		$gChannel->editMessage($message, getLang("giveawayTitle", $prize) . "\n\n" . getLang("giveawayInactive.2", $winner->__toString(), $endDate))->done();
				    		
				    		$gChannel->sendMessage(getLang("giveawayWinner", $winner->__toString(), $prize))->done();
				    		sendEmbed($winner, getLang("giveawayTitle", $prize), getLang("giveawayWinnerDM", $prize), COLOR_SUCCESS);
				    		$winner = null; 
				    		unset($giveaways[array_search($giveaway, $giveaways)]);
				    	}
				    }else{
				    	$hours = date("H", $end - time());
				    	$minutes = date("i", $end - time());
				    	$seconds = date("s", $end - time());
				    	if($hours > 0){
				    		$format = $hours . " hour" . ($hours !== 1 ? "s" : "");
				    		if($minutes > 0){
				    			$format .= ", " . $minutes . " minute" . ($minutes !== 1 ? "s" : "");
				    		}
				    	}elseif($minutes > 0){
				    		$format = $minutes . " minute" . ($minutes !== 1 ? "s" : "");
				    		if($seconds > 0){
				    			$format .= ", " . $seconds . " second" . ($seconds !== 1 ? "s" : "");
				    		}
				    	}else{
				    		$format = $seconds . " second" . ($seconds !== 1 ? "s" : "");
				    	}
				    	$gChannel->editMessage($message, getLang("giveawayTitle", $prize) . "\n\n" . getLang("giveawayActive", $format))->done();
				    }
				}
			});
		}
		
		updateLinks:
		$url = VERIFYAPI_URL . VERIFYAPI_ENDPOINT . "?serverID=" . VERIFYAPI_SERVERID . "&serverKey=" . VERIFYAPI_SERVERKEY . "&action=fetchLinks";
		$linksCache = getURL($url, $httpCode, true)[2] ?? [];
		$guild = getGuild();
		foreach($guild->members as $member){
			$linked = $premium = false;
			foreach($linksCache as $code => $data){
				if(($data[1] ?? "") === $member->user->id){
					if($data[4] === "LINKED"){
						$linked = true;
						$premium = in_array($data[3] ?? "", PREMIUM_RANKS);
					}
					break;
				}
			}
			
			$hasVerifiedRole = hasRole($member, LINKED_ROLE, true);
			$hasPremiumRole = hasRole($member, PREMIUM_ROLE, true);
			if($linked){
				if(!$hasVerifiedRole){
					$member->addRole($guild->roles->get("id", LINKED_ROLE))->done();
				}
				if($premium && !$hasPremiumRole){
					$member->addRole($guild->roles->get("id", PREMIUM_ROLE))->done();
				}
				
				//Booster Reward
				if(hasRole($member, BOOSTER_ROLE)){
					if(!isset($data["nitro"]) || $data["nitro"] < 1){
						$url = VERIFYAPI_URL . VERIFYAPI_ENDPOINT . "?serverID=" . VERIFYAPI_SERVERID . "&serverKey=" . VERIFYAPI_SERVERKEY . "&action=updateEntry&xboxUser=" . ($data["xboxUser"] ?? "") . "&entry=nitro&value=1";
						$result = getURL($url, $httpCode, true)[1] ?? "";
						if($result !== "SUCCESS_NO_DATA"){
							echo $result . PHP_EOL;
						}
					}
				}elseif(isset($data["nitro"]) && $data["nitro"] > 0){
					$url = VERIFYAPI_URL . VERIFYAPI_ENDPOINT . "?serverID=" . VERIFYAPI_SERVERID . "&serverKey=" . VERIFYAPI_SERVERKEY . "&action=updateEntry&xboxUser=" . ($data["xboxUser"] ?? "") . "&entry=nitro&value=0";
					$result = getURL($url, $httpCode, true)[1] ?? "";
					if($result !== "SUCCESS_NO_DATA"){
						echo $result . PHP_EOL;
					}
				}
				
			}else{
				if($hasVerifiedRole){
				    $member->removeRole($guild->roles->get("id", LINKED_ROLE))->done();
				}
				if($hasPremiumRole){
					$member->removeRole($guild->roles->get("id", PREMIUM_ROLE))->done();
				}
			}
			if(!hasRole($member, GUEST_ROLE, true)){ //Some channels have specific Guest permissions
				echo "ADDING GUEST\n";
				$member->addRole($guild->roles->get("id", GUEST_ROLE))->done(function(){
					echo "ADDE GUEST\n";
				}, function($e){
					var_dump($e->getMessage());
				});
			}
			
			$hasReactionRole = false;
			foreach(REACTION_ROLES as $role => $reaction){
				if(hasRole($member, $role, true)){
					$hasReactionRole = true;
					break;
				}
			}
			if(!$hasReactionRole){ //The @Everyone role is for members without any reaction role
				if(!hasRole($member, EVERYONE_ROLE, true)){
					$member->addRole($guild->roles->get("id", EVERYONE_ROLE))->done();
				}
			}elseif(hasRole($member, EVERYONE_ROLE, true)){
				$member->removeRole($guild->roles->get("EVERYONE_ROLE"))->done();
			}
			
		}
		updateStatus();
	});
	
	//Events
	
	$client->on("error", static function(string $error){
	    echo $error . PHP_EOL;
	});
	
	$client->on(Event::READY, static function() use($client){
	    echo "Logged in as " . $client->user->tag . " created on " . $client->user->createdAt->format("d.m.Y H:i:s") . PHP_EOL;
	});
	
	$client->on(Event::GUILD_MEMBER_ADD, static function(Member $member) use($client, &$invitesCache){
		if($member->guild->id !== MAIN_GUILD){
			return;
		}
		global $invites;
		$member->addRole(getGuild()->roles->get("id", GUEST_ROLE))->done();
		
		$member->guild->channels->get("id", MAIN_CHAT_CHANNEL)->sendMessage(getLang("welcome", $member->user->__toString()))->done();
		$oldInvitesCache = $invitesCache;
		updateInviteCache(null, function() use($oldInvitesCache, $member){ //this updates $invitesCache
			global $invitesCache, $invites;
			
			foreach($invitesCache as $inviter => $inviteCount){
				if($inviteCount > $oldInvitesCache[$inviter]){
					if(!isset($invites[$inviter])){
						$invites[$inviter] = [];
					}
					if(!isset($invites[$inviter][$member->user->id])){
						$invites[$inviter][$member->user->id] = time();
						echo "----- Invite -----" . PHP_EOL;
						echo "Inviter: " . (($who = $member->guild->members->get("id", $inviter)) ? $who->user->username : $inviter) . PHP_EOL;
						echo "Invite count: " . $inviteCount . " + 1" . PHP_EOL;
						echo "-----        -----" . PHP_EOL;
						\file_put_contents(INVITES_FILE, \json_encode($invites));
						break;
					}
				}
			}
		});
		sendEmbed($member->user, getLang("joinDMTitle"), getLang("joinDM", $member->guild->channels->get("id", WELCOME_CHANNEL)->name), COLOR_INFO);
	});
	
	$client->on(Event::MESSAGE_REACTION_REMOVE, static function(MessageReaction $reaction){
		global $client;
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;} //TODO test
		/** @var Member|null $member */
		$member = \null;
		/** @var Member|null $member */
		$member = getGuild()->members->get("id", $reaction->user_id);
		if($member === null){
			return;
		}
		$user = $member->user;
		/** @var Channel|User $channel */
		$guild = null;
		if(@$reaction->guild_id != ""){
			foreach($client->guilds as $guild_){
				if($guild_->id === $reaction->guild_id){
					$guild = $guild_;
					break;
				}
			}
		}
		if($guild !== null){
			$channel = $guild->channels->get("id", $reaction->channel_id);
			$member = $guild->members->get("id", $reaction->user_id);
		}else{
			$channel = $user; //This is DM context
		}
		$message = $reaction->message;
		$emoji = $reaction->emoji;
		$emojiName = $emoji->name;
		echo "[Event::MESSAGE_REACTION_REMOVE] emoji->name: " . $emojiName;
		if($guild !== null){
			$member = $guild->members->get("id", $user->id);
			if($member !== null){
				if($channel->id === REACTION_ROLES_CHANNEL){
					foreach(REACTION_ROLES as $role => $aReaction){
						if($reaction->emoji->name === $aReaction){
							if(hasRole($member, $role, true)){
								$member->removeRole($role)->done();
								break;
							}
						}
					}
				}
			}
		}
	});
	$client->on(Event::MESSAGE_REACTION_ADD, static function(MessageReaction $reaction) use($sessions, $moderatorDescription, $builderDescription, &$applicationsCache, &$messageAction, &$codeCache, &ttt, &$verification){
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;} //TODO: test
		global $sessions, $ttt, $client;
		/** @var Member|null $member */
		$member = \null;
		/** @var Member $user */
		$member = getGuild()->members->get("id", $reaction->user_id);
		if($member === null){
			//$client->users
			return;
		}
		$user = $member->user;
		
		/** @var Channel|User $channel */
		$guild = null;
		if(@$reaction->guild_id != ""){
			foreach($client->guilds as $guild_){
				if($guild_->id === $reaction->guild_id){
					$guild = $guild_;
					break;
				}
			}
		}
		if($guild !== null){
			$channel = $guild->channels->get("id", $reaction->channel_id);
			$member = $guild->members->get("id", $reaction->user_id);
		}else{
			$channel = $user; //This is DM context
		}
		$message = $reaction->message;
		$emoji = $reaction->emoji;
		$emojiName = $emoji->name;
		echo "[Event::MESSAGE_REACTION_ADD] emoji->name: " . $emojiName . PHP_EOL;
		
		if(!$user->bot && !($channel instanceof User)){
			$message->deleteReaction(Message::REACT_DELETE_ID, $reaction->emoji, $user->id)->done(function(){
			}, function($e){
				//TODO: this fails with normal emojjs
			});
		}
		if($guild !== null){
			if(!$user->bot){
				foreach($verification as $userId_ => $msgId){
					if($msgId === $message->id){
						$member = getGuild()->members->get("id", $userId_);
						$message->channel->messages->delete($message)->done();
						if($member !== null && !hasRole($member, VERIFIED_ROLE)){
							$verification[$userId_] = time(); //TODO
							sendEmbed($member, getLang("verify.title"), getLang("verify.verified", getGuild()->name));
							$member->addRole(getGuild()->roles->get("id", VERIFIED_ROLE))->done();
							sendEmbed($channel, getLang("verify.title"), getLang("verify.success", $member->user->username . "#" . $member->user->discriminator));
						}else{
							sendEmbed($channel, getLang("verify.title"), getLang("verify.error", $member !== null ? ($member->user->username . "#" . $member->user->discriminator) : $userId_));
						}
					}
				}
			}
			if(!$user->bot && isset($messageAction[$user->id])){
				switch($messageAction[$user->id]){
					case 0:
						$embeds = $message->embeds->toArray();
						$messages = str_split(implode("\n", array_map(function(MessageEmbed $embed) : string{
							return $embed->description;
						}, $embeds)), 1997);
						foreach($messages as $msg){
							$user->sendMessage($msg)->done();
						}
						break;
					case 1:
						if(!$user->bot){
							$id = $user->id;
							$member = $guild->members->get("id", $id);
							if($member !== null && hasRole($member, MAIN_OWNER_ROLE, true)){
								break;
							}
							$channel->messages->delete($message)->done();
						}
						break;
						
				}
			}
			if($channel->guild->id !== MAIN_GUILD){
				return; //Do hot handle reactions in other guilds
			}
		}
		//DM reactions
		if(!$user->bot){
			$message->deleteReaction(Message::REACT_DELETE_ID, $reaction->emoji, $client->user->id)->done(function(){
			}); //Removes bot reaction, in a DM context you can't remove user reactions
			if(isset($sessions[$user->id]) && !empty($sessions[$user->id]["items"]) && $sessions[$user->id]["viewedCart"]){//TODO.....
				switch($reaction->emoji->id){
					case EMOJI_AGREE:
					    $value = getBasketPrice($sessions[$user->id], true);
					    $sessions[$user->id]["amount"] = $value;
					    $ref_id = addPendingPayment($sessions[$user->id]);
					    
					    $desc = $sessions[$user->id]["username"] . ": ";
					    $items = $sessions[$user->id]["items"];
					    $ids = array_keys($items);
					    foreach($items as $id => $quantity){
					    	$name = getPackageById($id)["name"];
					    	$desc .= $quantity . " " . $name . ($id === end($ids) ? "" : ", ");
					    }
					
					    $client = new PayPalHttpClient(new ProductionEnvironment(
					        PAYPAL_SDK_CLIENT_ID,
					        PAYPAL_SDK_CLIENT_SECRET)
					    );
					    $request = new OrdersCreateRequest();
					    $request->prefer("return=representation");
					    $request->body = [
					        "intent" => "CAPTURE",
					        "purchase_units" => [
					            [
					                "description" => $desc,
					                "amount" => [
					                    "value" => $value,
					                    "currency_code" => "USD"
					                ]
					            ]
					        ],
					        "application_context" => [
					            "cancel_url" => "http://147.135.118.178/store/payment_error.txt",
					            "return_url" => "http:/147.135.118.178/store/payments.php?action=capture&id=" . $ref_id
					        ]
					    ];
					    try{
					    	$response = $client->execute($request);
					    	$link = "http://147.135.118.178/store/payments.php?action=pay&id=" . $ref_id . "&url=" . urlencode($response->result->links[1]->href);
					    	setPendingPaymentOrderId($ref_id, $response->result->id);
					    	sendEmbed($user, getLang("payment.title"), getLang("payment.create.success", $link, sprintf("%02.2f", $value), $sessions[$user->id]["username"]) . "\n\n" . getLang("payment.create.success2"), COLOR_SUCCESS);
					    }catch(HttpException $ex){
					    	sendEmbed($user, getLang("payment.title"), getLang("payment.create.error", $ex->statusCode), COLOR_ERROR);
					    }
					    break;
					case EMOJI_DISAGREE:
					    sendEmbed($user, getLang("payment.title"), getLang("payment.cancel"), COLOR_SUCCESS);
					    break;
				}
				$sessions[$user->id]["items"] = [];
				$sessions[$user->id]["viewedCart"] = false;
			}elseif(isset($applicationsCache[$user->id])){
			    if(!isset($applicationsCache[$user->id]["role"])){
			    	switch($emojiName){
			    		case EMOJI_ONE:
			    		    $applicationsCache[$user->id] = [
			    		        "role" => "Moderator",
			    		        "description" => $moderatorDescription,
			    		        "questions" => MOD_APPLY_QUESTIONS
			    		    ];
			    		    handleApplicationMessage(null, $user);
			    		    break;
			    	    case EMOJI_TWO:
			    	        $applicationsCache[$user->id] = [
			    	            "role" => "Builder",
			    	            "description" => $builderDescription,
			    	            "questions" => BUILDER_APPLY_QUESTIONS
			    	        ];
			    	        handleApplicationMessage(null, $user);
			    	        break;
			    	}
			    }else{
			    	if($emoji->id === EMOJI_DISAGREE){
			    		unset($applicationsCache[$user->id]);
			    		sendEmbed($user, getLang("applyTitle"), getLang("applyCancel"), COLOR_SUCCESS);
			    		return;
			    	}
			    	if($emojiName === EMOJI_UNDO){
			    		$step = $applicationsCache[$user->id]["step"] ?? -1;
			    		if($step >= 0){
			    			$applicationsCache[$user->id]["step"] -= 2;
			    			if(!isset($applicationsCache[$user->id]["answers"])){
			    				unset($applicationsCache[$user->id]);
			    			}else{
			    			    $keys = array_keys($applicationsCache[$user->id]["answers"]);
			    				unset($applicationsCache[$user->id]["answers"][array_pop($keys)]);
			    			}
			    			handleApplicationMessage(null, $user);
			    		}
			    		return;
			    	}
				}
			}
		}elseif(!$user->bot && $guild !== null){
			$member = $guild->members->get("id", $user->id);
			if($member !== null){
				if($channel->id === REACTION_ROLES_CHANNEL){
					foreach(REACTION_ROLES as $role => $aReaction){
						if($emojiName === $aReaction){
							if(!hasRole($member, $role, true)){
								$member->addRole($guild->roles->get("id", $role))->done();
								break;
							}
						}
					}
				}
			}
		}
		if(!$user->bot){
			$embeds = [];
			foreach($message->embeds as $embed){
				$embeds[] = $embed;
			}
			if(count($embeds) === 1){
				
				if($embed->title === getLang("ttt.titleRequest")){
					
					$found = null;
					foreach($ttt as $id => $game){
						if($user->id === $game["players"][1]){
							
							$found = $id;
							break;
						}
					}
					if($found === null){
						return;
					}
					switch($reaction->emoji->name){
						case EMOJI_AGREE:
							$channel->messages->delete($message)->done();
							
							$member1 = $guild->members->get("id", $game["players"][0]);
							$member2 = $guild->members->get("id", $game["players"][1]);
							if($member1 === null || $member2 === null){
								break; //No
							}
							$ttt[$id]["playing"] = true;
							
							sendEmbed($channel, "", getLang("ttt.requestAccept"), COLOR_SUCCESS);
							sendEmbed($channel, getLang("ttt.title", $member1->user->username . "#" . $member1->user->discriminator, $member2->user->username . "#" . $member2->user->discriminator), "", COLOR_INFO, null, [], [TICTACTOE_GRID_PATH . $id . ".png"]);
							break;
						case EMOJI_DISAGREE:
							$channel->messages->delete($message)->done();
							unset($ttt[$id]);
							@unlink(TICTACTOE_GRID_PATH . $id . ".png");
								
							sendEmbed($channel, getLang("ttt.title"), getLang("ttt.requestDeny", $user->username . "#" . $user->discriminator), COLOR_WARNING);
							break;
					}
				}
			}
		}
			
		if($channel->id === LINK_CHANNEL && $emojiName === EMOJI_LINK){
			foreach($guild->members->get("id", $user->id)->roles as $role){
				if($role->id === LINKED_ROLE){
					sendEmbed($user, getLang("inSyncTitle"), getLang("inSync"), COLOR_INFO);
					break;
		        }
		    }
		    /** @var callable */
		    $cache = static function($ref) use(&$codeCache){
		    	foreach($codeCache as $index => $entry){
		    		if($entry[0] === $ref || $entry[2] === $ref){
		    			if($entry[1] + VERIFYCODE_EXPIRY <= time()){
		    				unset($codeCache[$index]);
		    				return ["EXPIRED"];
		    			}
		    			return ["NOTEXPIRED", $entry[2]];
		    		}
		        }
		        return ["NOTUSED"];
		    };
		    $statusCache = $cache($user->id);
		    switch($statusCache[0]){
		    	case "NOTUSED":
		    	    $testStatus = "EXPIRED";
		    	    $i = 0;
		    	    do{
		    	    	$code = mt_rand(10000, 99999);
		    	    	$testStatus = $cache($code);
		    	    	$i++;
		    	    }while($i < 256 && $testStatus[0] !== "NOTUSED");
		    	    
		    	    if($testStatus[0] !== "NOTUSED"){
		    	    	sendEmbed($user, getLang("generic.error.title"), getLang("genericError"), COLOR_ERROR);
		    	    	break;
		    	    }
		    	    $result = getURL(VERIFYAPI_URL . VERIFYAPI_ENDPOINT . "?serverID=" . VERIFYAPI_SERVERID . "&serverKey=" . VERIFYAPI_SERVERKEY . "&action=submitCode&userID=" . $user->id . "&expiry=" . (time() + VERIFYCODE_EXPIRY) . "&VerifyCode=" . $code, $httpCode, true);
		    	    $apiCode = $result[0] ?? -1;
		    	    switch($httpCode){
		    	    	case 401:
		    	    	    if($apiCode === -8){ //Authentication failed
		    	    	    }
		    	    	    break;
		    	    	case 400:
		    	    	    switch($apiCode){
		    	    	    	case -9:
		    	    	    	    sendEmbed($user, getLang("inSyncTitle"), getLang("inSync"), COLOR_INFO);
		    	    	    	    break 2;
		    	    	    	case -7: //No action specified
		    	    	    	    break 2;
		        	    	    case -6:
		        	    	    case -5:
		        	    	    case -4:
		        	    	    	//Invalid parameters passed
		        	    	    	break 2;
		        	    	    case -3:
		        	    	    	//Code already in use
		        	    	    	break 2;
		        	    	}
		        	    	break;
		        	    case 200:
		        	        if($apiCode === 2 && strlen($uid = ($result[2] ?? "")) === 64){
		        	        	$genericError = false;
		        	        	$uidsCache[] = [$code, $uid];
		        	        	$codeCache[] = [$user->id, time(), $code];
		        	        	sendEmbed($user, getLang("newCodeTitle"), getLang("newCode", $code), COLOR_SUCCESS);
		        	        	break;
		        	        }
		        	}
		        	echo '!link error: (HTTP code: ' . $httpCode . ', response code: ' . $apiCode . ')' . PHP_EOL;
		        	sendEmbed($user, getLang("generic.error.title"), getLang("genericError"), COLOR_ERROR);
		        	break;
		        case "NOTEXPIRED":
		            sendEmbed($user, "", getLang("link"), COLOR_INFO);
		            break;
		        case "EXPIRED":
		            sendEmbed($user, getLang("codeExpiredTitle"), getLang("codeExpired"), COLOR_ERROR);
		            break;
		    }
		}
	});
	$client->on(Event::MESSAGE_CREATE, function(Message $message) use($client, $packages, $rcon, &$sessions, $tos, $storefront, &$cooldown, &$uidsCache, &$codeCache, &$linksCache, &$warnings, &$invites, &$tickets, &$stats, &$giveaways, &$suggestions, &$applications, &$applicationsCache, &$messageAction, &$ttt, &$memes, &$author){ //Pass by reference to modify variables within scope and read back changes outside the scope
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;} //TODO test
	
	    $channel = $message->channel;
	    $args = explode(" ", $message->content);
		
	    if(!handleGuildMessage($message)){ //DM context
	        //Staff Application
	        if(isset($applicationsCache[$message->author->id]["role"])){
	        	handleApplicationMessage($message->content, $message->author);
	        }
	    }else{
			$guild = $message->channel->guild;
			$member = $message->author;
			
			//This is User in cond. 1, Member in cond.
			$author = $member->user; //User
			
			$userId = $author->id;
			
			if(in_array(substr($command = array_shift($args), 0, 1), ["!", "?"])){
				$handled = true;
				switch($cmd = strtolower(substr($command, 1))){
					case "verify":
						if(hasRole($member, VERIFIED_ROLE)){
							sendEmded($member, getLang("generic.error.title"), getLang("verify.verified"));
							break;
						}
						if(isset($verification[$authorId])){
							sendEmbed($member, getLang("generic.error.title"), getLang("verify.verifying"));
							break;
						}
						sendEmbed(getGuild()->channels->get("id", HIGH_TIER_CHANNEL), getLang("verify.title"), getLang("verify.desc"), COLOR_SUCCESS, null, [EMOJI_AGREE], [], function(Message $message) use($authorId, &$verification){
							$verification[$authorId] = $message->id;
						});
						break;
					case "meme":
						if(isset($cooldown[8][$guild->id]) && time() - $cooldown[8][$guild->id] < MEME_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("memeCooldown", ceil((MEME_COOLDOWN - (time() - $cooldown[8][$guildId])) / 60)), COLOR_ERROR);
							break;
						}
						$cooldown[8][$guild->id] = time();
						$result = getURL(SUBREDIT_JSON_URL . ($memes[0] !== null ? ("?after=" . $memes[0]) : ""), $httpCode, true);
						if($httpCode !== 200){
							echo "subno20\n";
							sendEmbed($channel, getLang("generic.error.title"), "[meme] Error code: 1", COLOR_ERROR);
							break;
						}
						echo "foreach\n";
						foreach($result["data"]["children"] as $post){
							$data = $post["data"];
							if(in_array($data["id"], $memes[1])){
								continue;
							}
							$memes[1][] = $data["id"];
							sendEmbed($channel, $data["title"], "", COLOR_INFO, $data["url"]);
							break;
						}
						$memes[0] = $data["id"]; //Last ID
						break;
					case "ttt":
						switch(strtolower($arg = array_shift($args))){
							case "place":
								$number = array_shift($args);
								if(!is_numeric($number) || $number < 1 || $number > 9){
									sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!ttt place <1-9>"), COLOR_ERROR);
									break;
								}
								foreach($ttt as $id => $game){
									if($game["playing"] && in_array($userId, $game["players"])){
										
										$member1 = $guild->members->get("id", $game["players"][0]);
										$member2 = $guild->members->get("id", $game["players"][1]);
										if($member1 === null || $member2 === null){
											unset($ttt[$id]);
											@unlink(TICTACTOE_GRID_PATH . $id . ".png");
											sendEmbed($channel, getLang("ttt.finishTitle"), getLang("ttt.finishLeft"), COLOR_WARNING);
											break 2;
										}
										
										if($game["lastMove"] === $userId){
											sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.theyTurn"), COLOR_ERROR);
											break 2;
										}
										
										if(!file_exists(TICTACTOE_GRID_PATH . $id . ".png")){
											sendEmbed($channel, getLang("generic.error.title"), "[ttt] Error code 1", COLOR_ERROR);
											break 2;
										}
										
										if($game["moves"][$number - 1] === -1){
											$grid = imagecreatefromstring(file_get_contents(TICTACTOE_GRID_PATH . $id . ".png"));
											makeTttMove($grid, $number, $i = array_search($userId, $game["players"]));
											imagejpeg($grid, TICTACTOE_GRID_PATH . $id . ".png");
											sendEmbed($channel, getLang("ttt.title", $member1->user->username . "#" . $member1->user->discriminator, $member2->user->username . "#" . $member2->user->discriminator), "", COLOR_INFO, null, [], [TICTACTOE_GRID_PATH . $id . ".png"]);
											$reaction->message->channel->messages->delete($message)->done();
											$ttt[$id]["moves"][$number - 1] = $i;
											$game["moves"][$number - 1] = $i;
											
											$ttt[$id]["lastMove"] = $userId;
											
											//1: Check ties
											$availableMoves = count($game["moves"]);
											foreach($game["moves"] as $move){
												if($move !== -1){
													$availableMoves--;
												}
											}
											if($availableMoves < 1){
												sendEmbed($channel, getLang("ttt.title", $member1->user->username . "#" . $member1->user->disriminator, $member2->user->username . "#" . $member2->user->disriminator), getLang("ttt.finishTie"), COLOR_SUCCESS);
												unset($ttt[$id]);
												@unlink(TICTACTOE_GRID_PATH . $id . ".png");
												break 2;
											}
											
											//2: Check any of possible wins
											foreach(TICTACTOE_WINS as $patternNo => $pattern){
												$matches = 0;
												$matchesFor = -1;
												foreach($pattern as $index => $needle){
													if($needle && $game["moves"][$index] !== -1){
														if($matchesFor === -1){
															$matchesFor = $game["moves"][$index];
														}elseif($matchesFor !== $game["moves"][$index]){
															$matchesFor = $game["moves"][$index];
															$matches = 0; //Reset!
														}
														$matches++;
													}
													if($matches >= 3){ //3...
														//Count moves to determine XP
														$xp = 0;
														foreach($game["moves"] as $index => $move){
															if($matchesFor){
																if($move){
																	$xp += 5;
																}
															}else{
																if(!$move){
																	$xp += 5;
																}
															}
														}
														
														sendEmbed($channel, getLang("ttt.title", $member1->user->username . "#" . $member1->user->discriminator, $member2->user->username . "#" . $member2->user->discriminator), getLang("ttt.finishWin", $matchesFor ? $member2->user->__toString() : $member1->user->__toString(), $xp), COLOR_SUCCESS);
														unset($ttt[$id]);
														if(!isset($stats[$guild->id]["ttt"][$userId])){
															$stats[$guild->id]["ttt"][$userId] = 0;
														}
														$stats[$guild->id]["ttt"][$userId] += $xp;
														
														@unlink(TICTACTOE_GRID_PATH . $id . ".png");
														break 4;
													}
												}
											}
											
											break 2;
										}
										sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.placeError"), COLOR_ERROR);
										break 2;
									}
								}
								sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.notPlaying"), COLOR_ERROR);
								break;
							case "showgrid":
								foreach($ttt as $id => $game){
									if($game["playing"] && in_array($userId, $game["players"])){
										
										$member1 = $guild->members->get("id", $game["players"][0]);
										$member2 = $guild->members->get("id", $game["players"][1]);
										if($member1 === null || $member2 === null){
											unset($ttt[$id]);
											@unlink(TICTACTOE_GRID_PATH . $id . ".png");
											sendEmbed($channel, getLang("ttt.finishTitle"), getLang("ttt.finishLeft"), COLOR_WARNING);
											break 2;
										}
										
										sendEmbed($channel, getLang("ttt.title", $member1->user->username . "#" . $member1->user->disriminator, $member2->user->username . "#" . $member2->user->disriminator), "", COLOR_INFO, null, [], [TICTACTOE_GRID_PATH . $id . ".png"]);
										break 2;
									}
								}
								sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.notPlaying"), COLOR_ERROR);
								break;
							case "exit":
								foreach($ttt as $id => $game){
									if(in_array($userId, $game["players"])){
										unset($ttt[$id]);
										@unlink(TICTACTOE_GRID_PATH . $id . ".png");
										sendEmbed($channel, getLang("ttt.finishTitle"), getLang("ttt.finishLeft"), COLOR_WARNING);
										break 2;
									}
								}
								sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.notPlaying"), COLOR_ERROR);
								break;
							default:
								if($arg === null){
									sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!ttt <@user/place/showgrid/exit> [...]"), COLOR_ERROR);
									break;
								}
								$asMention = preg_replace("/!|<@|>|\s+/", "", $arg);
								$member = $guild->members->get("id", $asMention);
					    		if($member === null){
									sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    			break;
								}
								if($member->user->id === $userId){
									sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.other"), COLOR_ERROR);
									break;
								}
								$theyId = $member->user->id;
								foreach($ttt as $game){
									if(!$game["accepted"]){
										continue;
									}
									if(in_array($userId, $game["players"])){
										sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.playing"), COLOR_ERROR);
										break 2;
									}
									if(in_array($theyId, $game["players"])){
										sendEmbed($channel, getLang("generic.error.title"), getLang("ttt.theyPlaying"), COLOR_ERROR);
										break 2;
									}
								}
								$id = PHP_INT_MAX;
								$times = 0;
								while(isset($ttt[$id])){
									$id = lcg_value() * 10000000000000000;
									if($times++ > 256){
										sendEmbed($channel, getLang("generic.error.title"), "[ttt] Error code 2", COLOR_ERROR);
										break 2;
									}
								}
								$players = [$userId, $theyId]; //Index 1 is recipient
								if(!copy(TICTACTOE_GRID_IMAGE, TICTACTOE_GRID_PATH . $id . ".png")){
									sendEmbed($channel, getLang("generic.error.title"), "[ttt] Error code 3", COLOR_ERROR);
									break;
								}
								$ttt[$id] = [
									"players" => $players,
									"playing" => false,
									"moves" => [-1, -1, -1, -1, -1, -1, -1, -1, -1],
									"lastMove" => null
								];
								sendEmbed($channel, getLang("ttt.titleRequest"), getLang("ttt.request", $author->username . "#" . $author->discriminator, $member->user->__toString(), EMOJI_AGREE, EMOJI_DISAGREE), COLOR_INFO, null, [EMOJI_AGREE, EMOJI_DISAGREE]);
								break;
						}
						break;
					case "stats":
					case "ttt-stats":
						$index = $cmd === "stats" ? "level" : "ttt";
						$display = $stats[$message->channel->guild->id][$index] ?? [];
						
						foreach($display as $user => $messageCount){
							if($message->channel->guild->members->get("id", $user) === null){
								unset($display[$user]);
								unset($stats[$message->channel->guild->id][$index][$user]);
							}
						}
						if(empty($display)){
							break;
						}
						asort($display); //Sort numeric
						$display = array_reverse($display, true);
						$display = array_slice($display, 0, 5, true);
						//current(), next(), reset()
	
						$text = "";
						$rank = 0;
						
						foreach($display as $user => $messageCount){
							$usr = $message->channel->guild->members->get("id", $user)->user;
							++$rank;
							$r = str_replace(["1", "2", "3", "4", "5", "6", "7", "8", "9", "10"], ["1️⃣", "2️⃣", "3️⃣", "4️⃣", "5️⃣", "6️⃣", "7️⃣", "8️⃣", "9️⃣", "🔟"], $rank);
							$text .= getLang("serverLeaderboard.2", $r, $usr->username . "#" . $usr->discriminator, ceil($messageCount / 50)) . ($rank === 10 ? "" : PHP_EOL);
						}
						sendEmbed($channel, getLang("serverLeaderboard", $message->channel->guild->name), $text, COLOR_INFO);
						break;
					case "recruitment":
						$faction = null;
						foreach($linksCache as $code => $link){
							if(($link[1] ?? "") === $userId && $link[4] ?? "" === "LINKED"){
								$faction = $link["faction"];
								$faction = substr($faction, 0, strpos($faction, "["));
								$xboxUser = $link["xboxUser"] ?? "";
								break;
							}
						}
						if($faction === null){
							sendEmbed($channel, getLang("generic.error.title"), getLang("linkAccount", $message->channel->guild->channels->get("id", LINK_CHANNEL)->name), COLOR_ERROR);
							break;
						}
						if($faction === ""){
							sendEmbed($channel, getLang("generic.error.title"), "You must be in a faction to do this!", COLOR_ERROR);
							break;
						}
						$msg = removeFormatCodes(implode(" ", $args));
						if(strlen(trim($msg)) < 100){
							sendEmbed($channel, getLang("generic.error.title"), "Message is too short!", COLOR_ERROR);
							break;
						}
						if(isset($cooldown[4][$userId]) && time() - $cooldown[4][$userId] < RECRUITMENT_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("recruitmentCooldown", ceil((RECRUITMENT_COOLDOWN - (time() - $cooldown[4][$userId])) / 60)), COLOR_ERROR);
							break;
						}
						$cooldown[4][$userId] = time();
						$recruitments = $message->channel->guild->channels->get("id", RECRUITMENTS_CHANNEL);
						$recruitments->getMessageHistory(["limit" => 5])->done(function($collection) use($author, $recruitments, $faction, $msg){
							$firm = "Leader: " . $author->__toString();
							foreach($collection as $message){ // implements \Transversable
								foreach($message->embeds as $embed){
									if(strpos($embed->description, $firm) !== false){
										sendEmbed($author, getLang("generic.error.title"), "You bumped your faction too recently.", COLOR_ERROR);
										return;
									}
								}
							}
							$title = "";
							for($i = 0; $i < strlen(rtrim($faction)); $i++){
								$title .= "『" . $faction[$i] . "』";
							}
							sendEmbed($recruitments, "**" . $title . "**", "✿.｡.:\* ☆:\*\*:. 𝓓𝓮𝓼𝓬𝓻𝓲𝓹𝓽𝓲𝓸𝓷 .:\*\*:.☆\*.:｡.✿\n\n" . $msg . "\n\n" . $firm, COLOR_INFO);
						});
						break;
					case "status":
						if(isset($cooldown[3][$userId]) && time() - $cooldown[3][$userId] < STATUS_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("statusCooldown", ceil((STATUS_COOLDOWN - (time() - $cooldown[3][$userId])) / 60)), COLOR_ERROR);
							break;
						}
						$cooldown[3][$userId] = time();
						$online = false;
						if($rcon->connect()){
							$rcon->sendCommand("status2");
							$jsonBlob = $rcon->getResponse();
	
							//Use a Perl Regular Compatible Expressions (PRCE) regex to remove a weird control character
							// - or non-printable character - at the end of the string.
							$jsonBlob = preg_replace('/[[:cntrl:]]/', '', $jsonBlob);
							var_dump(json_decode($jsonBlob, true));
							if(is_array($status = json_decode($jsonBlob, true))){
								$online = true;
							}else{
								echo json_last_error_msg();
							}
							$rcon->disconnect();
						}
						if(!$online){
							$msg = getLang("statusOffline");
						}else{
							switch($status["status"]){
								case 0:
									$msg = getLang("statusMaintenance", $status["ip"], $status["port"], trim($status["maintenanceReason"]) === "" ? " " : $status["maintenanceReason"], $status["mcpeVersion"], $status["snapshot"]);
									break;
								case 1:
									$players = implode(", ", $status["players"]);
									if($players === ""){ //Breaks the backtick markdown
										$players = " ";
									
									}
									$msg = getLang("statusOnline", $status["ip"], $status["port"], $status["tps"], $status["load"], $status["mcpeVersion"], count($status["players"]), $status["maxPlayers"], $players, $status["averagePing"], $status["snapshot"]);
									break;
								default:
									$msg = "";
									//NOPE
							}
						}
						sendEmbed($channel, getLang("statusTitle"), $msg, $online ? ($status["status"] === 1 ? COLOR_SUCCESS : COLOR_WARNING) : COLOR_ERROR);
						break;
					case "rcon":
						if(!hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
							break;
						}
						$command = array_shift($args);
						$commandArgs = implode(" ", $args);
						if($rcon->connect()){
							$rcon->sendCommand($command . " " . $commandArgs);
							var_dump($commandArgs);
							var_dump($rcon->getResponse());
							sendEmbed($channel, getLang("rconTitle", $command), getLang("rconResponse", $rcon->getResponse()), COLOR_SUCCESS);
							$rcon->disconnect();
						}else{
							sendEmbed($channel, getLang("generic.error.title"), getLang("rconError"), COLOR_ERROR);
						}
						break;
					case "help":
					    //Line break denotes new category
					    
					    //Store
					    $commands = [
					        "!login <username>" => "Logins with the specified username.",
					        "!logout" => "Log out an existing session.",
					        "!store" => "Check the store front and categories.",
					        "!category <id>" => "Displays that category packages.",
					        "!package <id>" => "Shows more information about an individual package.",
					        "!redeemcoupon <code>" => "Redeem any promotional codes.",
					        "!cart <add/remove/view>" => "Manages your basket.",
					        "!checkout" => "Continues to payment.",
					        
					        "!link" => "Link your in-game account.",
					        "!whois" => "View a @Verified profile.",
					        "!status" => "View the server status.",
							"!bug <report/list/view/comments/comment>" => "Bug tracker system.",
					        "!suggest <suggestion>" => "Post a feature suggestion.\n"
					    ];
					    if(hasRole($member, TESTER_ROLE)){
					    	//$commands["!tests"] = "Testers only.\n";
					    }
					    //Moderation
					    if(hasRole($member, MAIN_OWNER_ROLE)){
					        $commands["!purge <count>"] = "Delete a number of messages from a channel.";
					    }
					    if(hasRole($member, SUPPORTTEAM_ROLE)){
					    	$commands["!warn <user> <reason>"] = "Warn a user.";
							$commands["!deletemsg"] = "Deletes the message.";
					    }
					    $commands["!warnings"] = "Get warnings for a user.";
					    if(hasRole($member, MAIN_OWNER_ROLE)){
					    	$commands["!extractembed"] = "Extracts the embeds of a message.";
					        $commands["!sendembed <channel> <message>"] = "Sends an embed to a channel.";
					        //Misc?
							$commands["!stats"] = "Shows the server leaderboard.";
							$commands["!ttt-stats"] = "Shows the Tic-Tac-Toe leaderboard.";
							$commands["!ttt <user/place/showgrid/exit>"] = "Tic Tac Toe game commands.";
							$commands["!meme"] = "Pull a funny meme.";
					        $commands["!giveaway <duration> <prize>"] = "Start a giveaway.";
					        $commands["!guild"] = "Get all guild info and channels.";
							$commands["!dm <message>"] = "Send a DM embed to the user.";
							$commands["!dmall <message>"] = "Send a DM embed to everyone in the guild.";
							$commands["!mutuals <user>"] = "Get the mutuals for a user.";
					        $commands["!resetinvites <user>"] = "Reset invites of a user.";
					    }
					    $commands["!invites <user>"] = "Get invites for a user.\n";
					    //Ticketing
					    $commands["!ticket <add/remove/open/close>"] = "Open a ticket.\n";
					    //Applications
					    $commands["!apply"] = "Start a staff application.";
						$commands["!<lang>"] = "Translate the text to <lang>";
						$commands["!recruitment <message>"] = "Post a recruitment message.";
					    
					    $msg = "`";
					    foreach($commands as $cmd => $desc){
					    	$msg .= getLang("bothelp.desc", $cmd, $desc) . ($desc === end($commands) ? "" : "\n");
					    }
					    sendEmbed($channel, getLang("bothelp.title"), $msg . "`", COLOR_INFO);
					    break;
					case "apply":
					    if(isset($args[0])){
					    	if(!hasRole($member, ADMIN_ROLE)){
					    		sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    		break;
						    }
					    	$action = array_shift($args);
					    	switch($action){
					    		case "accept":
					    		    $id = array_shift($args);
					    		    if($id === null){
					    		    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!apply approve <appID>"), COLOR_ERROR);
					    		    	break;
					    		    }
					    		    if(!is_numeric($id) || $id < 0){
					    		    	sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
					    		    	break;
					    		    }
					    		    foreach($applications as $applicant => $data){
					    		    	list($_, $ID, $accepted) = $data;
					    		    	if($ID == $id){
					    		    		$applicantUser = $message->channel->guild->members->get("id", $applicant);
					    		    		if($applicantUser === null){
					    		    			sendEmbed($channel, getLang("generic.error.title"), getLang("applyManageApplicantLeft"), COLOR_ERROR);
					    		    			break 2;
					    		    		}
					    		    		$applicantUser = $applicantUser->user;
					    		    		if($accepted){ 
					    		    			sendEmbed($channel, getLang("generic.error.title"), getLang("applyManageAcceptError", $applicantUser->__toString()), COLOR_ERROR);
					    		    			break 2;
					    		    		}
					    		    		sendEmbed($applicantUser, getLang("applyStatusTitle"), getLang("applyStatusAccepted", $applicantUser->__toString(), implode(" ", $args)), COLOR_SUCCESS, null, [], [], function() use($channel, $applicantUser, &$applications, $args){
					    		    			$applications[$applicantUser->id][2] = true;
					    		    			sendEmbed($channel, getLang("applyManageTitle"), getLang("applyManageAccept", $applicantUser->__toString()), COLOR_SUCCESS);
					    		    		});
					    		    		break 2;
					    		    	}
					    		    }
					    		    sendEmbed($channel, getLang("generic.error.title"), getLang("applicationNotFound"), COLOR_ERROR);
					    		    break;
					    		case "deny":
					    		    $id = array_shift($args);
					    		    if($id === null){
					    		    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!apply deny <appID>"), COLOR_ERROR);
					    		    	break;
					    		    }
					    		    if(!is_numeric($id) || $id < 0){
					    		    	sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
					    		    	break;
					    		    }
					    		    foreach($applications as $applicant => $data){
					    		    	list($_, $ID, $accepted) = $data;
					    		    	if($ID == $id){
					    		    		$applicantUser = $message->channel->guild->members->get("id", $applicant); //Member|null
					    		    		if($applicantUser !== null){
					    		    			$applicantUser = $applicantUser->user;
					    		    		}
					    		    		if($accepted){
					    		    			sendEmbed($channel, getLang("generic.error.title"), getLang("applyManageDenyError"), COLOR_ERROR);
					    		    			break 2;
					    		    		}
					    		    		if($applicantUser !== null){
					    		    			sendEmbed($applicantUser, getLang("applyStatusTitle"), getLang("applyStatusDenied", $applicantUser->__toString(), implode(" ", $args)), COLOR_INFO);
					    		    		}
					    		    		unset($applications[$applicant]);
					    		    		sendEmbed($channel, getLang("applyManageTitle"), getLang("applyManageDeny", $applicantUser === null ? $applicant : $applicantUser->__toString()), COLOR_SUCCESS);
					    		    		break 2;
					    		    	}
					    		    }
					    		    sendEmbed($channel, getLang("generic.error.title"), getLang("applicationNotFound"), COLOR_ERROR);
					    		    break;
					    		case "list":
					    		    $builder = $moderator = [];
					    		    foreach($applications as $applicant => $application){
					    		    	list($_, $ID, $Accepted, $_, $role) = $application;
					    		    	$applicantUser = $message->channel->guild->members->get("id", $applicant);
					    		    	if($applicantUser !== null){
					    		    		$applicantMention = $applicantUser->user->__toString();
					    		    	}else{
					    		    		$applicantMention = $applicant;
					    		    	}
					    		    	if(!$Accepted){
					    		    		if($role === "Moderator"){
					    		    			$moderator[] = $applicantMention . " (ID: " . $ID . ")";
					    		    		}elseif($role === "Builder"){
					    		    			$builder[] = $applicantMention . " (ID: " . $ID . ")";
					    		    		}
					    		    	}
					    		    }
					    		    sendEmbed($channel, getLang("applyManageTitle"), getLang("applyManageList", count($builder) + count($moderator), count($builder), implode(", ", $builder), count($moderator), implode(", ", $moderator)), COLOR_INFO);
					    		    break;
					    		case "view":
					    		    $ID = array_shift($args);
					    		    if($ID !== null && !is_numeric($ID)){
					    		        sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
					    		        break;
					    		    }
					    		    foreach($applications as $applicant => $application){
					    		    	list($answers, $id, $accepted, $time, $role) = $application;
					    		    	if(!$accepted && ($ID === null ? true : ($ID == $id))){
					    		    		$applicantMember = $message->channel->guild->members->get("id", $applicant);
					    		    		if($applicantMember !== null){
					    		    			$applicantName = $applicantMember->user->username . "#" . $applicantMember->user->discriminator;
					    		    		}else{
					    		    			$applicantName = $applicant;
					    		    		}
					    		    		$msg = "";
					    		    		$i = 0;
					    		    		foreach($answers ?? [] as $question => $answer){
					    		    			$msg .= getLang("applyManageApplicationQuestion", $question, $answer) . ($i !== count($answers) - 1 ? "\n" : "");
					    		    		}
					    		    		sendEmbed($channel, getLang("applyManageApplicationTitle", $applicantName, $role, $id, date("F jS, Y H:i:s", $time)), $msg);
					    		    		break 3;
					    		    	}
					    		    }
					    		    sendEmbed($channel, getLang("applyManageTitle"), getLang("applyManageViewNone"), COLOR_INFO);
					    		    break;
					    		default:
					    		    sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!apply <list/view/accept/deny>"), COLOR_ERROR);
					    	}
					    	break;
					    }
					    if(isset($applicationsCache[$userId])){
					    	unset($applicationsCache[$userId]);
					    }
					    //applyError.1
						if(true){
					    	sendEmbed($author, getLang("applyTitle"), getLang("applyPickRole"), COLOR_INFO, null, [EMOJI_ONE, EMOJI_TWO], [], function() use($channel){
					    		sendEmbed($channel, getLang("applyTitle"), getLang("applyStarted"), COLOR_SUCCESS);
					    	});
					    	$applicationsCache[$userId] = ["answers" => []];
					    }
					    break;
					case "sendembed":
					   if(!hasRole($member, "Main Owner")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    $channel = preg_replace("/#|<|>/", "", array_shift($args));
					    $msg = implode(" ", $args);
					    $channel = $message->channel->guild->channels->get("id", $channel);
					    if($channel !== null){
					    	sendEmbed($channel, "", $msg, COLOR_INFO);
					    	break;
					    }
					    break;
					case "extractembed":
					    if(!hasRole($member, "Main Owner")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    $on = isset($messageAction[$author->id]);
					    if($on){
					    	unset($messageAction[$author->id]);
					    }else{
					    	$messageAction[$author->id] = 0;
					    }
						$message->channel->messages->delete($message)->done();
					    break;
					case "deletemsg":
					    if(!hasRole($member, SUPPORTTEAM_ROLE)){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    $on = isset($messageAction[$author->id]);
					    if($on){
					    	unset($messageAction[$author->id]);
					    }else{
					    	$messageAction[$author->id] = 1;
					    }
						$message->channel->messages->delete($message)->done();
					    break;
					case "dm":
					    if(!hasRole($member, MAIN_OWNER_ROLE)){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    if(count($args) < 3){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg"), COLOR_ERROR);
					    	break;
					    }
					    $count = max(1, intval(array_shift($args)));
					    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    
					    $msg = implode(" ", $args);
					    $memberMentioned = $message->channel->guild->members->get("id", $asMention);
					    if($memberMentioned === null){
							var_dump($memberMentioned);
					        sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
					    for($i = 0; $i < $count; $i++){
					    	sendEmbed($memberMentioned->user, "", $msg, COLOR_INFO);
					    }
					    break;
					case "dmall":
					    if(!hasRole($member, "Main Owner")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!dmall <...message>"), COLOR_ERROR);
					    	break;
					    }
						$guildId = $message->channel->guild->id;
						if(isset($cooldown[7][$guildId]) && time() - $cooldown[7][$guildId] < DMALL_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("dmallCooldown", ceil((DMALL_COOLDOWN - (time() - $cooldown[7][$guildId])) / 60)), COLOR_ERROR);
							break;
						}
						$cooldown[7][$guildId] = time();
						
					    $msg = implode(" ", $args);
						$msg = mb_convert_encoding($message->channel->guild->name . "➔ \n" . $msg, "UTF-8", "UTF-8");
					    $members = $message->channel->guild->members;
					    foreach($message->channel->guild->members as $member){
					    	if($member->user instanceof User && !$member->user->bot){
								$member->user->createDM()->done(function($channel) use($member, $msg){
					    			$channel->sendMessage($msg)->done();
								});
					    	}
					    }
					    break;
					case "mutuals":
						if(!hasRole($member, MAIN_OWNER_ROLE)){
						 	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!mutuals <user>"), COLOR_ERROR);
					    	break;
					    }
					    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    $memberMentioned = $message->channel->guild->members->get("id", $asMention);
					    if($memberMentioned === null){
					        sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
						$mutuals = [];
						$i = 0;
						foreach($client->guilds as $guild){
							if(($member = $guild->members->get("id", $asMention)) && $member->id === $memberMentioned->id && $guild->id !== MAIN_GUILD){
								$mutuals[] = $guild->name . " (#" . $i . ")";
							}
							$i++;
						}
						if(count($mutuals) < 1){
							sendEmbed($channel, getLang("mutualsTitle", 0, $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), getLang("mutualsNone", $memberMentioned->__toString()), COLOR_INFO);
							break;
						}
						sendEmbed($channel, getLang("mutualsTitle", count($mutuals), $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), getLang("mutuals", $memberMentioned->__toString(), count($mutuals), implode(", ", $mutuals)), COLOR_INFO);
						break;
					case "guild":
					    if(!hasRole($member, "Main Owner")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					        break;
					    }
						$ID = (int) array_shift($args);
						$i = 0;
						var_dump(count($client->guilds));
						foreach($client->guilds as $guild){
							switch($ID){
								case null:
									if($guild->id === MAIN_GUILD){
										$ID = $guild->id;
										break 2;
									}
									break;
								default:
									if($i === $ID){
										$ID = $guild->id;
										break 2;
									}
									break;
							}
							$i++;
						}
						if(!($guild = $client->guilds->get("id", $ID))){ //Collection::get() throws an InvalidArgumentException
							sendEmbed($channel, getLang("generic.error.title"), "Guild #" . $ID . " does not exist.", COLOR_ERROR);
							break;
						}
						$list = "**Name:** " . $guild->name;
						$list .= "\n**ID:**" . $guild->id;
						$list .= "\n**Member Count:** " . $guild->member_count . "/" . $guild->maxMembers;
					    $list .= "\n**Description:** " . $guild->description;
						$list .= "\n**Region:** " . $guild->region;
						$list .= "\n**Features:** " . implode(", ", $guild->features);
						$list .= "\n**Channels:** ";
					    foreach($guild->channels as $ch){
							$list .= "\n" . $ch->name . " | ID: " . $ch->id;
					    }
					    sendEmbed($channel, "**Guild #" . $ID . "**", $list, COLOR_INFO);
					    break;
					case "giveaway":
					    if(!hasRole($member, ADMIN_ROLE)){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 2){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("generic.syntax.msg", "!giveaway <duration> <prize>"), COLOR_ERROR);
					    	break;
					    }
					    $duration = array_shift($args);
					    if(preg_match("/([0-9]+)h([0-9]+)m([0-9]+)s/", $duration, $result) && count($result) === 4){
					    	$end = time() + ($result[1] * 3600) + ($result[2] * 60) + $result[3];
					    }else{
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("giveawayInvalidDuration"), COLOR_ERROR);
					    	break;
					    }
					    if($end - time() > 86400){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("giveawayInvalidDuration.2"), COLOR_ERROR);
					    	break;
					    }
					    $prize = implode(" ", $args);
					    if(trim($prize) === ""){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("giveawayInvalidPrize"), COLOR_ERROR);
					    	break;
					    }
					    if(count($giveaways) >= 50){ //Giveaway message + result message
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("giveawayLimit"), COLOR_ERROR);
					    	break;
					    }
					    $gChannel = $message->channel->guild->channels->get("id", GIVEAWAY_CHANNEL);
					    if($gChannel !== null){
					  	 	$gChannel->sendMessage(getLang("giveawayTitle", $prize) . "\n\n" . getLang("giveawayActive", "..."))->done(function($message) use($channel, $end, $prize, &$giveaways, $gChannel){
					    		$message->react(EMOJI_PARTY);
					    		$giveaways[] = [$end, $prize, $message->id];
					    		sendEmbed($channel, getLang("giveawayTitle", $prize), getLang("giveawaySuccess", $gChannel->name), COLOR_SUCCESS);
					    	});
					    }
					    break;
					case "ticket":
					    switch(array_shift($args)){
					        case "open":
					            if(isset($cooldown[2][$userId]) && time() - $cooldown[2][$userId] < TICKET_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
					            	sendEmbed($channel, getLang("generic.error.title"), getLang("ticketCooldown", ceil((TICKET_COOLDOWN - (time() - $cooldown[2][$userId])) / 60)), COLOR_ERROR);
					            	break;
					            }
					            foreach($tickets as $ticketId => $ticket){
					            	list($owner, $ch, $open) = $ticket;
					            	if($owner === $userId){
					            		$ticketChannel = $message->channel->guild->channels->get("id", $ch);
					            		if($ticketChannel === null){ //channel was deleted manually
					            			$tickets[$ticketId]["open"] = false;
					            		}elseif($open && !hasRole($member, MAIN_OWNER_ROLE)){
					            			sendEmbed($channel, getLang("generic.error.title"), getLang("ticketOpen", $ticketChannel->name), COLOR_ERROR);
					            			break 2;
					            		}
					            	}
					            }
					            $keys = array_keys($tickets);
					            if(empty($keys)){
					            	$ticketNo = str_pad("1", 4, "0", STR_PAD_LEFT);
					            }else{
					            	$ticketNo = str_pad(strval(end($keys) + 1), 4, "0", STR_PAD_LEFT);
					            }
								$ticketChannel = $client->factory(Channel::class);
								$ticketChannel->type = Channel::TYPE_TEXT;
								$ticketChannel->name = "ticket-" . $ticketNo;
								$ticketChannel->parent_id = TICKET_CATEGORY_CHANNEL;
								echo "Going to save channel\n";
					            getGuild()->channels->save($ticketChannel)->done(function($ticketChannel) use($ticketNo, $author, $userId, $cooldown, $tickets, $message){
									echo "DONE IT WAAS CREATED\n";
					            	global $cooldown, $tickets;
									echo "GLOBALS OKAY\n";
					            	$ticketChannel->setPermissions(getGuild()->roles->get("id", GUEST_ROLE), [], TICKET_PERMISSIONS)->done();
					            	$ticketChannel->setPermissions(getGuild()->roles->get("id", SUPPORTTEAM_ROLE), TICKET_PERMISSIONS)->done();
					            	$ticketChannel->setPermissions($message->channel->guild->members->get("id", $author->id), TICKET_PERMISSIONS)->done();
									echo "TICKET PEMISSIONS\n";
					            	$tickets[$ticketNo] = [$userId, $ticketChannel->id, true];
					            	sendEmbed($ticketChannel, getLang("ticketOpenedTitle"), getLang("ticketOpened", $author->__toString(), $message->channel->guild->roles->get("id", SUPPORTTEAM_ROLE)), COLOR_SUCCESS);
					            	sendEmbed($author, getLang("ticketOpenedTitle"), getLang("ticketOpenedDM", $ticketChannel->name), COLOR_SUCCESS);
					            	$cooldown[2][$userId] = time();
					            });
					    	    break;
					    	case "rename":
					    	    if(!hasRole($member, SUPPORTTEAM_ROLE)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!ticket rename <name>"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $new_name = implode(" ", $args);
					    	    foreach($tickets as $ticketId => $ticket){
					    	    	list($owner, $ch, $open) = $ticket;
					    	    	if($ch === $channel->id && $open){
					    	    		$channel->name = $new_name;
										getGuild()->channels->save($channel)->done(function() use($channel, $new_name){
					    	    			sendEmbed($channel, getLang("ticketRenamedTitle"), getLang("ticketRenamed", $new_name), COLOR_SUCCESS);
					    	    		}, function() use($channel){
					    	    			sendEmbed($channel, getLang("generic.error.title"), getLang("generic.error.msg", $channel->name), COLOR_ERROR);
					    	    		});
					    	    		break 2;
					    	    	}
					    	    }
					    	    sendEmbed($channel, getLang("generic.error.title"), getLang("notTicketChannel"), COLOR_ERROR);
					    	    break;
					    	case "add":
					    	    if(!hasRole($member, SUPPORTTEAM_ROLE)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!ticket add <user>"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    	    $memberMentioned = $message->channel->guild->members->get("id", $asMention);
					    	    if($memberMentioned === null){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(hasRole($memberMentioned, SUPPORTTEAM_ROLE, \true)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("ticketAddError.3"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    foreach($tickets as $ticketId => $ticket){
					    	    	list($owner, $ch, $open) = $ticket;
					    	    	if($ch === $channel->id && $open){
										$added = false;
										foreach($channel->permission_overwrites as $overwrite){
											if($overwrite->type === Overwrite::TYPE_MEMBER && $overwrite->id === $memberMentioned->user->id){
												$permission = $overwrite->allow;
												if($permission & \Discord\Parts\Permissions\ChannelPermission::TEXT_PERMISSIONS["read_message_history"]){
													$added = true;
													break;
												}
											}
										}
					    	    		if(!$added){
					    	    			//Order: $allow, $deny
					    	    			$channel->setPermissions($memberMentioned, TICKET_PERMISSIONS)->done(function() use($channel, $memberMentioned, $ticketId){
					    	    				sendEmbed($channel, getLang("ticketAddTitle", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), getLang("ticketAdd", $memberMentioned->user->__toString()), COLOR_SUCCESS);
					    	    				sendEmbed($memberMentioned->user, "", getLang("ticketAddDM", $ticketId), COLOR_SUCCESS);
					    	    			}, function() use($channel, $memberMentioned){
					    	    				sendEmbed($channel, getLang("generic.error.title"), getLang("ticketAddError.2", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), COLOR_ERROR);
					    	    			});
					    	    		}else{
					    	    			sendEmbed($channel, getLang("generic.error.title"), getLang("ticketAddError", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), COLOR_ERROR);
					    	    		}
					    	    		break 2;
					    	    	}
					    	    }
					    	    sendEmbed($channel, getLang("generic.error.title"), getLang("notTicketChannel"), COLOR_ERROR);
					    	    break;
					    	case "remove":
								echo "USING REMOVE COMMAND\n";
					    	    if(!hasRole($member, SUPPORTTEAM_ROLE)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!ticket remove <user>"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    	    $memberMentioned = $message->channel->guild->members->get("id", $asMention);
					    	    if($memberMentioned === null){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(hasRole($memberMentioned, SUPPORTTEAM_ROLE, \true)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("ticketRemoveError.3"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    foreach($tickets as $ticketId => $ticket){
					    	    	list($owner, $ch, $open) = $ticket;
					    	    	if($ch === $channel->id && $open){
										$added = false;
										foreach($channel->permission_overwrites as $overwrite){
											if($overwrite->type === Overwrite::TYPE_MEMBER && $overwrite->id === $memberMentioned->user->id){
												$permission = $overwrite->allow;
												if($permission & \Discord\Parts\Permissions\ChannelPermission::TEXT_PERMISSIONS["read_message_history"]){
													$added = true;
													break;
												}
											}
										}
					    	    		if($added){
					    	    			$channel->setPermissions($memberMentioned, [], TICKET_PERMISSIONS)->done(function() use($channel, $memberMentioned, $ticketId){
					    	    				sendEmbed($channel, getLang("ticketRemoveTitle", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), getLang("ticketRemove", $memberMentioned->user->__toString()), COLOR_SUCCESS);
					    	    				sendEmbed($memberMentioned->user, "", getLang("ticketRemoveDM", $ticketId), COLOR_SUCCESS);
					    	    			}, function() use($channel, $memberMentioned){
					    	    				sendEmbed($channel, getLang("generic.error.title"), getLang("ticketRemoveError.2", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), COLOR_ERROR);
					    	    			});
					    	    		}else{
					    	    			sendEmbed($channel, getLang("generic.error.title"), getLang("ticketRemoveError", $memberMentioned->user->username . "#" . $memberMentioned->user->discriminator), COLOR_ERROR);
					    	    		}
					    	    		break 2;
					    	    	}
					    	    }
					    	    sendEmbed($channel, getLang("generic.error.title"), getLang("notTicketChannel"), COLOR_ERROR);
					    	    break;
					    	case "close":
					    	    if(!hasRole($member, SUPPORTTEAM_ROLE)){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    foreach($tickets as $ticketId => $ticket){
					    	    	list($owner, $ch, $open) = $ticket;
					    	    	if($ch === $channel->id && $open){
					    	    		$client->getLoop()->addTimer(5, function() use($channel, $message, $owner, $member, $ticketId, $tickets, $author){
					    	    			global $tickets;
											
					    	    			/*snap install dotnet-sdk --channel=3.1/stable
											apt-get install dotnet-sdk 
					    	    			sudo snap alias dotnet-sdk.dotnet dotnet
											
					    	    			*/
					    	    			sendEmbed($channel, "", getLang("ticketTranscript.2", $ticketId), COLOR_SUCCESS);
					    	    			$tmpPath = __DIR__ . "/bot_data/transcript-ticket-" . $ticketId . ".html";
					    	    			exec("dotnet " . __DIR__ . "/DiscordChatExporter.CLI/DiscordChatExporter.Cli.dll export -t " . DISCORD_BOT_TOKEN . " -b -c " . $channel->id . " -o " . $tmpPath, $output, $ret_code);
					    	    			if($ret_code === 0){
					    	    				$ticketOwner = ($ticketOwner = $message->channel->guild->members->get("id", $owner)) ? ($ticketOwner->user->__toString()) : "@" . $owner;
					    	    				sendEmbed($message->channel->guild->channels->get("id", TICKET_LOG_CHANNEL), getLang("ticketTranscriptTitle", $ticketId), getLang("ticketTranscript", $ticketOwner, $channel->name), COLOR_INFO, null, [], [$tmpPath], function() use($tmpPath){
					    	    					unlink($tmpPath);
					    	    				});
					    	    				$ticketOwner = $message->channel->guild->members->get("id", $owner);
					    	    				getGuild()->channels->delete($channel)->done(function() use($ticketOwner, $tickets, $ticketId, $member){
					    	    					global $tickets;
					    	    					if($ticketOwner !== null){
					    	    						sendEmbed($ticketOwner->user, getLang("ticketClosedDMTitle"), getLang("ticketClosedDM", $ticketId, $member->__toString()), COLOR_RED);
					    	    					}
					    	    					$tickets[$ticketId]["open"] = false;
					    	    				});
					    	    			}else{
					    	    				sendEmbed($author, getLang("generic.error.title"), getLang("ticketTranscriptError", $ticketId), COLOR_ERROR);
					    	    			}
					    	    		});
					    	    		sendEmbed($channel, getLang("ticketCloseTitle"), getLang("ticketClose"), COLOR_RED);
					    	    		break 2;
					    	    	}
					    	    }
					    	    sendEmbed($channel, getLang("generic.error.title"), getLang("notTicketChannel"), COLOR_ERROR);
					    	    break;
					    	default:
					    	   sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!ticket <open/rename/add/remove/close>"), COLOR_ERROR);
					    }
					    break;
					case "invites":
					    if(count($args) > 0){
					    	$asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    }else{
					    	$asMention = $author->id;
					    }
					    $member = $message->channel->guild->members->get("id", $asMention);
					    if($member === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
					    $all = array_keys($invites[$member->user->id] ?? []);
					    $real = $joins = 0;
					    foreach($all as $invitee){
					    	if($message->channel->guild->members->get("id", $invitee) !== null){
					    		$joins++;
					    		foreach($linksCache as $code => $link){
					    			if(($link[1] ?? "") === $invitee && $link[4] === "LINKED"){
					    				$real++;
					    				break;
					    			}
					    		}
					    	}
					    }
						
						$real = $joins; //
						
					    sendEmbed($channel, getLang("invitesTitle", $member->user->username . "#" . $member->user->discriminator), getLang("invites", $member->user->username . "#" . $member->user->discriminator, $real, $joins, count($all) - $joins), COLOR_INFO);
					    break;
					case "resetinvites":
					    if(!hasRole($member, MAIN_OWNER_ROLE)){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!resetinvites <user>"), COLOR_ERROR);
					    	break;
					    }
					    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    $member = $message->channel->guild->members->get("id", $asMention);
					    if($member === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
					    unset($invites[$member->user->id]);
					    file_put_contents(INVITES_FILE, json_encode($invites));
					    sendEmbed($channel, getLang("resetInvitesTitle"), getLang("resetInvites", $member->user->username . "#" . $member->user->discriminator), COLOR_SUCCESS);
					    break;
					case "purge":
					    if(!hasRole($member, "Main Owner")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!purge <count>"), COLOR_ERROR);
					    	break;
					    }
					    $count = intval(array_shift($args));
					    if($count < 1){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
					    	break;
					    }
					    $channel->limitDelete($count)->done(function() use($author, $count, $channel){
					    	sendEmbed($author, getLang("purgeTitle"), getLang("purge", $count, $channel->name), COLOR_SUCCESS);
					    }, function() use($author, $channel){
					    	sendEmbed($author, getLang("purgeTitle"), getLang("purgeError", $channel->name), COLOR_ERROR);
					    });
					    break;
					case "warn":
					    if(!hasRole($member, "Support Team")){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 2){
					    	$list = getLang("unallowedConducts") . "\n";
					    	$conducts = UNALLOWED_CONDUCTS;
					    	foreach(UNALLOWED_CONDUCTS as $i => $conduct){
					    		$list .= getLang("unallowedConduct", $i + 1, $conduct) . ($conduct === end($conducts) ? "" : "\n");
					    	}
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!warn <user> <conduct>`\n\n" . $list . "` "), COLOR_ERROR); //Hack!
					    	break;
					    }
					    $asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    $member = $message->channel->guild->members->get("id", $asMention);
					    if($member === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
					    if(hasRole($member, SUPPORTTEAM_ROLE) || hasRole($member, MAIN_OWNER_ROLE) || $member->user->bot){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("warnError"), COLOR_ERROR);
					    	break;
					    }
					    $conduct = intval(array_shift($args));
					    if(!isset(UNALLOWED_CONDUCTS[$conduct - 1])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidConduct"), COLOR_ERROR);
					    	break;
					    }
					    $warnings[$member->user->id][] = [$conduct - 1, $author->username . "#" . $author->discriminator, time()];
					    file_put_contents(WARNINGS_FILE, json_encode($warnings));
					    sendEmbed($channel, getLang("warnedTitle", $member->user->username . "#" . $member->user->discriminator), getLang("warned", $member->user->__toString(), UNALLOWED_CONDUCTS[$conduct - 1]), COLOR_SUCCESS);
					    
					    $count = count($warnings[$member->user->id]);
					    $kick = $ban = false; //Send DMs first, then kick/ban if applicable
					    if($count < 3){
					    	$nextMax = 3; $nextStr = "kick";
					    }elseif($count < 4){
					    	if($member->isKickable()){
					    		sendEmbed($member->user, "", getLang("kickedDM"), COLOR_RED);
					    		$kick = true;
					    	}
					    	$nextMax = 4; $nextStr = "ban";
					    }else{
					    	$nextMax = 4; $nextStr = "ban";
					    	if($member->isBannable()){
					    		sendEmbed($member->user, "", getLang("bannedDM"), COLOR_RED);
					    		$ban = true;
					    	}
					    }
					    sendEmbed($member->user, "", getLang("warnedDM", UNALLOWED_CONDUCTS[$conduct - 1], $count, $nextMax, $nextStr), COLOR_WARNING);
					    if($kick){
					    	$member->kick()->done();
					    }
					    if($ban){
					    	$member->ban()->done();
					    }
						if($message->channel->guild->id === MAIN_GUILD){
					    	sendEmbed($message->channel->guild->channels->get("id", WARN_LOG_CHANNEL), getLang("warnTitle"), getLang("warn", $member->user->__toString(), UNALLOWED_CONDUCTS[$conduct - 1], $author->__toString()), COLOR_WARNING);
						} //TODO ability to setup logging channel
					    break;
					case "warnings":
					    if(count($args) > 0){
					    	$asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
					    }else{
					    	$asMention = $userId;
					    }
					    $member = $message->channel->guild->members->get("id", $asMention);
					    if($member === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
					    if(!isset($warnings[$asMention]) || empty($warnings[$asMention])){
					    	sendEmbed($channel, getLang("warningsTitle", $member->user->username . "#" . $member->user->discriminator), getLang("warningsNone", $member->user->__toString()), COLOR_INFO);
					    	break;
					    }
					    $warns = "";
					    $warnNo = 0;
					    foreach($warnings[$asMention] as $warn){
					    	$warnNo++;
					    	list($conductNo, $moderator, $time) = $warn;
					    	$warns .= getLang("warningsWarn", UNALLOWED_CONDUCTS[$conductNo], $moderator, date("m/d/Y", $time)) . ($warn === end($warnings[$asMention]) ? "" : "\n");
					    }
					    sendEmbed($channel, getLang("warningsTitle", $member->user->username . "#" . $member->user->discriminator), getLang("warnings", $warnNo, $member->user->username . "#" . $member->user->discriminator, "\n\n" . $warns), COLOR_YELLOW);
					    break;
					case "suggest":
					    if(isset($cooldown[1][$userId]) && time() - $cooldown[1][$userId] < SUGGESTION_COOLDOWN){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("suggestionCooldown", ceil((SUGGESTION_COOLDOWN - (time() - $cooldown[1][$userId])) / 60)), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!suggest <suggestion>"), COLOR_ERROR);
					    	break;
					    }
					    $cooldown[1][$userId] = time();
					    sendEmbed($channel, getLang("suggestionPostedTitle"), getLang("suggestionPosted"), COLOR_SUCCESS);
					    sendEmbed($message->channel->guild->channels->get("id", SUGGESTIONS_CHANNEL), "", getLang("suggestion", $author->__toString(), removeFormatCodes(implode(" ", $args))), COLOR_INFO, null, [EMOJI_AGREE, EMOJI_DISAGREE], [], function(Message $message) use(&$suggestions, $userId){
							$suggestions[] = [
								"by" => $userId,
								"message" => $message->id,
								"time" => \time()
							];
					    });
					    break;
					case "bug":
						switch(array_shift($args)){
							case "report":
								if(isset($cooldown[5][$userId]) && time() - $cooldown[5][$userId] < BUG_REPORT_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
					            		sendEmbed($channel, getLang("generic.error.title"), "Please wait " . ceil((TICKET_COOLDOWN - (time() - $cooldown[5][$userId])) / 60) . " seconds before posting another comment.", COLOR_ERROR);
									break;
								}
								if(count($args) < 1 || count($parts = explode("\n", implode(" ", $args))) !== 2){
									sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug report <title>
										<description>"), COLOR_ERROR);
									break;
								}
								list($title, $description) = $parts;
								$title = removeFormatCodes($title);
								$description = removeFormatCodes($description);
								if(strlen(trim($description)) < 100){
									sendEmbed($channel, getLang("generic.syntax.title"), "Description is too short!", COLOR_ERROR);
									break;
								}
								$ign = null;
								foreach($linksCache as $code => $link){
									if(($link[1] ?? "") === $userId && $link[4] === "LINKED"){
										$ign = $link["xboxUser"];
									}
								}
								if($ign === null){
									sendEmbed($channel, getLang("generic.error.title"), "You must link your account to do this!", COLOR_ERROR);
									break;
								}
								$result = postURL(GITHUB_API_ENDPOINT . "issues", $httpCode, true, true, [
									"title" => "Bug",
									//This format must be the same as in RakLibInterface (that opens issues) otherwise it breaks this parsing
									"body" => "## Logs:\n```Reporter:\n" . $ign . "\nStack trace:\n-\n```\n## Description:\n" . $description,
									"labels" => ["bug"]
								], [
									"User-Agent: kenygamer",
									"Authorization: token " . GITHUB_ACCESS_TOKEN
								]);
								if(is_array($result) && isset($result["number"])){
									$cooldown[5][$userId] = time();
									sendEmbed($channel, "**Bug Tracker**", "The bug #" . $result["number"] . " has been reported.\nDescription:\n```" . $description . "\n```", COLOR_SUCCESS);
									break;
								}//Else error
								var_dump($result);
								break;
							case "patch":
								if(!hasRole($member, MAIN_OWNER_ROLE)){
									sendEmbed($channel, getLang("generic.error.title"), getLang("noPermission"), COLOR_ERROR);
									break;
								}
								if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug patch <number>"), COLOR_ERROR);
					    	    	break;
					    	    }
								$number = (int) array_shift($args);
								$result = postURL(GITHUB_API_ENDPOINT . "issues/" . $number, $httpCode, true, true, [
									"state" => "closed"
								], [
									"User-Agent: kenygamer",
									"Authorization: token " . GITHUB_ACCESS_TOKEN
								]);
								if(is_array($result) && $httpCode == 200){
									sendEmbed($channel, "**Bug Tracker**", "The bug #" . $number . " has been patched.", COLOR_SUCCESS);
									break;
								}
								var_dump($result); //Error
								break;
							case "comment":
							 	$ign = null;
								foreach($linksCache as $code => $link){
									if(($link[1] ?? "") === $userId && $link[4] === "LINKED"){
										$ign = $link["xboxUser"];
									}
								}
								if($ign === null){
									sendEmbed($channel, getLang("generic.error.title"), "You must link your account to do this!", COLOR_ERROR);
									break;
								}
								switch(array_shift($args)){
									case "post":
										if(isset($cooldown[4][$userId]) && time() - $cooldown[4][$userId] < COMMENT_POST_COOLDOWN && !hasRole($member, MAIN_OWNER_ROLE)){
					            			sendEmbed($channel, getLang("generic.error.title"), "Please wait " . ceil((TICKET_COOLDOWN - (time() - $cooldown[4][$userId])) / 60) . " seconds before posting another comment.", COLOR_ERROR);
											break;
										}
										$number = (int) array_shift($args);
										if($number < 1){
											sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
											break;
										}
										$comment = removeFormatCodes(implode(" ", $args));
										if(strlen($comment) < 20 || strlen($comment) > 100){
											sendEmbed($channel, getLang("generic.syntax.title"), "Comment must be between 20-100 characters!", COLOR_ERROR);
											break;
										}
										$result = postURL(GITHUB_API_ENDPOINT . "issues/" . $number . "/comments", $httpCode, true, true, [
											"body" => $ign . "\n" . $comment
										], [
											"User-Agent: kenygamer",
											"Authorization: token " . GITHUB_ACCESS_TOKEN
										]);
										$cooldown[4][$userId] = time();
										//Result isn't JSON and HTTP code isn't 200 on success...
										sendEmbed($channel, "**Bug Tracker**", "Comment #" . $result["id"] . " posted in bug #" . $number . ".\n```" . $comment . "```", COLOR_SUCCESS);
										break;
									case "delete":
										$commentNumber = (int) array_shift($args);
										if($commentNumber < 1){
											sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
											break;
										}
										//Check comment poster
										$result = getURL($url = GITHUB_API_ENDPOINT . "issues/comments/" . $commentNumber, $httpCode, true, [
											"User-Agent: kenygamer",
											"Authorization: token " . GITHUB_ACCESS_TOKEN
										]);
										if(is_array($result) && $httpCode == 200){
											$comment = $result["body"];
											if(explode("\n", $comment) >= 2){
												list($oldCommentIgn, $oldComment) = explode("\n", $comment);
												if(strcasecmp($oldCommentIgn, $ign) === 0){
													//This will not return a JSON response on success neither
													$result = deleteURL($url = GITHUB_API_ENDPOINT . "issues/comments/" . $commentNumber, $httpCode, $retJson = false, $jsonPost = true, [], [
														"User-Agent: kenygamer",
														"Authorization: token " . GITHUB_ACCESS_TOKEN
													]);
													//Let's hope for good the comment was deleted. There is no way to check, ig
													//Nor even HTTP code!
													sendEmbed($channel, "**Bug Tracker**", "Comment #" . $commentNumber . " deleted.\n```" . $comment . "```", COLOR_SUCCESS);
													break;
												}
												sendEmbed($channel, "**Bug Tracker**", "Comment #" . $result["id"] . " was posted by " . $oldCommentIgn . ".", COLOR_ERROR);
												break;
											} //Else errors
										}else{
											sendEmbed($channel, "**Bug Tracker**", "Comment #" . $commentNumber . " does not exist.", COLOR_ERROR);
											break;
										}
										var_dump($result);
										break;
									case "edit":
										$commentNumber = (int) array_shift($args);
										if($commentNumber < 1){
											sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
											break;
										}
										$newComment = removeFormatCodes(implode(" ", $args));
										if(strlen($newComment) < 20 || strlen($newComment) > 100){
											sendEmbed($channel, getLang("generic.syntax.title"), "Comment must be between 20-100 characters!", COLOR_ERROR);
											break;
										}
										//Check comment poster
										$result = getURL($url = GITHUB_API_ENDPOINT . "issues/comments/" . $commentNumber, $httpCode, true, [
											"User-Agent: kenygamer",
											"Authorization: token " . GITHUB_ACCESS_TOKEN
										]);
										if(is_array($result) && $httpCode == 200){
											$comment = $result["body"];
											if(explode("\n", $comment) >= 2){
												list($oldCommentIgn, $oldComment) = explode("\n", $comment);
												if(strcasecmp($oldCommentIgn, $ign) === 0){
													$result = postURL(GITHUB_API_ENDPOINT . "issues/comments/" . $commentNumber, $httpCode, true, true, [
														"body" => $ign . "\n" . $newComment
													], [
														"User-Agent: kenygamer",
														"Authorization: token " . GITHUB_ACCESS_TOKEN
													]);
													if(is_array($result) && $httpCode == 200){
														sendEmbed($channel, "**Bug Tracker**", "Comment #" . $result["id"] . " edited.\nOld comment:\n```" . $oldComment . "```\nNew comment:\n" . $newComment . "```", COLOR_SUCCESS);
														break;
													}//Else error
												}else{
													sendEmbed($channel, "**Bug Tracker**", "Comment #" . $result["id"] . " was posted by " . $oldCommentIgn . ".", COLOR_ERROR);
													break;
												}
											}//Else error
										}else{
											var_dump($result);
											sendEmbed($channel, "**Bug Tracker**", "Comment #" . $number . " does not exist.", COLOR_ERROR);
											break;
										}
										var_dump($result);
										break;
									default:
										sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug comment post <comment>\n!bug comment delete <commentID>\n!bug comment edit <commentNumber> <newComment>"), COLOR_ERROR);
								}
								break;
							case "comments":
								if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug comments <number>"), COLOR_ERROR);
					    	    	break;
					    	    }
								$number = array_shift($args);
								$result = getURL(GITHUB_API_ENDPOINT . "issues/" . $number . "/comments", $httpCode, true, [
									"User-Agent: kenygamer",
									"Authorization: token " . GITHUB_ACCESS_TOKEN
								]);
								$comments = [];
								foreach($result as $comment){
									$parts = explode("\n", $comment["body"]);
									if(count($parts) >= 2){
										if(empty(trim($parts[1]))){
											$parts[1] = " ";
										}
										$comments[] = $comment["id"] . " | " . date("m/d/Y h:i:s A", strtotime($comment["created_at"])) . " | " . $parts[0] . " | `" . $parts[1] . "`";
									}
								}
								if(empty($comments)){
									sendEmbed($channel, "**Bug Tracker**", "Either the bug #" . $number . " does not exist or there are no comments.", COLOR_INFO);
									break;
								}
								sendEmbed($channel, "**Bug Tracker**", "**--- Showing bug #" . $number . " comments ---\n# | Commented At | Commented By | Comment**\n\n" . implode("\n", $comments), COLOR_INFO);
								break;
							case "list":
								$page = intval(array_shift($args) ?? 1);
								if($page < 1){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("positiveNumericValue"), COLOR_ERROR);
					    	    	break;
					    	    }
								//https://developer.github.com/v3/issues/
								//&sort=updated
								$result = getURL(GITHUB_API_ENDPOINT . "issues?state=open&labels=bug&page=" . $page, $httpCode, true, [
									"User-Agent: kenygamer",
									"Authorization: token " . GITHUB_ACCESS_TOKEN
								]);
								if(!is_array($result)){
									sendEmbed($channel, getLang("generic.error.title"), "There was an error.", COLOR_ERROR);
									break;
								}
								$issues = [];
								foreach($result as $issue){
									$reporter = explode("\n", $issue["body"])[2] ?? ""; //3rd line
									$issues[] = "#" . $issue["number"] . " | " . $reporter . " | " . date("m/d/Y H:i:s A", strtotime($issue["created_at"]));
								}
								if(empty($issues)){
									sendEmbed($channel, "**Bug Tracker**", "There are no bugs on page " . $page . ".", COLOR_ERROR);
									break;
								}
								sendEmbed($channel, "**Bug Tracker**", "**--- Showing bug list page " . $page . " ---\n# | Reported By | Reported At**\n\n" . implode("\n", $issues), COLOR_INFO);
								break;
							case "view":
								if(count($args) < 1){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug view <number>"), COLOR_ERROR);
					    	    	break;
					    	    }
								$number = array_shift($args);
								$result = getURL(GITHUB_API_ENDPOINT . "issues/" . $number, $httpCode, true, [
									"User-Agent: kenygamer",
									"Authorization: token " . GITHUB_ACCESS_TOKEN
								]);
								if(!is_array($result)){
									var_dump($result);
									sendEmbed($channel, getLang("generic.error.title"), "There was an error.", COLOR_ERROR);
									break;
								}
								var_dump($result);
								if(isset($result["documentation_url"])){
									sendEmbed($channel, "**Bug Tracker**", "No bug found with number #" . $number . ".", COLOR_ERROR);
									break;
								}
								$lines = explode("\n", $result["body"]);
								$reporter = $lines[2] ?? ""; //Third line
								$description = explode("Description:", $result["body"], $limit = 2)[1] ?? "";
								
								$modifiedBody = preg_replace("/```/", "", $result["body"], 1); //Remove only first 3 backticks
								//I do insist that changing this format will break everything. This is hardcoded asf
								$stacktrace = "";
	    						$start = strpos($modifiedBody, "Stack trace:");
	    						if($start !== false){
	    							$start += strlen("Stack trace:");
	    							$length = strpos($modifiedBody, "```", $start) - $start;
	    							$stacktrace = substr($modifiedBody, $start, $length);
								}
								
								//if(trim($stacktrace, "\r\n") === ""){
								$title = $result["title"];
								if($title !== "Bug" && !hasRole($member, MAIN_OWNER_ROLE)){
									$title = "Bug";
								}
						
								$description = "Reporter: " . $reporter . "\nTitle: " . $title . "\nDescription: " . $description . (hasRole($member, MAIN_OWNER_ROLE) ? ("\n\nStack trace:\n" . $stacktrace) : "");
								
								sendEmbed($channel, "**Bug Tracker**", "**Bug #" . $number . " [Patched: " . ($result["state"] === "closed" ? EMOJI_AGREE : EMOJI_DISAGREE) . "]**\n\n" . $description, COLOR_INFO);
								break;
							default:
								sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!bug <report/patch/comment/comments/view/list>"), COLOR_ERROR);
						}
						break;
					case "avatar":
						if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!avatar <@mention>"), COLOR_ERROR);
					    	break;
					    }
					    $asMention = preg_replace("/!|<@|>|\s+/", "", $args[0]);
						$member = $message->channel->guild->members->get("id", $asMention);
						if($member === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("invalidUser"), COLOR_ERROR);
					    	break;
					    }
						$url = str_replace("discord.com", "discordapp.com", $member->user->avatar); //discriminator % 5 (0-4)
						echo $url . PHP_EOL;
					 	sendEmbed($channel, "**" . $author->username . "#" . $author->discriminator . "**", "", COLOR_INFO, $url, [], []);
						break;
					case "whois":
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!whois <@mention/IGN>"), COLOR_ERROR);
					    	break;
					    }
					    $asMention = preg_replace("/!|<@|>|\s+/", "", $args[0]);
					    
					    $members = $message->channel->guild->members;
					    $found = false;
					    $delta = PHP_INT_MAX;
					    foreach($linksCache as $code => $link){
					    	if(strpos($args[0], "@") !== false){ //str_contains in PHP 8
					    	    if(($link[1] ?? "") === $asMention && $link[4] === "LINKED"){
					    	    	$found = $code;
					    	    	break;
					    	    }
					    	}else{
					    		if(isset($link["xboxUser"]) && stripos($xboxUser = $link["xboxUser"], $args[0]) !== false){
					    			$curlDelta = strlen($xboxUser) - strlen($args[0]);
					    			if($curlDelta < $delta){
					    				$found = $code;
					    				$delta = $curlDelta;
					    			}
					    			if($curlDelta === 0){
					    				break;
					    			}
					    		}
					    	}
					    }
					    if($found === false){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("profileNotFound"), COLOR_ERROR);
					    	break;
					    }
					    $memberID = $linksCache[$found][1];
					    $xboxUser = $linksCache[$found]["xboxUser"];
					    
					    $member = $members->get("id", $memberID);
					    if($member !== null){
					    	$title = $member->user->username . "#" . $member->user->discriminator;
					    }else{
			    			$title = $xboxUser;
			    		}
			    		sendEmbed($channel, getLang("whoisProfileTitle", $title), getLang("whoisProfile", $xboxUser, $linksCache[$found][3] ?? "-", $linksCache[$found]["faction"] ?? "-", $linksCache[$found]["warns"] ?? "-"), COLOR_INFO, $url = SKINAPI_URL . "?player=" . urlencode($xboxUser) . "&noCache=" . mt_rand());
			    		break;
			    	case "unlink":
					    if(!hasRole($member, MAIN_OWNER_ROLE)){
							sendEmbed($channel, getLang("generic.error.title"), getLang("unlinkAccount"), COLOR_ERROR);
							break;
					    }
						$asMention = preg_replace("/!|<@|>|\s+/", "", array_shift($args));
						$xboxUser = null;
						foreach($linksCache as $code => $data){
							if(($data[1] ?? "") === $asMention){
								if($data[4] === "LINKED"){
									$xboxUser = $data["xboxUser"] ?? ""; //Should not be null
									break;
								}
							}
						}
						if($xboxUser === null){
							sendEmbed($member->user, getLang("generic.error.title"), getLang("linkAccount", $message->channel->guild->channels->get("id", LINK_CHANNEL)->name), COLOR_INFO);
							break;
						}
						$url = VERIFYAPI_URL . VERIFYAPI_ENDPOINT . "?serverID=" . VERIFYAPI_SERVERID . "&serverKey=" . VERIFYAPI_SERVERKEY . "&action=unlink&xboxUser=" . $xboxUser;
						$result = getURL($url, $httpCode, true)[2] ?? [];
						if($httpCode === 200 && isset($result[1]) && $result[1] === "SUCCESS_NO_DATA"){
			    	    	sendEmbed($member->user, getLang("unlinkAccountTitle"), getLang("unlinkedAccount", $xboxUser), COLOR_SUCCESS);
							break;
						}
						sendEmbed($member->user, getLang("generic.error.title"), getLang("genericError"), COLOR_ERROR);
			    	    break;
			    	case "link":
			    	    if(count($args) === 2 && $author->bot){
			    	    	$uid = $args[1];
			    	    	//Find code by UID
			    	    	$code = false;
			    	    	foreach($uidsCache as $index => $entry){
			    	    		if($entry[1] === $uid && $entry[0] === intval($args[0])){
			    	    			$code = $entry[0];
			    	    			unset($uidsCache[$index]);
			    	    			break;
			    	    		}
			    	    	}
			    	    	if($code === false){ //Bot was offline in the generation-verify lapse
			    	    		break;
			    	    	}
			    	    	foreach($codeCache as $index => $entry){
			    	    		if($entry[2] === $code){
			    	    			$member = $message->channel->guild->members->get("id", $entry[0]);
			    	    			if($member === null){ //Member is not in the discord!
			    	    			   break 2;
			    	    			}
			    	    			$member->addRole($guild->roles->get("id", LINKED_ROLE))->done();
			    	    			unset($codeCache[$index]);
			    	    			sendEmbed($user = $member->user, getLang("completedLinkTitle"), getLang("completedLink"), COLOR_SUCCESS);
			    	    			echo "Linked <@" . $entry[0] . "> (" . $user->username . "#" . $user->discriminator . ")" . PHP_EOL;
			    	    			break 2;
			    	    		}
			    	    	}
			    	    }else{ //Not a bot, and/or no arguments
			    	        //
			    	    }
			    	    break;
					case "redeemcoupon":
					    if(!isset($sessions[$userId])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("login.first"), COLOR_ERROR);
					    	break;
					    }
					    if(!isset($args[0])){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!redeemcoupon <code>"), COLOR_ERROR);
					    	break;
					    }
					    $code = strtoupper($args[0]);
					    if(!isset(COUPON_CODES[$code])){
					    	sendEmbed($channel, getLang("coupon.title"), getLang("coupon.invalid"), COLOR_ERROR);
					    	break;
					    }
					    if(in_array($code, $sessions[$userId]["coupons"])){
					    	sendEmbed($channel, getLang("coupon.title"), getLang("coupon.redeemed"), COLOR_WARNING);
					    	break;
					    }
					    $sessions[$userId]["coupons"][] = $code;
					    $sessions[$userId]["viewedCart"] = false;
					    sendEmbed($channel, getLang("coupon.title"), getLang("coupon.redeem", COUPON_CODES[$code], getLang("coupon.redeem.promotion", $code)), COLOR_SUCCESS);
					    break;
					case "checkout":
					    if(!isset($sessions[$userId])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("login.first"), COLOR_ERROR);
					    	break;
					    }
					    if(empty($sessions[$userId]["items"])){
					    	sendEmbed($channel, getLang("cart.title"), getLang("cart.view.empty"), COLOR_INFO);
					    	break;
					    }
					    if(!$sessions[$userId]["viewedCart"]){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("checkout.cart.view"), COLOR_ERROR);
					    	break;
					    }
					    if(isset($cooldown[0][$userId]) && time() - $cooldown[0][$userId] < CHECKOUT_COOLDOWN){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("checkout.cooldown", ceil((CHECKOUT_COOLDOWN - (time() - $cooldown[0][$userId])) / 60)), COLOR_ERROR);
					    	break;
					    }
					    sendEmbed($channel, getLang("checkout.title"), getLang("checkout.dmed"), COLOR_INFO);
					    sendEmbed($author, getLang("checkout.title"), $tos, COLOR_INFO, null, [EMOJI_AGREE, EMOJI_DISAGREE]);
					    $cooldown[$userId] = time();
					    break;
					case "login":
					    if(isset($sessions[$userId])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("login.loggedin.error", $sessions[$userId]["username"]), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!login <username>"), COLOR_ERROR);
					    	break;
					    }
					    $username = implode(" ", $args);
					    if(preg_match("/\"/", $username)){
					    	$username = preg_replace("/\"/", "", $username);
					    	sendEmbed($channel, getLang("login.title"), getLang("login.user.quotes"), COLOR_WARNING);
					    }
					    if(!(strlen($username) >= 2 && strlen($username) <= 16 && !preg_match("/[^A-Za-z0-9_ ]/", $username))){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("login.user.invalid"), COLOR_ERROR);
					    	break;
					    }
					    $sessions[$userId] = [
					       "username" => $username,
					       "items" => [],
					       "viewedCart" => false,
					       "coupons" => []
					    ];
					    $msg = getLang("login.loggedin.success", $username) . "\n\n";
					    if(isset($storeRedirect[$userId])){
					    	$package = getPackageById($storeRedirect[$userId]);
					    	$msg .= getLang("package.desc") . "\n" . $package["description"];
					    	if(!empty($package["features"])){
					    		$msg .= "\n\n" . getLang("package.desc2");
					    		foreach($package["features"] as $i => $feature){
					    			$msg .= "\n" . getLang("package.desc3", $i + 1, $feature);
					    		}
					    	}
					    	$msg .= "\n\n" . getLang("package.desc4", sprintf("%02.2f", $package["price"]));
					    	$msg .= "\n" . getLang("package.desc5.add", $storeRedirect[$userId]);
					    	sendEmbed($channel, getLang("package.title", $package["name"], $storeRedirect[$userId]), $msg, COLOR_SUCCESS, $package["image_url"]);
					    	unset($storeRedirect[$userId]);
					    }else{
					    	sendEmbed($channel, getLang("login.title"), $msg, COLOR_SUCCESS);
					    }
					    updateStatus();
						
						$mutuals = 0;
						foreach($client->guilds as $guild){
							if($guild->id !== MAIN_GUILD && $guild->members->get("id", $userId) !== null){
								$mutuals++;
							}
						}
						if($mutuals > 0){
							$sessions[$userId]["coupons"][] = PARTNER_COUPON_CODE;
					    	$sessions[$userId]["viewedCart"] = false;
					    	sendEmbed($channel, getLang("coupon.title"), getLang("coupon.redeem", COUPON_CODES[PARTNER_COUPON_CODE], getLang("coupon.redeem.mutual")), COLOR_SUCCESS);
						}
					    break;
					case "logout":
					    if(!isset($sessions[$userId])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("logout.invalid"), COLOR_ERROR);
					    	break;
					    }
					    unset($sessions[$userId]);
					    sendEmbed($channel, getLang("logout.title"), getLang("logout.success"), COLOR_SUCCESS);
					    updateStatus();
					    break;
					case "cart":
					    if(!isset($sessions[$userId])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("login.first"), COLOR_ERROR);
					    	break;
					    }
					    if(count($args) < 1){
					    	InvalidCartSyntax: {
					    		sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!cart <add/remove/view>"), COLOR_ERROR);
					    		break;
					    	}
					    }
					    switch($args[0]){
					    	case "add":
					    	    if(!isset($args[1])){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!cart add <packageID> [quantity: 1]"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $package = getPackageById(intval($args[1]));
					    	    if($package === null){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("package.invalid"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $quantity = isset($args[2]) ? intval($args[2]) : 1;
					    	    if($quantity < 1){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("cart.error.quantity"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $max = $package["maxQuantity"] ?? 0;
					    	    if(!($max < 1) && $quantity > $max){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("cart.add.error.2", $max), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    if(isset($sessions[$userId]["items"][$id = getPackageId($package)])){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("cart.add.error", $package["name"]), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    
					    	    $slotsUsed = 0;
					    	    foreach($sessions[$userId]["items"] ?? [] as $iid => $qquantity){
					    	    	$package = getPackageById($iid);
					    	    	$slotsUsed += ($package["slots"] ?? 0) * $qquantity;
					    	    }
					    	    if($slotsUsed + (($package["slots"] ?? 0) * $quantity) > 36){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("cart.add.error.3"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    
					    	    $sessions[$userId]["items"][$id] = $quantity;
					    	    $sessions[$userId]["viewedCart"] = false;
					    	    sendEmbed($channel, getLang("cart.title"), getLang("cart.add.success", $quantity, $package["name"]), COLOR_SUCCESS);
					    	    break;
					    	case "remove":
					    	    if(!isset($args[1])){
					    	    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!cart remove <packageID>", $quantity, $package["name"]), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    $id = intval($args[1]);
					    	    if(!isset($sessions[$userId]["items"][$id])){
					    	    	sendEmbed($channel, getLang("generic.error.title"), getLang("cart.remove.error"), COLOR_ERROR);
					    	    	break;
					    	    }
					    	    unset($sessions[$userId]["items"][$id]);
					    	    $sessions[$userId]["viewedCart"] = false;
					    	    sendEmbed($channel, getLang("cart.title"), getLang("cart.remove.success"), COLOR_SUCCESS);
					    	    break;
					    	case "view":
					    	    if(empty($sessions[$userId]["items"])){
					    	    	sendEmbed($channel, getLang("cart.title"), getLang("cart.view.empty"), COLOR_INFO);
					    	    	break;
					    	    }
					    	    $msg = getLang("cart.view.desc", count($sessions[$userId]["items"])) . "\n";
					    	    
					    	    foreach($sessions[$userId]["items"] as $id => $quantity){
					    	    	$package = getPackageById($id);
					    	    	$value = $package["price"] * $quantity;
					    	    	$msg .= "\n" . getLang("cart.view.desc2", $package["name"], $id, $quantity, sprintf("%02.2f", $value));
					    	    }
					    	    $msg .= "\n\n" . getLang("cart.view.desc3", sprintf("%02.2f", getBasketPrice($sessions[$userId])));
					    	    $msg .= "\n" . getLang("cart.view.desc4", sprintf("%02.2f", getBasketPrice($sessions[$userId], true)));
					    	    $msg .= "\n\n" . getLang("cart.view.desc5");
					    	    
					    	    sendEmbed($channel, getLang("cart.title"), $msg, COLOR_INFO);
					    	    $sessions[$userId]["viewedCart"] = true;
					    	    break;
					    	default:
					    	    goto InvalidCartSyntax;
					    }
					    break;
					case "store":
					    $msg = $storefront . "\n\n" . getLang("store.desc") . "\n";
					    $i = 0;
					    foreach($packages as $category => $list){
					    	$msg .= "\n" . getLang("store.desc2", $category, ++$i);
					    }
					    sendEmbed($channel, getLang("store.title"), $msg, COLOR_INFO);
					    break;
					case "category":
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!category <id>"), COLOR_ERROR);
					    	break;
					    }
					    $index = intval($args[0]) - 1;
					    if(!isset(array_keys($packages)[$index])){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("category.invalid"), COLOR_ERROR);
					    	break;
					    }
					    $categoryName = array_keys($packages)[$index];
					    $msg = getLang("category.desc", count($packages)) . "\n";
					    
					    $list = $packages[$categoryName];
					    uasort($list, function(array $A, array $B) : int{
					    	return $A["price"] < $B["price"] ? -1 : 1;
					    });
	
					    foreach($list as $name => $package){
					    	$msg .= "\n" . getLang("category.desc2", $name, getPackageId($package), sprintf("%02.2f", $package["price"]));
					    }
					    $msg .= "\n\n" . getLang("category.desc3");
					    sendEmbed($channel, getLang("category.title", $categoryName), $msg, COLOR_INFO);
					    break;
					case "package":
					    if(count($args) < 1){
					    	sendEmbed($channel, getLang("generic.syntax.title"), getLang("generic.syntax.msg", "!package <id>"), COLOR_ERROR);
					    	break;
					    }
					    $package = getPackageById(intval($args[0]));
					    if($package === null){
					    	sendEmbed($channel, getLang("generic.error.title"), getLang("package.invalid"), COLOR_ERROR);
					    	break;
					    }
					    $msg = getLang("package.desc") . "\n" . $package["description"];
					    if(!empty($package["features"])){
					    	$msg .= "\n\n" . getLang("package.desc2");
					    	foreach($package["features"] as $i => $feature){
					    		$msg .= "\n" . getLang("package.desc3", $i + 1, $feature);
					    	}
					    }
					    $msg .= "\n\n" . getLang("package.desc4", sprintf("%02.2f", $package["price"]));
					    $id = getPackageId($package);
					    if(isset($sessions[$userId])){
					    	if(isset($sessions[$userId]["items"][$id])){
					    		$msg .= "\n" . getLang("package.desc5.remove", $sessions[$userId]["items"][$id], $id);
					    	}else{
					    		$msg .= "\n" . getLang("package.desc5.add", $id);
					    	}
					    }else{
					    	$msg .= "\n" . getLang("package.desc5.login");
					    	$storeRedirect[$userId] = $id;
					    }
					    sendEmbed($channel, getLang("package.title", $package["name"], $args[0]), $msg, COLOR_INFO, $package["image_url"]);
					    break;
					default:
						$handled = false;
					    //
				}
				if($handled){
					$message->channel->messages->delete($message)->done();
				}
				$author = null; //Nullify global
			}
		}
	});
	$client->getLoop()->addTimer(3, $func = function() use($client, &$func){
		try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;$this->getLoop()->addTimer(3, $func);return;}
		$constants = get_defined_constants();
		foreach($constants as $constant => $value){
			if(strpos($constant, '_CHANNEL') !== false){
				$guild = getGuild();
				$channel = $guild->channels->get("id", $value);
				if($channel === null){
					echo 'Channel ' . implode(' ', array_map('ucfirst', explode('_', strtolower(str_replace('_CHANNEL', '', $constant))))) . ' does not exist' . PHP_EOL;
				}
			}
			if(strpos($constant, '_ROLE') !== false && strpos($constant, '_ROLES') === false){
				$guild = getGuild();
				$role = $guild->roles->get("id", $value);
				if($role === null){
					echo 'Role ' . implode(' ', array_map('ucfirst', explode('_', strtolower(str_replace('_ROLE', '', $constant))))) . ' does not exist' . PHP_EOL;
				}
			}
		}
	});
	$loop->run();
	//set_time_limit(-1);
	//ignore_user_abort(false);
/**
 * BEWARE! This is not a stern role check. Unless $strict is set to true, role positions will be accounted for.
 *
 * @param Member $member
 * @param string $role May exist or not
 * @param bool $strict
 * @return bool
 */
function hasRole(Member $member, string $role, bool $strict = false) : bool{
	global $client;
	$position = -1;
	$roleObj = getGuild()->roles->get("id", $role);
	foreach($member->roles as $role_){
		if($role_->id === $role || stripos($role_->name, $role) !== false){
			return true;
		}
		//comparePosition, sort of spaceship operator
		if($roleObj !== null){
			$position_ = $role_->position > $roleObj->position ? 1 : ($role_->position < $roleObj->position ? -1 : 0);
			if($position_ > $position){
				$position = $position_;
			}
		}
	}
	return !$strict && $position >= 0;
}

/**
 * @param string $str
 * @return string
 */
function removeFormatCodes(string $str) : string{
	/** @var string[] */
	$ocurrences = [
    	"_", "*", "~", "@", "\n", "`", "|"
	];
	return str_replace($ocurrences, "", $str);
}

function getGuild() : Guild{
	global $client;
	if(!($client->guilds instanceof GuildRepository)){
		throw new \RuntimeException("Not a GuildRepository");
	}
	foreach($client->guilds as $guild){
		//var_dump($guild->id . ":" . $guild->name . ":" . $guild->member_count);
		if($guild->id === MAIN_GUILD){
			/*foreach($guild->channels as $channel){
				echo $channel->name . ":" . $channel->id . PHP_EOL;
			}*/
			return $guild;
		}
	}
	throw new \RuntimeException("Bot must be added to the main guild");
}

function updateStatus() : void{
	global $start;
	global $linksCache;
	global $sessions;
	global $client;
		
	if($client->user !== null){
		if(time() - $start >= 750){
			/*$img = imagecreatefromstring(file_get_contents(__DIR__ . "/bot_data/EliteStar.png"));
			if($img !== null){
				imagettftext($img, 66, 0, 90, 410, imagecolorallocate($img, 255, 255, 255), __DIR__ . "/bot_data/Montserrat_Regular.ttf", date("H:i:s"));
				imagejpeg($img, __DIR__ . "/bot_data/EliteStar_date.png");
				$client->user->setAvatar(__DIR__ . "/bot_data/EliteStar_date.png")->done();
			}*/
			$start = time();
		}
		$activity = $client->factory(\Discord\Parts\User\Activity::class);
		
		$method = new \ReflectionMethod(Discord::class, "send");
		$method->setAccessible(true);
		$method->invokeArgs($client, [$payload = [
			"op" => 3,//\Discord\WebSockets\Op::OP_PRESENCE_UPDATE,
			"d" => [
				"since" => null,
				"game" => [
					"name" => "🔗 " . count($linksCache) . " accounts linked | 🛒" . count($sessions) . " users shopping",
					"type" => 3,
					"url" => "147.135.118.178"
				],
				"status" => "online",
				"afk" => false
			]
		]]);
	}
}

/**
 * @param Message $message
 * @return bool
 */
function handleGuildMessage(Message $message) : bool{
	global $cooldown, $stats;
	if(!(($member = $message->author) instanceof Member)){
		//Commands must be in a guild
		return false;
	}
	$userId = $member->user->id;
	$bot = $member->user->bot;
	$guild = $message->channel->guild;
	if(!$bot){
		if(!isset($stats[$guild->id]["level"][$userId])){
			$stats[$guild->id]["level"][$userId] = 0;
		}
		$needMessages = 50 - (++$stats[$guild->id]["level"][$userId] % 50);
		if($needMessages === 1){
				
			sendEmbed($message->channel, getLang("levelUpTitle"), getLang("levelUp", $member->user->__toString(), ($stats[$guild->id]["level"][$userId] + 1) / 50), COLOR_SUCCESS);
		}
		
		//!<lang>
		//^ Regex engine start match at the specified position
		//$ Regex engine ends match at the specified position
		if(trim($message->content) !== "" && preg_match("/^([!?])+([a-z]{2})$/", ($text = explode(" ", removeFormatCodes($message->content)))[0])){
			if(isset($cooldown[6][$userId]) && time() - $cooldown[6][$userId] < TRANSLATE_COOLDOWN && !hasRole($member, "Main Owner")){
				sendEmbed($message->channel, getLang("generic.error.title"), getLang("translatorCooldown", ceil((TRANSLATE_COOLDOWN - (time() - $cooldown[6][$userId])) / 60)), COLOR_ERROR);
			}else{
				$cooldown[6][$userId] = time();
				$result = translate("auto", ($tl = substr($command = array_shift($text), 1)), implode(" ", $text), $sl);
				if($result !== $text){	
					//$sl can be null...
					sendEmbed($message->channel, getLanguage((string) $sl) . " -> " . getLanguage($tl), $result, COLOR_INFO);
				}
			}
		}
	}
	
	if($guild->id !== MAIN_GUILD && !in_array(strtolower(explode(" ", $message)[0]), [
			"!extractembed", "!rmmessage", "!sendembed", "!guild", "!warnings", "!warn", "!dmall", "!dm", "!purge", "!status"
	])){
		return false; //Command is not permitted in another guild
	}
	if($bot){
		if($message->channel->id !== LINK_CHANNEL){
			return false;
		}
		//$message->channel->messages->delete($message)->done();
		return true; //Link webhook
	}
	$isCommand = in_array(substr($message->content, 0, 1), ["?", "!"]);
	$msg = substr($message->content, 1);
	
	$channels = [
	    STORE_COMMANDS_CHANNEL => [
	       "commands" => [
	          "login" => false,
	          "logout" => false,
	          "store" => false,
	          "category" => false,
	          "package" => false,
	          "redeemcoupon" => false,
	          "cart" => false,
	          "checkout" => false
	        ],
	        "onlyCommands" => true
	    ]
	];
	//!unlink !whois !status !suggest !invites !resetinvites
	
	$onlyCommands = isset($channels[$message->channel->id]) && ($channels[$message->channel->id]["onlyCommands"] ?? false);
	$hasCommands = isset($channels[$message->channel->id]) && !empty($channels[$message->channel->id]["commands"] ?? []);
	if($hasCommands){
		$return = false;
		foreach($channels[$message->channel->id]["commands"] as $cmd => $canBeUsedElsewhere){
			if(strpos($msg, $cmd) === 0 && $isCommand){
				$return = true;
				break;
			}
		}
		if(!$return && $onlyCommands){
			sendEmbed($message->author, getLang("commands.channel.title"), getLang("commands.channel", $message->channel->name, implode(", ", array_keys($channels[$message->channel->id]["commands"]))), COLOR_INFO);
		}
	}else{
		$return = true;
		foreach($channels as $channel => $data){
			foreach($data["commands"] ?? [] as $command => $canBeUsedElsewhere){
				if(strpos($msg, $command) === 0 && $isCommand && $channel != $message->channel->id && !$canBeUsedElsewhere){ //this is an int!
					sendEmbed($message->channel, getLang("commands.channel.title"), getLang("commands.channel.2", $message->channel->guild->channels->get("id", $channel)->name), COLOR_INFO);
					$return = false;
					break 2;
				}
			}
		}
	}
	if($onlyCommands){
		$message->channel->messages->delete($message)->done();
	}
	return $return;
}

/**
 * Returns an operable CURL resource.
 * @internal
 * @param string $url
 * @param array $headers
 * @return resource
 */
function createCURL(string $url, array $headers){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, true);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	return $ch;
}

/**
 * Executes the CURL resource.
 * @internal
 * @param resource $ch
 * @param int $httpCode
 * @param bool $retJson
 * @return mixed
 */
function execCURL($ch, ?int &$httpCode = null, bool $retJson = true){
	$result = curl_exec($ch);
	if($retJson){
		if($result === false){
			echo 'curl_error($ch): ' . curl_error($ch) . PHP_EOL;
		}
		$decodedJson = json_decode($result, true);
		if(json_last_error() !== JSON_ERROR_NONE){
			echo 'json_last_error(): ' . json_last_error() . PHP_EOL;
			var_dump($result);
			$decodedJson = [];
		}
	}
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $retJson ? $decodedJson : $result;
}

//Somehow POST can supersede PATCH in GitHub.

/**
 * SSL HTP DELETE
 *
 * @param string $url
 * @param int $httpCode by reference
 * @param bool $retJson
 * @param bool $jsonPost
 * @param array $parameters
 * @param array $headers
 * @return mixed
 */
function deleteURL($url, int &$httpCode = null, bool $retJson = true, bool $jsonPost = false, array $parameters = [], array $headers = []){
	$ch = createCURL($url, $headers);
	if($jsonPost){
		$parameters = json_encode($parameters);
		$headers[] = "Content-Type: application/json"; //This might be always needed for PATCH requests
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //Headers updated#
	}
	//curl_setopt($ch, CURLOPT_POST, true); //Is this required?
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	
	return execCURL($ch, $httpCode, $retJson);
}

/**
 * SSL HTP POST
 * @param string $url
 * @param int $httpCode by reference
 * @param bool $retJson
 * @param bool $jsonPost
 * @param array $parameters
 * @param array $headers
 * @return mixed
 */
function postURL($url, int &$httpCode = null, bool $retJson = true, bool $jsonPost = false, array $parameters = [], array $headers = []){
	$ch = createCURL($url, $headers);
	if($jsonPost){
		$parameters = json_encode($parameters);
		$headers[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //Headers updated
	}
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
	return execCURL($ch, $httpCode, $retJson);
}

/**
 * SSL HTTP GET
 * @param string $url
 * @param int $httpCode By reference
 * @param bool $retJson
 * @param array $headers
 * @return mixed
 */
function getURL(string $url, int &$httpCode = null, bool $retJson = true, array $headers = []){
	$ch = createCURL($url, $headers);
	return execCURL($ch, $httpCode, $retJson);
}

function updateInviteCache($loop = null, Closure $callback = null) : void{
	try{getGuild();}catch(\RuntimeException $e){echo $e->getMessage() . PHP_EOL;return;}
	global $invitesCache;
	global $client;
	$guild = getGuild();
	$count = 0;
	$invites = $guild->invites->freshen()->done(function(Collection $invites) use($invitesCache, $callback){
		global $invitesCache;
		
		$invitesCache = [];
		foreach($invites as $invite){
			$user = $invite->inviter;
			if($user !== null){
				if(!isset($invitesCache[$user->id])){
					$invitesCache[$user->id] = 0;
				}
				$invitesCache[$user->id] += (int) $invite->uses;
			}
		}
		if($callback !== null){
			$callback();
		}
	});
}

/**
 * Get the full language by its two-digit code.
 * @param string $iso
 * @return string
 */
function getLanguage(string $iso) : string{
	static $codes = [
    "ab" => "Abkhazian",
    "aa" => "Afar",
    "af" => "Afrikaans",
    "ak" => "Akan",
    "sq" => "Albanian",
    "am" => "Amharic",
    "ar" => "Arabic",
    "an" => "Aragonese",
    "hy" => "Armenian",
    "as" => "Assamese",
    "av" => "Avaric",
    "ae" => "Avestan",
    "ay" => "Aymara",
    "az" => "Azerbaijani",
    "bm" => "Bambara",
    "ba" => "Bashkir",
    "eu" => "Basque",
    "be" => "Belarusian",
    "bn" => "Bengali",
    "bh" => "Bihari languages",
    "bi" => "Bislama",
    "bs" => "Bosnian",
    "br" => "Breton",
    "bg" => "Bulgarian",
    "my" => "Burmese",
    "ca" => "Catalan, Valencian",
    "km" => "Central Khmer",
    "ch" => "Chamorro",
    "ce" => "Chechen",
    "ny" => "Chichewa, Chewa, Nyanja",
    "zh" => "Chinese",
    "cu" => "Church Slavonic, Old Bulgarian, Old Church Slavonic",
    "cv" => "Chuvash",
    "kw" => "Cornish",
    "co" => "Corsican",
    "cr" => "Cree",
    "hr" => "Croatian",
    "cs" => "Czech",
    "da" => "Danish",
    "dv" => "Divehi, Dhivehi, Maldivian",
    "nl" => "Dutch, Flemish",
    "dz" => "Dzongkha",
    "en" => "English",
    "eo" => "Esperanto",
    "et" => "Estonian",
    "ee" => "Ewe",
    "fo" => "Faroese",
    "fj" => "Fijian",
    "fi" => "Finnish",
    "fr" => "French",
    "ff" => "Fulah",
    "gd" => "Gaelic, Scottish Gaelic",
    "gl" => "Galician",
    "lg" => "Ganda",
    "ka" => "Georgian",
    "de" => "German",
    "ki" => "Gikuyu, Kikuyu",
    "el" => "Greek (Modern)",
    "kl" => "Greenlandic, Kalaallisut",
    "gn" => "Guarani",
    "gu" => "Gujarati",
    "ht" => "Haitian, Haitian Creole",
    "ha" => "Hausa",
    "he" => "Hebrew",
    "hz" => "Herero",
    "hi" => "Hindi",
    "ho" => "Hiri Motu",
    "hu" => "Hungarian",
    "is" => "Icelandic",
    "io" => "Ido",
    "ig" => "Igbo",
    "id" => "Indonesian",
    "ia" => "Interlingua (International Auxiliary Language Association)",
    "ie" => "Interlingue",
    "iu" => "Inuktitut",
    "ik" => "Inupiaq",
    "ga" => "Irish",
    "it" => "Italian",
    "ja" => "Japanese",
    "jv" => "Javanese",
    "kn" => "Kannada",
    "kr" => "Kanuri",
    "ks" => "Kashmiri",
    "kk" => "Kazakh",
    "rw" => "Kinyarwanda",
    "kv" => "Komi",
    "kg" => "Kongo",
    "ko" => "Korean",
    "kj" => "Kwanyama, Kuanyama",
    "ku" => "Kurdish",
    "ky" => "Kyrgyz",
    "lo" => "Lao",
    "la" => "Latin",
    "lv" => "Latvian",
    "lb" => "Letzeburgesch, Luxembourgish",
    "li" => "Limburgish, Limburgan, Limburger",
    "ln" => "Lingala",
    "lt" => "Lithuanian",
    "lu" => "Luba-Katanga",
    "mk" => "Macedonian",
    "mg" => "Malagasy",
    "ms" => "Malay",
    "ml" => "Malayalam",
    "mt" => "Maltese",
    "gv" => "Manx",
    "mi" => "Maori",
    "mr" => "Marathi",
    "mh" => "Marshallese",
    "ro" => "Moldovan, Moldavian, Romanian",
    "mn" => "Mongolian",
    "na" => "Nauru",
    "nv" => "Navajo, Navaho",
    "nd" => "Northern Ndebele",
    "ng" => "Ndonga",
    "ne" => "Nepali",
    "se" => "Northern Sami",
    "no" => "Norwegian",
    "nb" => "Norwegian Bokmål",
    "nn" => "Norwegian Nynorsk",
    "ii" => "Nuosu, Sichuan Yi",
    "oc" => "Occitan (post 1500)",
    "oj" => "Ojibwa",
    "or" => "Oriya",
    "om" => "Oromo",
    "os" => "Ossetian, Ossetic",
    "pi" => "Pali",
    "pa" => "Panjabi, Punjabi",
    "ps" => "Pashto, Pushto",
    "fa" => "Persian",
    "pl" => "Polish",
    "pt" => "Portuguese",
    "qu" => "Quechua",
    "rm" => "Romansh",
    "rn" => "Rundi",
    "ru" => "Russian",
    "sm" => "Samoan",
    "sg" => "Sango",
    "sa" => "Sanskrit",
    "sc" => "Sardinian",
    "sr" => "Serbian",
    "sn" => "Shona",
    "sd" => "Sindhi",
    "si" => "Sinhala, Sinhalese",
    "sk" => "Slovak",
    "sl" => "Slovenian",
    "so" => "Somali",
    "st" => "Sotho, Southern",
    "nr" => "South Ndebele",
    "es" => "Spanish, Castilian",
    "su" => "Sundanese",
    "sw" => "Swahili",
    "ss" => "Swati",
    "sv" => "Swedish",
    "tl" => "Tagalog",
    "ty" => "Tahitian",
    "tg" => "Tajik",
    "ta" => "Tamil",
    "tt" => "Tatar",
    "te" => "Telugu",
    "th" => "Thai",
    "bo" => "Tibetan",
    "ti" => "Tigrinya",
    "to" => "Tonga (Tonga Islands)",
    "ts" => "Tsonga",
    "tn" => "Tswana",
    "tr" => "Turkish",
    "tk" => "Turkmen",
    "tw" => "Twi",
    "ug" => "Uighur, Uyghur",
    "uk" => "Ukrainian",
    "ur" => "Urdu",
    "uz" => "Uzbek",
    "ve" => "Venda",
    "vi" => "Vietnamese",
    "vo" => "Volap_k",
    "wa" => "Walloon",
    "cy" => "Welsh",
    "fy" => "Western Frisian",
    "wo" => "Wolof",
    "xh" => "Xhosa",
    "yi" => "Yiddish",
    "yo" => "Yoruba",
    "za" => "Zhuang, Chuang",
    "zu" => "Zulu"
	];
	return $codes[$iso] ?? "Unknown";
}

/**
 * Translate text using Google Translate API
 *
 * @param $sl Source language (ISO 639-1)
 * @param $tl Target language (ISO 639-1)
 * @param string $text
 * @param string $sl
 * @return string
 */
function translate(string $sl = "auto", string $tl, string $text, ?string &$sl_ = "") : string{
	if(trim($sl) === "" || trim($tl) === "" || trim($text) === ""){
		$sl_ = $tl;
		return $text;
	}
	$text = removeFormatCodes($text);
	
	@unlink($fname = __DIR__ . "/bot_data/transres.js");
	ob_start();
	
	copy("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" .$tl . "&hl=hl&q=". urlencode($text), $fname);
	$ob = ob_get_flush();
	
	if(trim($ob) !== ""){ //Similar to PHP Warning: Failed to open stream: HTTP request failed! HTTP/1.0 400 Bad Request
		$sl_ = $tl;
		return $text;
	}
		
	if(file_exists($fname)){
		$res = json_decode(@file_get_contents($fname), true);
		if(isset($res[0][0][0])){
			$sl_ = $res[2];
			$ret = "";
			foreach($res[0] as $arr){
				$ret .= $arr[0];
			}
			echo json_encode($res[0], JSON_PRETTY_PRINT);
			return $ret;
		}
	}
	return $text;
}


function handleApplicationMessage(?string $msg, User $user){
	global $applications, $applicationsCache;
	if(!isset($applicationsCache[$user->id])){
		return;
	}
	$questions = $applicationsCache[$user->id]["questions"] ?? [];
	$step = $applicationsCache[$user->id]["step"] ?? -1;
		
	if($step < 0){
		sendEmbed($user, getLang("applyTitle"), getLang("applyPick", $applicationsCache[$user->id]["role"]) . "\n\n" . $applicationsCache[$user->id]["description"], COLOR_INFO);
		sendEmbed($user, getLang("applyTitle"), getLang("applyQuestion", array_keys($questions)[0]), COLOR_INFO, null, [EMOJI_UNDO, EMOJI_DISAGREE]);
		$applicationsCache[$user->id]["step"] = 0;
	}else{
		$values = $questions[array_keys($questions)[$step]];
		if($msg !== null && (!empty($regex = ($values["regex"] ?? "")) && !preg_match("/" . $regex . "/", $msg))){
			sendEmbed($user, getLang("applyTitle"), $values["message"], COLOR_ERROR);
				
		}else{
			$msg = (string) $msg;
			$applicationsCache[$user->id]["answers"][] = removeFormatCodes($msg);
			if(($next = ++$applicationsCache[$user->id]["step"]) < count($questions)){
				sendEmbed($user, getLang("applyTitle"), getLang("applyQuestion", array_keys($questions)[$next]), COLOR_INFO, null, [EMOJI_UNDO, EMOJI_DISAGREE]);
			}else{
				$ids[] = 0;
				foreach($applications as $application){
					$ids[] = $application[1];
				}
				$id = max($ids) + 1;
				$applications[$user->id] = [array_combine(array_keys($questions), $applicationsCache[$user->id]["answers"]), $id, false, time(), $applicationsCache[$user->id]["role"]];
				$position = array_search($user->id, array_keys($applications)) + 1;
				sendEmbed($user, getLang("applyTitle"), getLang("applySubmitted", $position), COLOR_INFO);
				unset($applicationsCache[$user->id]);
			}
		}
	}
}


final class Rcon{
    private $host;
    private $port;
    private $password;
    private $timeout;
    private $socket;
    private $authorized = false;
    private $lastResponse = "";
    private const PACKET_AUTHORIZE = 5;
    private const PACKET_COMMAND = 6;
    private const SERVERDATA_AUTH = 3;
    private const SERVERDATA_AUTH_RESPONSE = 2;
    private const SERVERDATA_EXECCOMMAND = 2;
    private const SERVERDATA_RESPONSE_VALUE = 0;
	
    public function __construct($host, $port, $password, $timeout){
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }
	
    public function getResponse(){
        return $this->lastResponse;
    }
	
    public function connect(){
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if(!$this->socket){
            $this->lastResponse = $errstr;
            var_dump($errstr);
            return false;
        }
        stream_set_timeout($this->socket, 3, 0);
        return $this->authorize();
    }
	
    public function disconnect(){
        if($this->socket){
        	fclose($this->socket);
        }
    }
	
    public function isConnected(){
        return $this->authorized;
    }
	
    public function sendCommand($command){
        if(!$this->isConnected()){
        	return false;
        }
        $this->writePacket(self::PACKET_COMMAND, self::SERVERDATA_EXECCOMMAND, $command);
        $response_packet = $this->readPacket();
        if($response_packet["id"] == self::PACKET_COMMAND){
            if($response_packet["type"] == self::SERVERDATA_RESPONSE_VALUE){
                $this->lastResponse = $response_packet["body"];
                return $response_packet["body"];
            }
        }
        return false;
    }
	
    private function authorize(){
        $this->writePacket(self::PACKET_AUTHORIZE, self::SERVERDATA_AUTH, $this->password);
        $response_packet = $this->readPacket();
        if($response_packet["type"] == self::SERVERDATA_AUTH_RESPONSE){
            if($response_packet["id"] == self::PACKET_AUTHORIZE){
                $this->authorized = true;
                return true;
            }
        }
        $this->disconnect();
        return false;
    }
	
    private function writePacket($packetId, $packetType, $packetBody){
        $packet = pack("VV", $packetId, $packetType);
        $packet = $packet.$packetBody . "\x00";
        $packet = $packet . "\x00";
        $packet_size = strlen($packet);
        $packet = pack("V", $packet_size) . $packet;
        fwrite($this->socket, $packet, strlen($packet));
    }
	
    private function readPacket(){
        $size_data = fread($this->socket, 4);
        $size_pack = unpack("V1size", $size_data);
        $size = $size_pack["size"];
        $packet_data = fread($this->socket, $size);
        $packet_pack = unpack("V1id/V1type/a*body", $packet_data);
        return $packet_pack;
    }
}
__halt_compiler(); //Byte position: __COMPILER__HALT_OFFSET