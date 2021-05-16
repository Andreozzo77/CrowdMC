<?php

function query(string $host, int $port, int $timeout = 4) : array{
	$socket = @fsockopen("udp://" . $host, $port, $errno, $errstr, $timeout);
	if($errno){
		fclose($socket);
		throw new \Exception($errno . ": " . $errstr, E_WARNING);
    }elseif($socket === false){
    	throw new \Exception($errno . ": " . $errstr, E_WARNING);
	}
	stream_set_timeout($socket, $timeout);
	stream_set_blocking($socket, true);
	
	$command = "\xfe\xfd"; //fe fd: UT3 query identifier - http://wiki.unrealadmin.org/UT3_query_protocol
	$command .= pack("V*", 9, time()); //Handshake + timestamp
	
	$length = \strlen($command);

    if($length !== fwrite($socket, $command, $length)){
    	throw new \Exception("Failed to write on socket.", E_WARNING);
	}
	$data = fread($socket, 4096);

	//N = 4-byte int
	//V = 16-byte little endian int
	
	if(substr($data, 0, 1) !== "\x09"){
		throw new \Exception();
	}else{
		//$requestID = unpack("N", substr($data, 1, 4));
		$token = (int) substr($data, 5);
		
		$command = "\xfe\xfd"; //2 bytes
		$command .= "\x0"; //1 byte
		$command .= pack("N", 1);
		//Payload
		$command .= pack("N", $token); //Must be the token
		$command .= pack("N", $token); //Make up 4 bytes more to get long query
		
		fwrite($socket, $command, strlen($command));
		
		$data = fread($socket, 4096);
		$data = explode("\x00", substr($data, 4));
		
		$continue = $isPlayerList = false;
		$playerList = [];
		$ret = [];
		foreach($data as $i => $value){
			if($value === "\x01player_"){
				$isPlayerList = true;
				continue;
			}
			if($value === "\x01" && $isPlayerList){
				$isPlayerList = false;
				continue;
			}
			if($isPlayerList && $value !== ""){
				$playerList[] = $value;
				continue;
			}
			if($continue){
				$continue = false;
				continue;
			}
			if($value === "" || !isset($data[$i + 1])){
				continue;
			}
			$ret[$value] = $data[$i + 1];
			$continue = true;
		}
		array_shift($ret);
		$ret["player_"] = $playerList;
		fclose($socket);
	}
	return $ret;
}