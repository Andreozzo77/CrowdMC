<?php

$password = "[REDACTED]";
if(!isset($_GET["password"]) || $_GET["password"] !== $password || !isset($_GET["action"])){
	http_response_code(400);
	exit;
}

$tests = (array) json_decode(file_get_contents(__DIR__ . "/tests.js"), true);
$testsArray = $tests["tests"] ?? [];
	
function save(){
	global $tests;
	file_put_contents(__DIR__ . "/tests.js", json_encode($tests)); //Chmod this to 777
}

$testers = $tests["testers"] ?? [];
if(!empty($testsArray)){
	$test = end($testsArray);
	switch($_GET["action"]){
		case "submitCheck":
		    if(isset($_GET["ok"]) && ($_GET["ok"] === "0" xor $_GET["ok"] === "1") && isset($_GET["tester"]) && isset($tests["testers"][$_GET["tester"]]) && isset($_GET["notes"])){
		    	$ok = boolval($_GET["ok"]);
		    	$keys = array_keys($testsArray);
		    	$testNo = end($keys);
		    	$tests["tests"][$testNo]["tests"][$_GET["tester"]] = [$ok, $_GET["notes"]]; 
		    	save();
		    	http_response_code(200);
				exit;
			}
		    break;
		case "deploy":
		    $changes = addslashes(implode("\n, ", $test["changes"]));
		    /*To sign commits automatically:
		    
		    ~/.gnupg/gpg-agent.conf:
		    default-cache-ttl 93312000
		    max-cache-ttl 93312000
		    
		    git config --global user.signingkey "Kevin <me@kenygamer.com>"
		    */
		    set_time_limit(0);
		    //exec("sudo chown -R www-data:www-data /home/crowdmc/plugins"); 
		    exec("cd /home/crowdmc/plugins ; sudo git add . ; sudo git commit -S -m \"" . $changes . "\" ; sudo git push https://kenygamer:8f8d590f5241cdc4de1f10096c36b9b5f80e00b1@github.com/kenygamer/CrowdMC.git --all 2>&1 ", $output, $return); //disable PHP safe mode. run by default by www-data, unless you use sudo
			
		    var_dump($output);
		    var_dump($return);
			
		    if($return != 0 || stripos($output, "everything up to date") === false){
		    	http_response_code(500);
		    	exit;
		    }
		    $keys = array_keys($testsArray);
		    $testNo = end($keys);
		    $tests["tests"][$testNo]["deployed"] = true;
			foreach($tests["tests"] as $noB => $test){
				if($noB !== $testNo){
					unset($tests["tests"][$noB]);
				}
			}
		    save();
		    http_response_code(200);
		    exit;
		    break;
	}
}

//W / w/out tests
switch($_GET["action"]){
	case "unsubscribe":
	    if(isset($_GET["tester"]) && isset($testers[$_GET["tester"]])){
	    	unset($tests["testers"][$_GET["tester"]]);
	    	save();
	    	http_response_code(200);
	    	exit;
	    }
	    break;
	case "subscribe":
	    if(isset($_GET["tester"]) && isset($_GET["ign"]) && !isset($testers[$_GET["tester"]]) && !in_array($_GET["ign"], $testers)){
	    	$tests["testers"][$_GET["tester"]] = $_GET["ign"];
	    	save();
	    	http_response_code(200);
	    	exit;
	    }
	    break;
}

http_response_code(400);
