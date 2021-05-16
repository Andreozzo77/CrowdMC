<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;

use kenygamer\Core\Main;

class AsyncHttpGetTask extends AsyncTask{
	private const CURL_TIMEOUT = 15;
	
	/** @var string */
	private $url;
	/** @var int */
	private $limit;
	/** @var int */
	private $id;
	/** @var string */
	private $callback;
	
	/** @var mixed */
	private $result;
	
	/**
	 * @param string $url
	 * @param int $limit
	 * @param int $id
	 * @param array $callback
	 */
	public function __construct(string $url, int $limit, int $id, array $callback){
		$this->url = $url;
		$this->limit = $limit;
		$this->id = $id;
		$this->callback = serialize($callback);
	}
	
	/**
	 * @return void
	 */
	public function onRun() : void{
		$this->result = Internet::getURL($this->url, self::CURL_TIMEOUT);
	}
	
	/**
	 * @param Server $server
	 * @return void
	 */
	public function onCompletion(Server $server) : void{
		$plugin = Main::getInstance();
		if($plugin !== null){
			$plugin->setHttpGetResult($this->id, $this->limit, $this->result);
			$callback = unserialize($this->callback);
			if(count($callback) >= 2){
				$class = array_shift($callback);
				$function = array_shift($callback);
				//$callback is parameter array
				call_user_func_array([$class, $function], $callback);
			}
		}
	}
	
}