<?php

declare(strict_types=1);

namespace kenygamer\Core;

use pocketmine\Server;
use pocketmine\Thread;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use kenygamer\Core\listener\MiscListener2;

//Mutex works similar like rubber chickens in heated discussions

//PHP Threads =/ Posix Threads
final class RelayThread extends Thread{
	private const SOCKET_1_ADDRESS = "crowdmc.us.to"; 
	private const SOCKET_1_PORT = 45008; 
	
	private const SOCKET_2_PORT = 45009;
	
	/** @var bool */
	private $hasStopped = false;
	/** @var int */
	private $sleep = 0;
	
	public const RELAY_THREAD_IN = 0;
	public const RELAY_THREAD_OUT = 1;
	
	/**
	 * Relays a message.
	 *
	 * @param string $format The message sent to recipients. {%0} placeholder is replaced
	 * @param string $msg The message that will be translated
	 * @param array $recipients Recipient list.
	 * @param int $type RELAY_THREAD_IN: Raw messages; RELAY_THREAD_OUT: Messages ready to send.
	 */
	public static function relay(string $format, string $msg = "", array $recipients = [], ?int $type = self::RELAY_THREAD_IN, string $discordMessage = "No message") : void{
		$recipients_ = [];
		foreach($recipients as $recipient){
			$recipients_[$recipient->getName()] = LangManager::LANG_DEFAULT;
			//TODO:
			//$recipients_[$recipient->getName()] = $recipient instanceof Player ? ($lang = LangManager::getInstance()->getPlayerLanguage($recipient)) : LangManager::LANG_DEFAULT;
		}
		
		$queue = new Config($fname = \pocketmine\PATH . "relay_queue.js", Config::JSON); //Must create a new config
		//Reloading the config doesn't work, at least in \Thread's
		$queue->reload();
		$all = $queue->getAll();
		$all[] = [$format, $msg, $recipients_, $type, $discordMessage];//date("[H:i:s]", time()) . " " . 
		//$queue->setAll($all);
		
		//To use file locking and minimize JSON
		//Locking is only on main thread. Relay thread will always wait main thread.
		$flags = LOCK_EX;
		try{
			Server::getInstance();
		}catch(\RuntimeException $e){
			$flags = 0;
		}
		file_put_contents($fname, json_encode($all, JSON_UNESCAPED_UNICODE), $flags);
	}
	
	
	public function __construct(string $pluginDataPath){
		//$this->queue = new \SplQueue(); //Extends SplDoublyLinkedList
		//$this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
		$this->pluginDataPath = $pluginDataPath;
		$this->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS); //PTHREADS_INHERIT_ALL
	}
	
	//A new thread in PHP has a separate heap - Share Nothing, code above doesn't work
	public function run(){
		$this->doLogic();
	}
	
	private function doLogic() : void{
		$this->registerClassLoader(); //Needed by Thread descendents
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$socket2 = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($socket2, "0.0.0.0", self::SOCKET_2_PORT) or die("Failed to listen to socket on port " . self::SOCKET_2_PORT);
		try{
			while(true){
				if($this->hasStopped){
					//parent::quit();
					break;
				}
				if(microtime(true) < $this->sleep){
					continue;
				}
				$messages = [];
				$len = 0;  
				$relayQueue = new Config($fname = \pocketmine\PATH . "relay_queue.js", Config::JSON);
				$all = $relayQueue->getAll();
				$new = [];
				foreach($all as $needle){
					list($format, $msg, $recipients, $type, $discordMessage) = $needle;
					if($type === RelayThread::RELAY_THREAD_OUT){
						$new[] = $needle;
						continue;
					}
					$len += strlen($discordMessage); 
					$messages[] = TextFormat::clean($discordMessage); //Messages sent to the socket
					
					//Messages sent to the recipients
					
					//TODO
					$result = str_replace("{%0}", $msg, $format);
					if(count($recipients) === 0){
						$new[] = [$result, "", [], RelayThread::RELAY_THREAD_OUT, $discordMessage];
					}else{
						foreach($recipients as $recipient => $lang){
							$new[] = [$result, "", [$recipient => $lang], RelayThread::RELAY_THREAD_OUT, $discordMessage];
						}
					}
				}
				/*$mh = curl_multi_init();
				$ch_arr = [];
				//Perform multi-threaded curl
				foreach($all as $needle){
					list($format, $msg, $recipients, $type, $discordMessage) = $needle;
					if($type === RelayThread::RELAY_THREAD_OUT){
						continue;
					}
					foreach($recipients as $recipient => $lang){
						$ch_arr[] = [curl_init("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=auto&tl=" . $lang . "&hl=hl&q=" . urlencode($msg)), $msg, $format, $recipient, $lang, $discordMessage];
						$index = count($ch_arr) - 1;
						curl_setopt($ch_arr[$index][0], CURLOPT_CONNECTTIMEOUT, 2);
						curl_setopt($ch_arr[$index][0], CURLOPT_CONNECTTIMEOUT, 2);
						curl_setopt($ch_arr[$index][0], CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch_arr[$index][0], CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($ch_arr[$index][0], CURLOPT_RETURNTRANSFER, true);
						curl_multi_add_handle($mh, $ch_arr[$index][0]);
					}
				}
				do{
					curl_multi_exec($mh, $still_running);
				}while($still_running > 0);
				for($index = 0; $index < count($ch_arr); $index++){
					list($ch, $msg, $format, $recipient, $lang, $discordMessage) = $ch_arr[$index];
					$response = curl_multi_getcontent($ch);
				
					$response = @json_decode((string) @$response, true);
					if(empty($response)){
						$result = str_replace("{%0}", $msg, $format);
					}else{
						$result = "";
						if(isset($response[0])){
							foreach($response[0] as $arr){
								$result .= $arr[0];
							}
						}
						$result = str_replace("{%0}", $result, $format);
					}
					$new[] = [$result, "", [$recipient => $lang], RelayThread::RELAY_THREAD_OUT, $discordMessage];
					curl_close($ch);
					
					
				}*/
				$relayQueue->setAll($new); 
				$relayQueue->setAll([]); //TODO
				//Acquire lock and lock to the current process
				$fp = fopen($fname, "w+");
				if($fp !== null){
					$time = 0;
					$gotlock = true;
					//LOCK_NB to not wait
					while(!flock($fp, LOCK_EX | LOCK_NB, $wouldblock)){ //It will wait up to 0.05s to free the lock with almost zero margin of error
		    			if($wouldblock && ($time = $time + 10000) < 500000){
		        			usleep(1000); //0.01s / check 1/5 the server tick rate.
							continue;
						}
						$gotlock = false;
						break;
					}
					if($gotlock){
						$relayQueue->save();
						flock($fp, LOCK_UN);
					}else{
						//NOPE. It is impossible that it takes longer than 0.5s. Unless the code previous to locking crashed
					}
				}
				if(count($messages) > 0){
					$jsonBlob = json_encode($messages, JSON_UNESCAPED_UNICODE);
					@socket_sendto($socket, $jsonBlob, strlen($jsonBlob), 0, self::SOCKET_1_ADDRESS, self::SOCKET_1_PORT);
				}
				
				//Read incoming messages
				if(socket_recvfrom($socket2, $buffer, 32000, MSG_DONTWAIT, $ip, $port)){
					if(is_string($buffer)){
						
						$messages = (array) @json_decode($buffer, true);
						foreach($messages as $message){
							self::relay("{%0}", $message, [], self::RELAY_THREAD_IN, $message);
						}
					}
				}else{
					//echo socket_strerror(socket_last_error());
				}
				//1s = 10 ^6 ms
				$this->sleep = microtime(true) + 0.1; //workaround to not delay server shutdown
			
			}
		}catch(\Throwable $e){
			echo $e->getMessage() . PHP_EOL;
		}
		socket_close($socket);
	}
	/**
	 * Called by {@see \pocketmine\ThreadManager::stopAll()} when the server is stopped
	 */
	public function quit(){
		$this->hasStopped = true;
	}
	
}