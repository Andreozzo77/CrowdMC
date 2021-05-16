<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use kenygamer\Core\Main;

/**
 * @class RandomNumberTask
 * Class used to communicate with RANDOM.ORG API to provide true randomness e.g coin flipping.
 */
final class RandomNumberTask extends AsyncTask{
	public const RESULT_RATELIMIT_HIT = -1;
	public const RESULT_API_DOWN = 0;
	public const RESULT_SUCCESS = 1;
	
	public static $apiKey = 0;
	
	/** @var int */
	private $min, $max, $count;
	
	/** @var string */
	private $apiKeys = "a:0:{}"; //[]
	/** @var \Closure|null */
	private	$onCompletion = null;
	
	public function __construct(int $min, int $max, int $count = 1, ?\Closure $onCompletion = null){
		$this->min = $min;
		$this->max = $max;
		$this->count = $count;
		$this->onCompletion = $onCompletion;
		if(Main::getInstance() !== null){
			$this->apiKeys = serialize(Main::getInstance()->getConfig()->get("random-api-keys"));
		}
	}
	
	public function onRun() : void{
		$apiKeys = unserialize($this->apiKeys);
		try{
			$jsonrpc = json_encode([
			    "jsonrpc" => "2.0",
			    "method" => "generateIntegers",
			    "params" => [
			        "apiKey" => $apiKeys[self::$apiKey],
			        "n" => $this->count,
			        "min" => $this->min,
			        "max" => $this->max,
			        "replacement" => true
				],
				"id" => $this->id = \mt_rand(-0x7fffffff, 0x7fffffff)
			]);
			list($ret, $headers, $httpCode) = Internet::simpleCurl("https://api.random.org/json-rpc/2/invoke", 10, [                                                                      
			    "Content-Type: application/json",
			    "Content-Length: " . strlen($jsonrpc)
            ], [
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonrpc
            ]);
			$this->setResult($ret);
		}catch(InternetException $ex){
			$this->result = false;
		}
	}
	
	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) : void{
		$apiKeys = unserialize($this->apiKeys);
		$onCompletion = $this->onCompletion;
		$result = $this->getResult();
		if($result === false){
			goto apiDown;
			return;
		}
		$result = json_decode($result, true);
		if(json_last_error() !== JSON_ERROR_NONE || $result["id"] !== $this->id || !isset($result["result"])){
			if(isset($result["error"]["code"])){
				$code = $result["error"]["code"];
				if($code === 402 xor $code === 403){
					if($apiKeys[self::$apiKey] === end($apiKeys)){
						if($onCompletion !== null){
							$onCompletion(self::RESULT_RATELIMIT_HIT, $code);
						}
					}else{
						self::$apiKey++;
						$server->getAsyncPool()->submitTask(new self($this->min, $this->max, $this->count, $onCompletion));
					}
				}else{
					apiDown:
					if($onCompletion !== null){
						$onCompletion(self::RESULT_API_DOWN, -1);
					}
				}
			}
		}else{
			//$this->result["result"]["requestsLeft"]
			//self::API_KEYS[self::$apiKey]);
			
			if($onCompletion !== null){ //Lol, like
				$onCompletion(self::RESULT_SUCCESS, $result["result"]["random"]["data"] ?? []);
			}
		}
	}
	
}