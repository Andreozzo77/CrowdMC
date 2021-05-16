<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\listener\MiscListener;

class GiveTokensCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"givetokens",
			"Add or subtract tokens from a player",
			"/givetokens <player> <tokens>",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
	    $tokens = intval($args[1]);
	    if($tokens < 0){
	    	$this->getPlugin()->subtractTokens($player, abs($tokens));
	    	$sender->sendMessage("givetokens-taken", abs($tokens), $player->getName());
	    }elseif($tokens !== 0){
	    	$this->getPlugin()->addTokens($player, $tokens);
			$sender->sendMessage("givetokens-given", $tokens, $player->getName());
	    }else{
			$sender->sendMessage("givetokens-invalid");
	    }
	    return true;
	}
	
}