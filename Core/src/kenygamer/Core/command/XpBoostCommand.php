<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\LangManager;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;

class XpBoostCommand extends BaseCommand{

	public function __construct(){
		parent::__construct(
			"xpboost",
			"Buy an EXP boost for money",
			"/xpboost",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$xpboost = $this->getPlugin()->xpboost->get($sender->getName());
		if($xpboost === false || time() >= $xpboost[1]){
			
			//1-2 EXP per block: 3M equivalence of EXP mining 3000 blocks a minute:
			//4.5M equivalence of EXP with x1.5 multiplier (+1.5M/minute)
			//6M equivalence of EXP with x2.0 multiplier (+3M/minute)
			//7.5M equivalence of EXP with x2.5 multiplier (+4.5M/minute)
			//9M equivalence of EXP with x3.0 multiplier (+6M/minute) 
			
			//Type floats as strings. Float to string casting removes the decimal part.
            $boosts = ["1.5" => 1000000, 2 => 2000000, "2.5" => 3000000, 3 => 4000000];
            
	    	$form = new SimpleForm(function(Player $player, ?string $boost) use($boosts){
	    		if($boost !== null){
	    			/** @var int[] */
	    			$options = array_map(function(int $option){
	    				return strval($option);
	    			}, range(10, 60, 10));
	    			
	    			$form = new CustomForm(function(Player $player, ?array $data) use($boost, $boosts, $options){
	    			    if($data !== null){
	    			    	$time = $options[$data[1]];
	    			    	$cost = $boosts[$boost] * $time;
	    			    	if($player->reduceMoney($cost)){
	    			    		LangManager::send("xpboost-bought", $player, $boost);
	    			    		$this->getPlugin()->xpboost->set($player->getName(), [$boost, time() + ($time * 60)]);
	    			    	}else{
	    			    		LangManager::send("money-needed-more", $player, $cost - $player->getMoney());
	    			    	}
	    			    }
	    			});
	    			$form->setTitle(LangManager::translate("xpboost-typetitle", $player, $boost));
	    			$form->addLabel(LangManager::translate("xpboost-typecost", $player, $boosts[$boost]));
	    			$form->addStepSlider(LangManager::translate("xpboost-duration", $player), $options);
	    			$player->sendForm($form);
	    		}
	    	});
	    	$form->setTitle(LangManager::translate("xpboost-title", $sender));
	    	$form->setContent(LangManager::translate("xpboost-desc", $sender));
	    	foreach($boosts as $boost => $cost){
	    		$form->addButton(LangManager::translate("xpboost-multiplier", $sender, $boost), -1, "", is_int($boost) ? strval($boost) : $boost);
	    	}
	    	$sender->sendForm($form);
	    }else{
	    	LangManager::send("xpboost-active", $sender, $xpboost[0], $this->getPlugin()->formatTime($this->getPlugin()->getTimeLeft($xpboost[1]), TextFormat::AQUA, TextFormat::AQUA));
	    }
		return true;
	}
	
}