<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;

class VotePointsCommand extends BaseCommand{
	/** @var \Closure[] */
	private $rewards = [];
	
	public function __construct(){
		parent::__construct(
			"votepoints",
			"View your vote points, claim a reward.",
			"/votepoints [add/subtract/see]",
			["vp"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
		
		$this->setupReward(function(Player $player) : bool{
			$player->addMoney(250000);
			return true;
		}, 2, "250,000 $ In-Game Money (1d)");
		
		$this->setupReward(function(Player $player){
			$item = ItemUtils::get("atlas_gem");
			if(!$this->getPlugin()->testSlot($player, 1)){
				return false;
			}
			$player->getInventory()->addItem($item);
			return true;
		}, 4, "x1 Atlas Gem (2d)");
		
		$this->setupReward(function(Player $player) : bool{
			$item = ItemUtils::get("weekly_voter_gem");
			if(!$this->getPlugin()->testSlot($player, 1)){
				return false;
			}
			$player->getInventory()->addItem($item);
			return true;
		}, 14, "Weekly Voter Kit (1w)");
		
		$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Vip")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Vip");
			return true;
		}, 28, "VIP Rank (2w)");
		
		$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Mvp")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Mvp");
			return true;
		}, 56, "MVP Rank (4w)");
		
		$this->setupReward(function(Player $player) : bool{
			$item = ItemUtils::get("monthly_voter_gem");
			if(!$this->getPlugin()->testSlot($player, 1)){
				return false;
			}
			if(!$this->getPlugin()->isVip($player, true)){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Vip");
			$player->getInventory()->addItem($item);
			return true;
		}, 58, "Monthly Voter Kit (1m)");
		
		$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Ultra")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Ultra");
			return true;
		}, 86, "Ultra Rank (1m, 2w)");
		
		$this->setupReward(function(Player $player) : bool{
			$item = ItemUtils::get("supreme_voter_gem");
			if(!$this->getPlugin()->testSlot($player, 1)){
				return false;
			}
			if(!$this->getPlugin()->isVip($player, true)){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Vip");
			$player->getInventory()->addItem($item);
			return true;
		}, 58, "Supreme Voter Kit (1m)");
		
		$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Ultimate")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Ultimate");
			return true;
		}, 114, "Ultimate Rank (2m)");
		
		/*$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Nightmare")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Nightmare");
			return true;
		}, 142, "Nightmare (2m, 2w)");
		
		$this->setupReward(function(Player $player) : bool{
			if($this->getPlugin()->hasPlayerRank($player, "Universe")){
				return false;
			}
			$this->getPlugin()->permissionManager->setPlayerGroup($player, "Universe");
			return true;
		}, 142, "Universe (2m, 4w)");*/
		
		usort($this->rewards, function($rewardA, $rewardB) : int{
			return $rewardA->cost < $rewardB->cost ? -1 : 1;
		});
		return true;
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset($args[0])){
		    switch(mb_strtolower($args[0])){
			    case "see":
					if(count($args) < 2){
                    	LangManager::send("vp-see-usage", $sender);
                    	break;
					}
					$target = $this->getPlugin()->getPlayer($args[1]);
			    	if($target === null){
                    	LangManager::send("player-notfound", $sender);
                    	break;
					}
					LangManager::send("vp-see", $sender, $target->getName(), $this->getVp($target));
					break;
				case "add":
					if(!$sender->isOp()){
						return false;
					}
					if(count($args) < 3){
                    	LangManager::send("vp-set-usage", $sender);
                    	break;
					}
					$target = $this->getPlugin()->getPlayer($args[1]);
			    	if($target === null){
                    	LangManager::send("player-notfound", $sender);
                    	break;
					}
					if(!is_numeric($args[2]) || $args[2] < 1){
						LangManager::send("positive-value", $sender);
						break;
					}
					$this->addVp($target, (int) round($args[2]));
				    LangManager::send("vp-set", $sender, $target->getName(), $this->getVp($target));
					break;
				case "subtract":
					if(!$sender->isOp()){
						return false;
					}
					if(count($args) < 3){
                    	LangManager::send("vp-set-usage", $sender);
                    	break;
					}
					$target = $this->getPlugin()->getPlayer($args[1]);
			    	if($target === null){
                    	LangManager::send("player-notfound", $sender);
                    	break;
					}
					if(!is_numeric($args[2]) || $args[2] < 1){
						LangManager::send("positive-value", $sender);
						break;
					}
					$this->subtractVp($target, (int) round($args[2]));
				    LangManager::send("vp-set", $sender, $target->getName(), $this->getVp($target));
					break;
			}
			return true;
		}
		$form = new SimpleForm(function(Player $player, ?int $option) use(&$form){
			if(is_int($option) && isset($this->rewards[$option]) && $player->isOnline()){
				//Handle meeh
				$reward = $this->rewards[$option];
				$formB = new ModalForm(function(Player $player, ?bool $confirm) use(&$form, $reward){
					if($player->isOnline()){
						if($confirm){
							if($this->getVp($player) < $reward->cost){
								LangManager::send("vp-novp", $player, $reward->cost, $reward->name);
							}else{
								$this->subtractVp($player, $reward->cost);
								$closure = $reward->execute;
								if($closure($player)){
									LangManager::send("vp-buy", $player, $reward->name, $reward->cost);
								}else{
									LangManager::send("vp-buyerror", $player);
								}
							}
						}else{
							$player->sendForm($form);
						}
					}
				});
				$formB->setTitle(LangManager::translate("vp-buytitle", $player));
        		$formB->setContent(LangManager::translate("vp-buydesc", $player, $reward->name, $reward->cost));
        		$formB->setButton1(LangManager::translate("continue", $player), 1);
        		$formB->setButton2(LangManager::translate("cancel", $player), 2);
        		$player->sendForm($formB);
			}
		});
		$form->setTitle(LangManager::translate("vp-title", $sender));
		$form->setContent(LangManager::translate("vp-me", $sender, $this->getVp($sender)));
		foreach($this->rewards as $reward){
			$form->addButton(LangManager::translate("vp-prize", $sender, $reward->name, $reward->cost));
		}
		$sender->sendForm($form);
		return true;
	}
	
	/**
	 * @param \Closure $closure If true deducts the $cost vote points
	 * @param int $cost
	 * @param string $name
	 */
	private function setupReward(\Closure $closure, int $cost, string $name) : void{
		//Fuck PHP, ReflectionFunction->__toString() is undefined to conditions but to var_dump
		/*$reflection = new \ReflectionFunction($closure);
		var_dump($reflection->getReturnType()->__toString());
		if($reflection->getReturnType()->__toString() !== "bool"){
			throw new \InvalidArgumentException(__METHOD__ . ": Argument 1 / closure must have a bool return type");
		}*/
		$reward = new \StdClass();
		$reward->name = $name;
		$reward->cost = $cost;
		$reward->execute = $closure;
		$this->rewards[] = $reward;
	}
	
	/**
	 * @param Player $player
	 * @return int
	 */
	private function getVp(Player $player) : int{
    	return (int) $this->getPlugin()->getEntry($player, Main::ENTRY_VOTEPOINTS);
    }
	 
	/**
	 * @param Player $player
	 * @param int $points
	 * @return bool
	 */
	private function subtractVp(Player $player, int $points) : bool{
    	if($this->getVp($player) - abs($points) >= 0){
    		$this->getPlugin()->registerEntry($player, Main::ENTRY_VOTEPOINTS, $this->getVp($player) - abs($points));
    		return true;
    	}
    	return false;
    }
    
	/**
	 * @param Player $player
	 * @param int $points
	 */
    private function addVp(Player $player, int $points) : void{
    	$this->getPlugin()->registerEntry($player, Main::ENTRY_VOTEPOINTS, $this->getVp($player) + abs($points));
    }

}