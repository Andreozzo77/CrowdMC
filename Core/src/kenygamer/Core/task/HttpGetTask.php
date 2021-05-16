<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\Server;
use pocketmine\scheduler\Task;

use kenygamer\Core\Main;

class HttpGetTask extends Task{
	/** @var string */
	private $url;
	/** @var bool */
	private $await;
	/** @var int */
	private $requestsLeft;
	/** @var int */
	private $limit;
	/** @var int */
	private $id;
	/** @var array */
	private $callback;
	/** @var bool */
	private $awaiting = false;
	
	/**
	 * @param string $url
	 * @param int $requests
	 * @param bool $await
	 * @param int $limit
	 * @param int $id
	 * @param array $callback
	 */
	public function __construct(string $url, int $requests, bool $await, int $limit, int $id, array $callback){
		$this->url = $url;
		$this->requestsLeft = $requests;
		$this->await = $await;
		$this->limit = $limit;
		$this->id = $id;
		$this->callback = $callback;
	}
	
	/**
	 * @param int $currentTick
	 * @return void
	 */
	public function onRun(int $currentTick) : void{
		if(!($this->await && $this->awaiting)){
			if($this->requestsLeft === 0){
			    Main::getInstance()->cancelHttpGetTask($this->id);
			}else{
				Server::getInstance()->getAsyncPool()->submitTask(new AsyncHttpGetTask($this->url, $this->limit, $this->id, $this->callback));
				if($this->requestsLeft !== -1){
					$this->requestsLeft--;
				}
			}
		}
	}
	
}