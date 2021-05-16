<?php
echo "TEST\n";
try{

define("DISCORD_PUSH_WEBHOOK", "https://discord.com/api/webhooks/793164738862383104/3K_gNf1QZ5CNEJp8P6j1YDkBwLxWcjveeHl-nlIKUS2D9ULpAsYtOxp_ZOdJ7jnVarUx");

define("DISCORD_ISSUES_WEBHOOK", "https://discord.com/api/webhooks/793164738862383104/3K_gNf1QZ5CNEJp8P6j1YDkBwLxWcjveeHl-nlIKUS2D9ULpAsYtOxp_ZOdJ7jnVarUx"); //Unused

define("WEBHOOK_SECRET", "[REDACTED]");
define("COMMIT_FILE", "/home/crowdmc/commit.tmp");

require_once __DIR__ . "/api/MCPEDiscord/functions.php";

if(!isset($_SERVER["HTTP_X_HUB_SIGNATURE"])){
	die("Signature is not set.");
}
if(!extension_loaded("hash")){
	die("Extension hash not found.");
}
list($algo, $hash) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + ["", ""];
if(!in_array($algo, hash_algos(), true)){
	die("Algorithm not supported");
}
$snapshot = json_decode(COMMIT_FILE, true);

$_POST = file_get_contents("php://input");
if($hash !== hash_hmac($algo, $_POST, WEBHOOK_SECRET)){
	die("Hashes do not match");
}

$json = file_get_contents("php://input");
$_POST = json_decode($json, true);
			
$full_repo = $_POST["repository"]["full_name"];
list($user, $repo) = explode("/", $full_repo);
$reply = "";
var_dump($_SERVER["HTTP_X_GITHUB_EVENT"]);
switch($_SERVER["HTTP_X_GITHUB_EVENT"]){
	case "push":
	    if($_POST["ref"] !== "refs/heads/stable"){
			break;
	    }
		$branch = str_replace("refs/heads/", "", $_POST["ref"]);
		
		foreach($_POST["commits"] ?? [] as $commit){
			$id = substr($commit["id"], 0, 7);
			$msg = $commit["message"];
			
			$timestamp = $commit["timestamp"]; //2020-12-03T23:38:04-03:00 
			[$added, $removed, $modified] = [$commit["added"], $commit["removed"], $commit["modified"]];
		}
		if(!isset($msg)){
			die("No commits");
		}
		$time = strtotime($timestamp);
		$date = date(DATE_RFC2822, $time); //Wed, 25 Sept 2013 15:28:57 -0700
		
		$embeds = [];
		$embed = [
			"color" => 1597882,
			"title" => str_repeat(" ", mt_rand(1, 15)),
			//"title" => "`" . $id . "`",
			/*"description" => str_replace("`", "", $msg) . "\n➕ | 🔍 | ➖\n" . strval(count($added)) . " | " . strval(count($modified)) . " | " . strval(count($removed)),*/
			"description" => "`" . $id . "` - " . str_replace(["`", "_", "*", "|"], "", $msg),
			"author" => [
				"name" => $user,
				"icon_url" => "https://github.com/" . $user . ".png"
			],
			/*"fields" => [
				[
					"name" => "files added",
					"value" => strval(count($added)),
					"inline" => true
				],
				[
					"name" => "files removed",
					"value" => strval(count($removed)),
					"inline" => true
				],
				[
					"name" => "files modified",
					"value" => strval(count($modified)),
					"inline" => true
				]
			],*/
			"footer" => [
				"text" => $_POST["repository"]["full_name"],
					//$branch . " - " . $date, //OCTOCAT!
				"icon_url" => "https://avatars1.githubusercontent.com/u/583231?s=400&u=a59fef2a493e2b67dd13754231daf220c82ba84d&v=4" //$_POST["repository"]["owner"]["avatar_url"] . "&nocache=" . strval(time())
			]
		];
	 	$embeds[] = $embed;
		sendDiscordWebhook(DISCORD_PUSH_WEBHOOK, "", $reply, ["embeds" => $embeds]);
		var_dump($reply);
		file_put_contents(COMMIT_FILE, $id);
		break;
	case "issues":
	    $issue = $_POST["issue"];
		$number = $issue["number"];
		$embeds = [];
		switch($_POST["action"]){
			case "labeled":
				echo "[TEST]\n";
				var_dump($_POST);
				if(isset($_POST["label"])){
					$label = $_POST["label"];
					$issue = $_POST["issue"];
					$number = $issue["number"];
					$embed = [
						"color" => hexdec($label["color"]),
						"title" => "**Added the " . $label["name"] . " label to bug #" . $number . "**",
						"description" => "https://github.com/crowdmc/bugs/issues/" . $number,
						"author" => [
							"name" => $user,
							"icon_url" => "https://github.com/" . $user . ".png"
						],
						"footer" => [
							"text" => $_POST["repository"]["full_name"],
							"icon_url" => "https://avatars1.githubusercontent.com/u/583231?s=400&u=a59fef2a493e2b67dd13754231daf220c82ba84d&v=4"
						]
					];
					echo "[ADDED LABEL]\n";
				}
				break;
			case "opened":
				if($_POST["repository"]["full_name"] !== "CrowdMC/bugs"){
					break;
				}
				
			    /*list($_, $stacktrace, $description) = explode("```", $issue["body"]);
				$lines = explode("\n", $stacktrace);
				array_shift($lines);
				$reporter = array_shift($lines);
				$stacktrace = implode("\n", $lines);
				$ch = curl_init("https://jsonblob.com/api/jsonBlob");
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
				curl_setopt($ch, CURLOPT_VERBOSE, 1); 
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["title" => base64_encode($issue["title"]), "stacktrace" => base64_encode($stacktrace)]));
				$result = curl_exec($ch);
				preg_match('/^Location: [^\r\n]m', $result, $matches);
				$link = str_replace("Location: ", "", $matches[0]);
				curl_close($ch);
				if(!filter_var($link, FILTER_VALIDATE_URL)){
					//die($result);
				}
				
				$description = str_replace("## Description:", "", $description);
				$url = "https://mcpe.life/bug_tracker?bug=" . str_replace("https://jsonblob.com/api/jsonBlob/", "", $link);
				$embed = [
					"color" => 16744576,
					"title" => "**Bug #" . $number . "**",
					"description" => "||<" . $url . ">||",
					"footer" => [
						"text" => $full_repo,
						"icon_url" => "https://github.com/" . $user . ".png"
					],
					"fields" => [
						[
							"name" => "**Reporter**",
							"value" => $reporter,
						    "inline" => false

						]
					]
				];
				*/
				$description = $issue["body"];
				$embed = [
					"color" => 0xffcc00,
					"title" => "**Created bug #" . $number . "**",
					"description" => "https://github.com/crowdmc/bugs/issues/" . $number,
					"author" => [
						"name" => $user,
						"icon_url" => "https://github.com/" . $user . ".png"
					],
					"footer" => [
						"text" => $_POST["repository"]["full_name"],
						"icon_url" => "https://avatars1.githubusercontent.com/u/583231?s=400&u=a59fef2a493e2b67dd13754231daf220c82ba84d&v=4"
					]
				];
				
				if(trim($description) !== "" && count($issue["labels"]) === 0){
					$embed["fields"][] = [
						"name" => "**Description**",
						"value" => $description . str_repeat(" ", mt_rand(3, 30)),
						"inline" => false
				 	];
				}
			 	break;
			case "closed":
				if($_POST["repository"]["full_name"] !== "CrowdMC/bugs"){
					
					break;
				}
				
				$embed = [
					"color" => 0x90ee90,
					"title" => "**Patched bug #" . $number . "**",
					"description" => "https://github.com/crowdmc/bugs/issues/" . $number . " " . str_repeat(" ", mt_rand(3, 30)),
					"author" => [
						"name" => $user,
						"icon_url" => "https://github.com/" . $user . ".png"
					],
					"footer" => [
						"text" => $_POST["repository"]["full_name"],
						"icon_url" => "https://avatars1.githubusercontent.com/u/583231?s=400&u=a59fef2a493e2b67dd13754231daf220c82ba84d&v=4"
					],
					"fields" => []
				];
				$description = $issue["body"];
				if(trim($description) !== "" && count($issue["labels"]) === 0){
					$embed["fields"][] = [
						"name" => "**Description**",
						"value" => $description . str_repeat(" ", mt_rand(3, 30)),
						"inline" => false
				 	];
				}
				break;
		}
		if(isset($embed)){
			$embeds[] = $embed;
			sendDiscordWebhook(DISCORD_ISSUES_WEBHOOK, "", $reply, ["embeds" => $embeds]);
			sleep(mt_rand(1, 3));
			var_dump($reply);
			var_dump($embeds);
		}
		break;
}
}catch(\Throwable $e){
	var_dump($e);
	}
