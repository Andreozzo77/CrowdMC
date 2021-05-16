<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use PHPMailer\PHPMailer\PHPMailer;

class SendEmailTask extends AsyncTask{
	/** @var string Serialized PHPMailer */
	private $mail;
	/** @var bool */
	private $error = false;

	public function __construct(PHPMailer $mail, \Closure $success = null, \Closure $failure = null){
		$this->mail = serialize($mail);
		$this->storeLocal([$success, $failure]);
	}
	
	public function onRun() : void{
		$mail = unserialize($this->mail);
		try{
			if(!$mail->send()){
				echo "Failed sending email: " . $mail->ErrorInfo . PHP_EOL;
				$this->error = true;
				return;
			}
		}catch(\Exception $e){
			echo $e->getMessage() . PHP_EOL;
			$this->error = true;
			return;
		}
	}
	
	public function onCompletion(Server $server) : void{
		$callbacks = $this->fetchLocal();
		if(!$this->error){
			if($callbacks[0] !== null){
				$callbacks[0]();
			}
		}else{
			if($callbacks[1] !== null){
				$callbacks[1]();
			}
		}
	}
}