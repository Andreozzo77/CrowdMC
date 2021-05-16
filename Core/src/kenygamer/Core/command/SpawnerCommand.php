<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\block\MonsterSpawner;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class SpawnerCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"spawner",
			"Buy a spawner with EXP",
			"/spawner",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	/**
	 * @todo
	 */
	protected function onExecute($sender, array $args) : bool{

	    $form = new SimpleForm(function(Player $player, ?string $spawnerName){
	    	if($spawnerName !== null && isset($this->getPlugin()->spawners[$spawnerName])){
	    		$form = new CustomForm(function(Player $player, ?array $data) use($spawnerName){
	    			if(isset($data[1]) && ($quantity = intval($data[1])) > 0){
	    				if(!$this->getPlugin()->canAffordSpawner($player, $spawnerName)){
	    					$player->sendMessage("spawners-buy-error");
	    					return;
	    				}
	    				$block = new MonsterSpawner();
	    				$block->entityId = $this->getPlugin()->getEntityId($spawnerName);
	    				$block->isValid = 1;
	    				$spawner = $block->getSilkTouchDrops($player->getInventory()->getItemInHand())[0];
	    				$spawner->setCount($quantity);
	    				if(!$player->getInventory()->canAddItem($spawner)){
	    					$player->sendMessage("inventory-nospace");
	    				}else{
	    					$exp = $this->getPlugin()->spawners[$spawnerName]["exp"] * $quantity;
	    					if($player->getCurrentTotalXp() < $exp){
								$player->sendMessage("exp-needed-more", $exp - $player->getCurrentTotalXp());
	    					}else{
	    						$player->subtractXp($exp);
	    						$player->getInventory()->addItem($spawner);
								$player->sendMessage("spawners-buy", $quantity, $spawnerName, $exp);
	    					}
	    				}
	    			}
	    		});
	    		$form->setTitle(LangManager::translate("spawners-buy-title", $player, $spawnerName));
	    		$form->addLabel(LangManager::translate("spawners-buy-desc", $player, $this->getPlugin()->spawners[$spawnerName]["exp"]));
	    		$form->addSlider(LangManager::translate("quantity", $player), 1, 64);
	    		$form->sendToPlayer($player);
	    	}
	    });
	    $form->setTitle(LangManager::translate("spawners-title", $sender));
	    $form->setContent(LangManager::translate("spawners-desc", $sender));
	    foreach($this->getPlugin()->spawners as $spawnerName => $data){
	    	$form->addButton(LangManager::translate("spawners-spawner", $sender, $spawnerName, $data["exp"]), -1, "", $spawnerName);
	    }
	    $form->sendToPlayer($sender);
	    return true;
	}
	
}