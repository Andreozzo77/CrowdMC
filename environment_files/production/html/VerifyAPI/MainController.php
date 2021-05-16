<?php

/**
 * MCPEDiscordRelay
 *
 * PHP library that allows you to add a verification wall for the members of your Minecraft Discord server
 * and relays their in-game stats to the Discord server.
 *
 * @author kenygamer
 * @copyright 2019-2020 kenygamer
 * @link https://kenygamer.com Kevin
 */
 
 //CURL EXTENSION REQUIRED
 
declare(strict_types=1);

interface APIResponse{
	
	//NEW
	const ERR_SAME_ENTRY = -14;
	const ERR_INVLD_ENTRY_VALUE = -13;
	const ERR_INVLD_ENTRY = -12;
	
	/**
	 * The rank to change is the same as the old.
	 */
	const ERR_SAME_RANK = -11;
	
	/**
	 * The rank is invalid or too long.
	 */
	const ERR_INVLD_RANK = -10;
	
	/**
	 * The account is already linked.
	 */
	const ERR_ALRD_LINKED = -9;
	
	/**
	 * Server credentials provided are invalid.
	 */
	const ERR_AUTH_FAILED = -8;
	/**
	 * API action was not be recognized.
	 */
	const ERR_UNREC_ACTION = -7;
	/**
	 * User ID provided does not follow Discord syntax.
	 */
	const ERR_INVLD_USR_ID = -6;
	/**
	 * Unix 32-bit timestamp is invalid.
	 */
	const ERR_INVLD_TIMESTAMP = -5;
	/**
	 * Verification code field is null, empty or not 4-digit long.
	 */
	const ERR_INVLD_VERFCODE = -4;
	/**
	 * Code being submitted is already on the DB.
	 */
	const ERR_CODE_USED = -3;
	/**
	 * Could not write data to database due to server-related error.
	 */
	const ERR_DB_SAVE = -2;
	/**
	 * The Xbox Live username field is invalid.
	 */
	const ERR_INVLD_XBOX = -1;
	/**
	 * Discord Webhooks returned a unexpected response.
	 */
	const ERR_DISCORD_WEBHOOK = 0;
	/**
	 * Means the request has completed but no data is displayed.
	 */
	const SUCCESS_NO_DATA = 1;
	/**
	 * Means the request has completed and data is returned.
	 */
	const SUCCESS_DATA = 2;
	
}

ignore_user_abort(true);
set_time_limit(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("WEBHOOK_URL", "https://discordapp.com/api/webhooks/646202214297370625/gttYP90W4LLlFymbPX3HEXqdl-V_q6CujjtsJ9uWE1__gOxUHP6nLQfE9j8BLfsKID5o");

require_once(__DIR__ . "/functions.php");

$server = json_decode(file_get_contents(dirname(__FILE__) . "/server.json", true), true);
$serverID = isset($_GET["serverID"]) ? intval($_GET["serverID"]) : null;
if(!array_key_exists($serverID, $server) || !isset($_GET["serverKey"]) || empty($serverKey = $_GET["serverKey"]) || strlen($serverKey) !== 64 || $serverKey !== $server[$serverID]){
	echo json_encode([0 => APIResponse::ERR_AUTH_FAILED, 1 => "ERR_AUTH_FAILED"]);
	http_response_code(401);
}else{
	$VerifyCodes = json_decode(file_get_contents(dirname(__FILE__) . "/code/" . $serverID . ".json"), true);
	$UniqueIDs = json_decode(file_get_contents(dirname(__FILE__) . "/uniqID/" . $serverID . ".json"), true);
	
	function saveDB() : bool{
		global $serverID;
		global $VerifyCodes;
		global $UniqueIDs;
		$writeVrf = file_put_contents(dirname(__FILE__) . "/code/" . $serverID . ".json", json_encode($VerifyCodes, JSON_PRETTY_PRINT), LOCK_EX);
		$writeUniqIDs = file_put_contents(dirname(__FILE__) . "/uniqID/" . $serverID . ".json", json_encode($UniqueIDs, JSON_PRETTY_PRINT), LOCK_EX);
		return ($writeVrf !== FALSE ? TRUE : FALSE) && ($writeUniqIDs !== FALSE ? TRUE : FALSE);
	}
	
	//$reference can be: (string) userID or (int) VerifyCode
	function getLinkStatus($reference) : string{
		global $VerifyCodes;
		global $UniqueIDs;
		foreach($VerifyCodes as $code => $data){
			if($reference === $code or $reference === $data[1]){
				$i = $code;
			}
		}
		if(!isset($i)){
			return "INVALID";
		}
		if(!$VerifyCodes[$i][2]){
			if($VerifyCodes[$i][0] <= time()){
				unset($VerifyCodes[$i]);
				unset($UniqueIDs[$i]);
				saveDB();
				return "EXPIRED";
			}
			return "PENDING";
		}else{
			return "LINKED";
		}
	}
	
	switch($_GET["action"] ?? ""){
	    case "submitCode":
	        if(!isset($_GET["userID"]) || !is_numeric($userID = $_GET["userID"]) || (strlen($userID) < 17 or strlen($userID) > 18)){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_USR_ID, 1 => "ERR_INVLD_USR_ID"]);
	        	http_response_code(400);
	        	break;
	        }elseif(!isset($_GET["expiry"]) || !is_numeric($expiry = $_GET["expiry"]) || !ctype_digit($expiry)){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_TIMESTAMP, 1 => "ERR_INVLD_TIMESTAMP"]);
	        	http_response_code(400);
	        	break;
	        }elseif(!isset($_GET["VerifyCode"]) || !is_numeric($VerifyCode = $_GET["VerifyCode"]) || (strlen($VerifyCode) < 4 or strlen($VerifyCode) > 8)){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_VERFCODE, 1 => "ERR_INVLD_VERFCODE"]);
	        	http_response_code(400);
	        	break;
	        }elseif(isset($VerifyCodes[$VerifyCode]) && in_array(getLinkStatus($VerifyCode), ["INVALID", "EXPIRED"])){
	        	echo json_encode([0 => APIResponse::ERR_CODE_USED, 1 => "ERR_CODE_USED"]);
	        	http_response_code(400);
	        	break;
	        }else{
	        	//Prevent re-linking discord accounts
	        	foreach($VerifyCodes as $code => $data){
	        		if($userID === $data[1] && $data[2]){
	        			echo json_encode([0 => APIResponse::ERR_ALRD_LINKED, 1 => "ERR_ALRD_LINKED"]);
	        			http_response_code(400);
	        			break 2;
	        		}
	        	}
	        	
	        	$VerifyCodes[$VerifyCode] = [$expiry, $userID, false, ""];
	        	
	        	//A way to prevent abuse.
	        	foreach($VerifyCodes as $vrfcode => $data){
	        		if($data[1] === $userID && $VerifyCodes[$vrfcode] !== end($VerifyCodes)){
	        			unset($VerifyCodes[$vrfcode]);
	        		}
	        	}
	        	
	        	$uniqID = getUniqueID(64);
	        	//yes, they can be overriden
	        	$UniqueIDs[$VerifyCode] = $uniqID;
	        	if(saveDB()){
	        		echo json_encode([0 => APIResponse::SUCCESS_DATA, 1 => "SUCCESS_DATA", 2 => $uniqID]);
	        		http_response_code(200);
	        		break;
	        	}else{
	        		echo json_encode([0 => APIResponse::ERR_DB_SAVE, 1 => "ERR_DB_SAVE"]);
	        		http_response_code(500);
	        		break;
	        	}
	        }
	        break;
    	case "fetchLinks":
    	    $vrfcodes = [];
    	    foreach($VerifyCodes as $code => $data){
    	    	$vrfcodes[$code] = $data;
    	    	$vrfcodes[$code][] = getLinkStatus($code); //Index 4 for fetchLinks is the code status (string)
    	    }
    	    //$code sent is a numeric string, so run loosely checks
    	    echo json_encode([0 => APIResponse::SUCCESS_DATA, 1 => "SUCCESS_DATA", 2 => $vrfcodes]);
    	    http_response_code(200);
	        break;
	    case "verifyLink":
	        //bot is responsible for verifying hash match (uniqID). API stores, generates
	        //and sends the hash to the discord bot & webhook bot
	        if(!isset($_GET["xboxUser"]) || strlen($xboxUser = $_GET["xboxUser"]) > 16){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_XBOX, 1 => "ERR_INVLD_XBOX"]);
    	    	http_response_code(400);
    	    	break;
    	    }elseif(!isset($_GET["VerifyCode"]) || !is_numeric($VerifyCode = $_GET["VerifyCode"]) || (strlen($VerifyCode) < 4 or strlen($VerifyCode) > 8)){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_VERFCODE, 1 => "ERR_INVLD_VERFCODE"]);
	        	http_response_code(400);
	        	break;
	        }else{
	        	foreach($VerifyCodes as $code => $data){
	        		
	        		//Prevent re-linking xbox users
	        		if((isset($data["xboxUser"]) && $data["xboxUser"] === $xboxUser) || $code === $VerifyCode){
	        			echo json_encode([0 => APIResponse::ERR_ALRD_LINKED, 1 => "ERR_ALRD_LINKED"]);
	        			http_response_code(400);
	        			break 2;
	        		}
	        		//Unused error code (what rank could be > 16 chars long???)
	        		if(!isset($_GET["rank"]) || !is_string($rank = $_GET["rank"]) || strlen($rank) > 16){
	        			echo json_encode([0 => APIResponse::ERR_INVLD_RANK, 1 => "ERR_INVLD_RANK"]);
	        			http_response_code(400);
	        			break 2;
	        		}
	        		
	        	}
	        	$VerifyCodes[$VerifyCode]["xboxUser"] = $xboxUser;
	        	$VerifyCodes[$VerifyCode][2] = true;
	        	$VerifyCodes[$VerifyCode][3] = $rank;
	        	
	        	$hash = $UniqueIDs[$VerifyCode];
	        	unset($UniqueIDs[$VerifyCode]);
	        	if(saveDB()){
	        		$reply = "";
	        		sendDiscordWebhook(WEBHOOK_URL, "!link " . $VerifyCode . " " . $hash, $reply);
	        		if($reply === ""){
	        			echo json_encode([0 => APIResponse::SUCCESS_NO_DATA, 1 => "SUCCESS_NO_DATA"]);
	        			http_response_code(200);
	        			break;
	        		}
	        		echo json_encode([0 => APIResponse::ERR_DISCORD_WEBHOOK, 1 => "ERR_DISCORD_WEBHOOK"]);
	        		http_response_code(500);
	        		break;
	        	}else{
	        		echo json_encode([0 => APIResponse::ERR_DB_SAVE, 1 => "ERR_DB_SAVE"]);
	        		http_response_code(500);
	        		break;
	        	}
	        }
	        break;
	    case "updateEntry":
		    if(!isset($_GET["xboxUser"]) || !is_string($xboxUser = $_GET["xboxUser"]) || strlen($xboxUser = $_GET["xboxUser"]) > 16){
		    	echo json_encode([0 => APIResponse::ERR_INVLD_XBOX, 1 => "ERR_INVLD_XBOX"]);
    	    	http_response_code(400);
    	    	break;
    	    }
		    if(!isset($_GET["entry"]) || !is_string($entry = $_GET["entry"])){
		    	echo json_encode([0 => APIResponse::ERR_INVLD_ENTRY, 1 => "ERR_INVLD_ENTRY"]);
		    	http_response_code(400);
		    	break;
		    }
		    if(!isset($_GET["value"]) || !is_string($value = $_GET["value"])){
		    	echo json_encode([0 => APIResponse::ERR_INVLD_ENTRY, 1 => "ERR_INVLD_ENTRY_VALUE"]);
		    	http_response_code(400);
		    	break;
		    }
		    $vrfcodes = $VerifyCodes;
    	    foreach($VerifyCodes as $code => $data){
    	    	if(mb_strtolower($vrfcodes[$code]["xboxUser"]) === mb_strtolower($xboxUser) && ($vrfcodes[$code][$entry] ?? "") !== $value){
    	    		$vrfcodes[$code][$entry] = $value;
    	    		break;
    	    	}
    	    }
    	    if($vrfcodes !== $VerifyCodes){ //Changes were detected
    	        $VerifyCodes = $vrfcodes; //Now we can set the var to save using the function
    	    	if(saveDB()){
    	    		echo json_encode([0 => APIResponse::SUCCESS_NO_DATA, 1 => "SUCCESS_NO_DATA"]);
    	    		http_response_code(200);
    	    	}else{
    	    		echo json_encode([0 => APIResponse::ERR_DB_SAVE, 1 => "ERR_DB_SAVE"]);
    	    		http_response_code(500);
    	    	}
	        }else{
	        	echo json_encode([0 => APIResponse::ERR_SAME_RANK, 1 => "ERR_SAME_ENTRY"]);
	        	http_response_code(400);
	        	break;
	        }
	        break;
	    case "unlink":
	        if(!isset($_GET["xboxUser"]) || strlen($xboxUser = $_GET["xboxUser"]) > 15){
	        	echo json_encode([0 => APIResponse::ERR_INVLD_XBOX, 1 => "ERR_INVLD_XBOX"]);
    	    	http_response_code(400);
    	    	break;
    	    }
    	    foreach($VerifyCodes as $code => $data){
    	    	if(isset($data["xboxUser"]) && $data["xboxUser"] === $xboxUser){
    	    		unset($VerifyCodes[$code]);
    	    		if(saveDB()){
	        		    echo json_encode([0 => APIResponse::SUCCESS_NO_DATA, 1 => "SUCCESS_NO_DATA"]);
	        		    http_response_code(200);
	        		    break 2;
	        		}else{
	        		    echo json_encode([0 => APIResponse::ERR_DB_SAVE, 1 => "ERR_DB_SAVE"]);
	        		    http_response_code(500);
	        		    break 2;
	        		}
	        	}
	        }
	        break;
	    case "sendDiscordWebhook": //a helper api endpoint for sending Discord webhooks
	        sendDiscordWebhook: {
	        	$url = $_GET["url"] ?? null;
	        	$message = $_GET["message"] ?? null;
	        	if(!is_string($url) or !filter_var($url, FILTER_VALIDATE_URL)){
	        		echo json_encode([0 => APIResponse::ERR_INVLD_URL, 1 => "ERR_INVLD_URL"]);
	        		http_response_code(400);
	        		break;
	        	}
	        	if(!is_string($message) || strlen($message) > 2048){
	        		echo json_encode([0 => APIResponse::ERR_INVLD_MSG, 1 => "ERR_INVLD_MSG"]);
	        		http_response_code(400);
	        		break;
	        	}
	        	$reply = "";
	        	sendDiscordWebhook($url, $message, $reply);
	        	echo json_encode([0 => APIResponse::SUCCESS_NO_DATA, 1 => "SUCCESS_NO_DATA", 2 => $reply]); //sending the reply is unnecessary
	        	http_response_code(200);
	        	break;
	        }
    	default:
	        echo json_encode([0 => APIResponse::ERR_UNREC_ACTION, 1 => "ERR_UNREC_ACTION"]);
	        http_response_code(400);
	}
}