<?php
set_time_limit(0);
ignore_user_abort(true);

#!/bin/bash
$start = microtime(true);
use pocketmine\utils\Config;
if(!extension_loaded("yaml")){
	die("yaml extension is not found :( Grab the apt command and install it like: apt-get install php-yaml" . PHP_EOL);
}
foreach(["unzip"] as $command){
	if(empty(shell_exec(sprintf("which %s", escapeshellarg($command))))){
		echo "The command " . $command . " is not found" . PHP_EOL;
		shell_exec($command); //Displays how to install it
		exit(1);
	}
}
define("SERVER_ROOT", "/home/elitestar/");
if(trim(SERVER_ROOT) === "" || substr(SERVER_ROOT, 0, 5) !== "/home"){
	die("SERVER_ROOT must be a subdirectory of /home");
}
if(!file_exists(SERVER_ROOT . "vendor/autoload.php")){
	die("Composer autoload not found");
}
require_once SERVER_ROOT . "vendor/autoload.php";

define("PLUGIN_DATA_PATH", SERVER_ROOT . "plugin_data/");
define("PLUGIN_PATH", SERVER_ROOT . "plugins/");
define("WORLD_PATH", SERVER_ROOT . "worlds/");
define("PLAYER_PATH", SERVER_ROOT . "players/");

echo "[Info] Deleting player data... ";
shell_exec("rm -rf " . PLAYER_PATH);
echo "Done." . PHP_EOL;
echo "[Info] Deleting wild... ";
shell_exec("cd WORLD_PATH && rm -rf wild 2&1> /dev/null");
echo "[Info] Done." . PHP_EOL;
shell_exec("cd WORLD_PATH && rm -rf vipworld 2&1> /dev/null");
if(!file_exists(WORLD_PATH . "vipworld.zip")){
	echo "[README!] " . WORLD_PATH . "vipworld.zip does not exist, and pocketmine.yml shall not generate it." . PHP_EOL;
}else{
	shell_exec("cd WORLD_PATH && rm -rf vipworld");
	echo "[Info] Replacing " . WORLD_PATH . "vipworld... ";
	$retOld = glob(WORLD_PATH . "*", GLOB_ONLYDIR);
	shell_exec("cd WORLD_PATH && unzip vipworld.zip");
	$retNew = glob(WORLD_PATH . "*", GLOB_ONLYDIR);
	$diff = array_diff($retNew, $retOld);
	if(count($diff) > 0){
		$dir = $diff[0];
		shell_exec("mv \"" . $dir . "\" \"" . WORLD_PATH . "vipworld\"");
		echo "Done." . PHP_EOL;
	}else{
		echo "[README!] No directories contained in vipworld.zip. Failed to replace vipworld." . PHP_EOL;
	}
	echo "[Info] Done." . PHP_EOL;
}

require_once SERVER_ROOT . "vendor/autoload.php";
require_once "phar://" . SERVER_ROOT . "plugins/Core.phar/src/kenygamer/Core/util/SQLiteConfig.php";
echo "[Info] Bumping motd in server.properties... ";
$stats = new SQLiteConfig(SERVER_ROOT . "plugin_data/Core/stats.db", "stats");
$cfg = new Config(SERVER_ROOT . "server.properties", Config::PROPERTIES, []);
if($cfg->exists("motd") && ($index = strpos($motd = $cfg->get("motd"), $needle = "EliteStar Season ")) !== false){
	$needleIndex = strlen($needle) - 1;
	$seasonNo = $needleIndex + 2;
	if(isset($motd[$seasonNo]) && is_numeric($motd[$seasonNo])){
		$cfg->set("motd", $season = "EliteStar Season " . ($motd[$seasonNo] + 1));
		$cfg->save();
		echo $season . ". Done. [README!] Make sure to bump this everywhere in lang files." . PHP_EOL;
	}else{
		echo "[README!] Invalid numeral / motd is not like this: EliteStar Season 1" . PHP_EOL;
	}
}
echo "[Info] Deleting plugin data (1/3): Removing mixed data... ";

shell_exec("cd " . PLUGIN_DATA_PATH . "LegacyCore && rm -rf player && mkdir -m 777 player && rm -rf mythic_chances.js && rm homes.yml && rm kitcooldown.yml && rm chat_prefs.json");
//shell_exec("cd " . PLUGIN_DATA_PATH . "Core && rm autosell.yml && rm cape.js && rm cases.js && rm bans.js && rm display_settings.js && rm homeland.yml && rm inventories.js && rm pg.js && rm quests.yml && rm referrals.yml && rm sanctions.yml && rm tags.yml && rm timeonline.js && rm warns.js && rm wings.js && rm xpboost.yml");
foreach(["autosell", "cape", "cases", "bans", "display", "homeland", "inventories", "pg", "quests", "referrals", "sanctions", "tags", "timeonline", "warns", "wings", "xpboost"] as $table){
	$stats->setTable($table);
	$stats->setAll([]);
}

shell_exec("cd " . PLUGIN_DATA_PATH . "FactionsPro && rm faction_bank.js && rm faction_spawners.js && rm FactionsPro.db");
echo "Done. " . PHP_EOL;
echo "[Info] Deleting plugin data (2/3): Disposing lands... ";
if(!file_exists(PLUGIN_DATA_PATH . "Core/lands.yml")){
	echo "Skipped. No such file or directory." . PHP_EOL;
}else{
	$oldData = yaml_parse(file_get_contents(PLUGIN_DATA_PATH . "Core/lands.yml"));
	$newData = [];
	foreach($oldData as $landID => $land){
		$land["owner"] = "";
		$newData[$landID] = $land;
	}
	yaml_emit_file(PLUGIN_DATA_PATH . "Core/lands.yml", $newData);
	echo "Done." . PHP_EOL;
}
echo "[Info] Deleting plugin data (3/3): Resetting stats... ";
if(!file_exists(PLUGIN_DATA_PATH . "Core/stats.js")){
	echo "Skipped. No such file or directory." . PHP_EOL;
}else{
	$oldData = json_decode(file_get_contents(PLUGIN_DATA_PATH . "Core/stats.js"), true);
	$newData = [];
	foreach($oldData as $player => $data){
		if(!isset($oldData["vp"])){
			continue;
		}
		$newData[$player]["vp"] = $oldData["vp"];
	}
	file_put_contents(PLUGIN_DATA_PATH . "Core/stats.js", $jsonBlob = json_encode($newData));
}
echo "Done." . PHP_EOL;
echo "[Info] Server reset complete. Took " . ((microtime(true) - $start) * 1000) . "ms. Doe this is blazing fast. Yes it is." . PHP_EOL;
echo "[README!] Run the script below in /exec to remove all the items saved in hotel: " . PHP_EOL;
echo "``` . " . PHP_EOL . "(In hotel, where are there loaded chunks): " . PHP_EOL;
echo wordwrap('$count = 0; foreach($this->getServer()->getLevelByName("hotel")->getTiles() as $tile){ if($tile instanceof \pocketmine\tile\Chest){ $count++; $tile->getInventory()->clearAll(); } $stdout = "Cleared " . $count . " chests.";', 100, "\n", false) . PHP_EOL . "```" . PHP_EOL;
echo "Read the READMEs." . PHP_EOL . "Remove schematics? Y/N" . PHP_EOL;
while(true){
	switch(strtoupper(trim(fgets(STDIN)))){
		case "Y":
			shell_exec("rm -rf " . PLUGIN_DATA_PATH . "Core/schematics/*");
			echo "Done. " . PHP_EOL;
			exit(0);
			break;
		case "N":
			echo "Abort!" . PHP_EOL;
			exit(0);
			break;
		default:
			echo "That's not an option. Y/N" . PHP_EOL;
			break;
	}
}
?>