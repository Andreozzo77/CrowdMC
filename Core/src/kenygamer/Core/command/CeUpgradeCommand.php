<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\enchantment\Enchantment;
use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;

class CeUpgradeCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"ceupgrade",
			"Upgrade your custom enchant book",
			"/ceupgrade <enchant> [levelWanted]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"	
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		/** @var int[] */
	    $RESERVED_ENCHANTS = [
	        CustomEnchantsIds::JACKHAMMER, CustomEnchantsIds::TOKENMASTER, CustomEnchantsIds::EXPLOSIVE,
	        CustomEnchantsIds::FROSTBITE, CustomEnchantsIds::PENETRATING
	    ];
	    $enchant = $args[0];
	    $enchantment = CustomEnchants::getEnchantmentByName($enchant);
	    if($enchantment === null){
			$sender->sendMessage("enchant-notfound");
	    	return true;
	    }
	    if(in_array($enchantment->getId(), $RESERVED_ENCHANTS)){
			$sender->sendMessage("eupgrade-disallowed");
	    	return true;
	    }
	    
	    $item = $sender->getInventory()->getItemInHand();
	    if(!$item->hasEnchantments()){
			$sender->sendMessage("hold-enchantedbook");
	    	return true;
	    }
	    $e = $item->getEnchantment($enchantment->getId());
	    if($e === null){
			$sender->sendMessage("eupgrade-notfound");
	    	return true;
	    }
		$levelWanted = intval($args[1] ?? null);
	    $levelWanted = $levelWanted <= $e->getLevel() ? ($e->getLevel() + 1) : $levelWanted;
	    if($levelWanted > $enchantment->getMaxLevel()){
			$sender->sendMessage("eupgrade-level", $enchantment->getMaxLevel());
	    	return true;
	    }
	    
	    $TOKENS_COST = [
	        Enchantment::RARITY_COMMON => 12,
	        Enchantment::RARITY_UNCOMMON => 16,
	        Enchantment::RARITY_RARE => 20,
	        Enchantment::RARITY_MYTHIC => 24
	    ];
	    $tokens = $TOKENS_COST[$enchantment->getRarity()] * ($levelWanted - $e->getLevel());
	    if($this->getPlugin()->subtractTokens($sender, $tokens)){
	    	if($levelWanted === $enchantment->getMaxLevel()){
	    		$this->getPlugin()->questManager->getQuest("ultimate_upgrader")->progress($sender, 1, $enchantment->getRarity());
	    	}
	    	$sender->getInventory()->removeItem($item);
			$sender->sendMessage("eupgrade-upgraded", $enchantment->getName(), $this->getPlugin()->getPlugin("CustomEnchants")->getRomanNumber($levelWanted), $tokens);
	    	$new = $this->getPlugin()->getPlugin("CustomEnchants")->addEnchantment($item, [$enchantment->getId()], [$levelWanted], false);
	    	$sender->getInventory()->addItem($new);
	    }else{
			$sender->sendMessage("tokens-needed", $tokens);
	    }
	    return true;
	}
	
}