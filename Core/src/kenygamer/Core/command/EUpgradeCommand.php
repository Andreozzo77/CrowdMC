<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\util\ItemUtils;
use pocketmine\item\enchantment\Enchantment;

class EUpgradeCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"eupgrade",
			"Upgrade your vanilla enchant",
			"/eupgrade <enchant> [levelWanted]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
	    $item = $sender->getInventory()->getItemInHand();
	    if(count($item->getEnchantments()) < 1){ //Item::hasEnchantments() Checks for the presence of the ench tag
	    	$sender->sendMessage("hold-enchantedbook");
	    	return true;
		}
	    $TOKENS_COST = [
	        Enchantment::PROTECTION => 8,
	        Enchantment::FEATHER_FALLING => 6,
	        Enchantment::RESPIRATION => 6,
	        Enchantment::DEPTH_STRIDER => 6,
	        Enchantment::SHARPNESS => 8,
	        Enchantment::KNOCKBACK => 30,
	        Enchantment::FIRE_ASPECT => 30,
	        Enchantment::EFFICIENCY => 30,
	        Enchantment::SILK_TOUCH => 31,
	        Enchantment::UNBREAKING => 38,
	        Enchantment::FORTUNE => 30,
	        Enchantment::POWER => 15,
	        Enchantment::PUNCH => 30 
	    ];
	    $enchant = mb_strtolower(array_shift($args));
	    $eid = -1;
	    $enchantments = (new \ReflectionClass(Enchantment::class))->getConstants();
	    foreach($enchantments as $enchantment => $id){
	    	if(mb_strtolower($enchantment) === $enchant && in_array($id, Main2::VANILLA_ENCHANT_LIST) && isset($TOKENS_COST[$id])){
	    		$eid = $id;
	    		break;
	    	}
	    }
	    if($eid === -1){
	    	$sender->sendMessage("eupgrade-disallowed");
	    	return true;
	    }
	    $enchantment = $item->getEnchantment($eid);
	    if($enchantment === null){
	    	$sender->sendMessage("eupgrade-notfound");
	    	return true;
	    }
	    $levelWanted = array_shift($args);
	    $levelWanted = intval($levelWanted) <= $enchantment->getLevel() ? ($enchantment->getLevel() + 1) : intval($levelWanted);
	    $tokensNeeded = $TOKENS_COST[$eid] * ($levelWanted - $enchantment->getLevel());
	    if($levelWanted > ($maxLevel = $enchantment->getType()->getMaxLevel())){
	    	$sender->sendMessage("eupgrade-level", $maxLevel);
	    	return true;
	    }
	    if(Main::getInstance()->subtractTokens($sender, $tokensNeeded)){
	    	ItemUtils::addEnchantments($item, [$enchant => $levelWanted]);
	    	$sender->getInventory()->setItemInHand($item);
	    	$sender->sendMessage("eupgrade-upgraded", $enchant, $levelWanted, $tokensNeeded);
	    }else{
	    	$sender->sendMessage("tokens-needed", $tokensNeeded);
	    }
	    return true;
	}
	
}