<?php

function getUniqueID(int $length, string $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"){
	$uniqID = "";
    $charsLength = strlen($chars);
    for ($i = 0; $i < $length; $i++){
        $uniqID .= $chars[rand(0, $charsLength - 1)];
    }
    return $uniqID;
}

/**
 * Send a Discord webhook via POST.
 *
 * @param string $url
 * @param string $message
 * @param string &$reply
 * 0param array|null $customfields
 */
function sendDiscordWebhook(string $url, string $message = "", string &$reply, ?array $customfields = null){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	if($customfields){
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($customfields));
	}else{
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["content" => $message]));
	}
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
	$reply = curl_exec($ch); //passed by reference
	curl_close($ch);
}