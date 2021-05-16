<?php

$secret = "[REDACTED]";
if(isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && extension_loaded("hash")){
	list($algo, $hash) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + ["", ""];
	if(in_array($algo, hash_algos(), true)){
		$snapshot = json_decode(file_get_contents(__DIR__ . "/snapshot.js"), true);
		$_POST = file_get_contents("php://input");
		if($hash === hash_hmac($algo, $_POST, $secret)){
			$json = file_get_contents("php://input");
			$_POST = json_decode($json, true);
			
			$fullRepo = $_POST["repository"]["full_name"];
			$head_commit = substr($_POST["after"], 0, 7);
			
		    /* $_POST["commits"] as $commit $commit = substr($commit["id"], 0, 7); //Short commit id
		    foreach($_POST["commits"] as $commit){
		    	$id = substr($commit["id"], 0, 7);
		    	$msg = $commit["message"];
			}
			require_once(__DIR__ . "/VerifyAPI/functions.php");
			$reply = "";
			$embeds = [];
			$embed = [
			    "color" => 7506394,
			    "title" => $head_commit,
			    "description" => ""
		    ];
		    $embeds[] = $embed;
		    sendDiscordWebhook("https://discord.com/api/webhooks/709174929945526302/eb0K4tMj7XDCYhBZSy55SCgFdAO89tROg60cBdeaBCfWfef6FUrNKLSJwo8W9_HlsRVY", "", $reply, ["embeds" => $embeds]);*/
		    
		    file_put_contents("/home/crowdmc/commit.tmp", $head_commit);

			$HTTP_Code = 200;
		}else{
			$HTTP_Code = 401;
		}
	}else{
		$HTTP_Code = 401;
	}
}else{
	$HTTP_Code = 400;
}
http_response_code($HTTP_Code ?? 200);
if($HTTP_Code !== 200){ //OK
	echo "Failed to send restart signal";
}else{
	echo "Sent restart signal";
}
