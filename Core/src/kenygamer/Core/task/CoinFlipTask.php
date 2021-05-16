<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

use onebone\economyapi\EconomyAPI;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

/**
 * Class used to communicate with RANDOM.ORG API to provide true randomness for coin flipping.
 *
 * @class CoinFlipTask
 * @package Core\task
 */
final class CoinFlipTask extends AsyncTask{
	/** @var string */
	private $gambler1;
	/** @var string */
	private $gambler2;
	/** @var int */
	private $money;
	
	/** @var int|null */
	private $id = -1;
	/** @var array|bool */
	private $result = false;
	
	private const API_KEYS = [
	    "2cb049cc-c142-4010-aa59-ce2530652511", "7350d075-fbca-4e31-87ae-1c6941a12f8a", "c17fa4cf-0730-4214-b012-1f8d2e923130",
	    "ffa0d63c-95fb-44c7-bba2-53c5f8b89927", "5b07d71e-549d-431f-a000-1260b11674d9", "f1670f94-53d6-42f2-8f6f-5a6a2fb6c15d"
	];
	
	public static $apiKey = 0;
	
	public function __construct(string $gambler1, string $gambler2, int $money){
		$this->gambler1 = $gambler1;
		$this->gambler2 = $gambler2;
		$this->money = $money;
	}
	
	public function onRun() : void{
		try{
			$jsonrpc = json_encode([
			    "jsonrpc" => "2.0",
			    "method" => "generateIntegers",
			    "params" => [
			        "apiKey" => self::API_KEYS[self::$apiKey],
			        "n" => 1,
			        "min" => 1,
			        "max" => 99,
			        "replacement" => true
				],
				"id" => $this->id = \kenygamer\Core\Main::mt_rand()
			]);
			list($ret, $headers, $httpCode) = Internet::simpleCurl("https://api.random.org/json-rpc/2/invoke", 10, [                                                                      
			    "Content-Type: application/json",
			    "Content-Length: " . strlen($jsonrpc)
            ], [
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonrpc
            ]);
			$this->result = $ret;
		}catch(\InternetException $ex){
			$this->result = false;
		}
	}
	
	public function onCompletion(Server $server) : void{
		unset(Main::$gambling[array_search($this->gambler1, Main::$gambling)]);
		unset(Main::$gambling[array_search($this->gambler2, Main::$gambling)]);
		$gambler1 = $server->getPlayerExact($this->gambler1);
		$gambler2 = $server->getPlayerExact($this->gambler2);
		$result = json_decode($this->result, true);
		if(json_last_error() !== JSON_ERROR_NONE || $result["id"] !== $this->id || !isset($result["result"])){
			if(isset($result["error"]["code"])){
				$code = $result["error"]["code"];
				if($code === 402 xor $code === 403){
					if(self::API_KEYS[$apiKey] === end(self::API_KEYS)){
						$result = ["coinflip-down", []];
					}else{
						self::$apiKey++;
						$server->getAsyncPool()->submitTask(new self($this->gambler1, $this->gambler2, $this->money));
					}
				}else{
					$result = ["coinflip-down-error", [$code]];
				}
				if(isset($result)){
					foreach([$gambler1, $gambler2] as $player){
						if($player !== null){
							LangManager::send($result[0], $player, ...$result[1]);
						}
					}
				}
			}
		}else{
			//$result["result"]["requestsLeft"]
			//self::API_KEYS[self::$apiKey]);
			$chance = array_shift($result["result"]["random"]["data"]);
			$winner = $chance <= 50 ? $gambler1 : $gambler2;
			$loser = $gambler1 === $winner ? $gambler2 : $gambler1;
			if($winner !== null && $loser !== null){
				if(EconomyAPI::getInstance()->myMoney($gambler1) < $this->money || EconomyAPI::getInstance()->myMoney($gambler2) < $this->money){
					foreach([$winner, $loser] as $player){
						LangManager::send("coinflip-gambler-nomoney", $player, $this->money);
					}
				}else{
					EconomyAPI::getInstance()->setMoney($winner, EconomyAPI::getInstance()->myMoney($winner) + $this->money);
					EconomyAPI::getInstance()->setMoney($loser, EconomyAPI::getInstance()->myMoney($loser) - $this->money);
					LangManager::send("coinflip-robbed", $winner, $this->money, $loser->getName(), $chance);
					LangManager::send("coinflip-lost", $loser, $this->money, $chance);
				}
			}
		}
	}
	
}