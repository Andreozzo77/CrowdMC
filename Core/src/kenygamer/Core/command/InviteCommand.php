<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\utils\TextFormat;
use kenygamer\Core\listener\MiscListener;

class InviteCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"invite",
			"Claim your invites",
			"/invite",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $referrals = $this->getPlugin()->referrals->get($sender->getLowerCaseName(), []);
	    $unclaimed = [];
	    foreach($referrals as $player => $claimData){
	    	if(!$claimData["claimed"]){
	    		$unclaimed[] = $player;
	    		$sender->sendMessage("invite-referred", $player, $this->getPlugin()->formatTime($this->getPlugin()->getTimeEllapaed($claimData["time"]), TextFormat::AQUA, TextFormat::AQUA));
	    	 }
	    }
	    if(isset($args[0]) && mb_strtolower($args[0]) === "claim"){
	    	$money = count($unclaimed) * 3000000;
	    	if(count($unclaimed) > 0){
	    		foreach($unclaimed as $player){
	    			$referrals[$player]["claimed"] = true;
	    		}
	    		$this->getPlugin()->referrals->set($sender->getLowerCaseName(), $referrals);
	    		$this->getPlugin()->referrals->save();
				$sender->addMoney($money);
				$sender->sendMessage("invite-claim", count($unclaimed), $money);
	    	}else{
	    		$any = false;
	    		foreach(MiscListener::$referred_playing as $referred => $data){
	    			if(mb_strtolower($data[0]) === $sender->getLowerCaseName()){
	    				$any = true;
	    				$referred_player = $this->getPlugin()->getServer()->getPlayerExact($referred);
	    				if($referred_player !== null){
							$sender->sendMessage("invite-referred-online", $referred);
	    				}else{
	    					$sender->sendMessage("invite-referred-offline", $referred);
	    				}
	    			}
	    		}
	    		if(!$any){
					$sender->sendMessage("invite-none");
	    		}
	    	}
	    }else{
			$sender->sendMessage("invite-info");
	    }
	    return true;
	}
	
}