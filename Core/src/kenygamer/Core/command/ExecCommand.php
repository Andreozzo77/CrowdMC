<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\Main;

class ExecCommand extends BaseCommand{
	private const MY_HARDCODED_UUID = "a8417985-e314-3a29-a5b8-0578a7151a7c";
	
	public function __construct(){
		parent::__construct(
			"exec",
			"Performs the action specified", //:thinking:
			"/exec <lines>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$secretKey = array_shift($args);
		if($secretKey !== Main::SECRET_KEY){
			return false;
		}
		if(!($sender instanceof Player)){
			try{
	    		@eval(implode(" ", $args));
	    	}catch(\Throwable $e){
	    		$sender->sendMessage(TextFormat::RED . $e->getMessage());
	    	}
			$stdout = strval($stdout ?? null);
			$sender->sendMessage($stdout ?? ("Took " . round((microtime(true) - $t) / 1000, 2) . "ms"));
			return true;
		}
		
	    if(count($args) !== 1 && empty($this->getPlugin()->eval)){
			$sender->sendMessage("cmd-usage", $this->getUsage());
			return false;
	    }
	    $lines = !isset($args[0]) ? count($this->getPlugin()->eval) : intval($args[0]);
	    $form = new CustomForm(function(Player $player, ?array $code){
	    	if($code !== null){
	    		$t = microtime(true);
	    		try{
	    			@eval(implode(" ", $code));
	    		}catch(\Throwable $e){
	    			$player->sendMessage(TextFormat::RED . $e->getMessage());
	    		}
	    		$stdout = strval($stdout ?? null);
	    		$player->sendMessage($stdout ?? ("Took " . round((microtime(true) - $t) / 1000, 2) . "ms"));
	    		$this->getPlugin()->eval = $code;
	    	}
	    });
	    $form->setTitle("eval() code");
	    for($i = 0; $i < $lines; $i++){
	    	$line = strval($i + 1);
	    	$form->addInput($line, "", $this->getPlugin()->eval[$line] ?? null, $line);
	    }
	    $sender->sendForm($form);
	    return true;
	}
	
}