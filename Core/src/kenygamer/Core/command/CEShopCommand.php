<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class CEShopCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"ceshop",
				"Buy a custom enchant book.",
			"/ceshop",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$rarities = [];
		foreach($this->getPlugin()->getConfig()->get("ceshop") as $rarity => $price){
			$int = $this->getPlugin()->getRarityByName($rarity);
			if($int === -1){
				continue;
			}
			$rarities[] = [$rarity, $int, $price];
		}
		$closure = function(Player $player, ?int $data) use($rarities){
			if($data === null || !$player->isOnline() || !isset($rarities[$data])){
				return;
			}
			$data = $rarities[$data];
			list($rarity, $int, $price) = $data;
			$closure = function(Player $player, ?array $data) use($rarity, $price){
				if($data === null){
					if($player->isOnline()){
						$player->chat("/ceshop");
					}
					return;
				}	
				$amount = (int) round($data[1]);
				
				$price = (int) round($price * $amount);
				if($player->getCurrentTotalXp() - $price <= 0){
					$player->sendMessage("exp-needed", $price);
				}else{
				    $book = ItemUtils::get(mb_strtolower($rarity) . "_book")->setCount($amount);
			        $player->getInventory()->addItem($book);
					$player->subtractXp($price);
				    $player->sendMessage("ceshop-success", $amount, $rarity . " Book", $price);
				}
			};
			$form = new CustomForm($closure);
        	$form->setTitle(LangManager::translate("ceshop-title-book", $player, $rarity));
			$form->addLabel(LangManager::translate("ceshop-cost", $player, $price));
        	$form->addSlider(LangManager::translate("ceshop-amount", $player) , 1, 64, 1);
        	$player->sendForm($form);
		};
		$form = new SimpleForm($closure);
		$form->setTitle(LangManager::translate("ceshop-title", $sender));
		$form->setContent(LangManager::translate("ceshop-desc", $sender));
		$ce = $this->getPlugin()->getPlugin("CustomEnchants");
		foreach($rarities as $data){
			list($rarity, $int, $price) = $data;
			$form->addButton(LangManager::translate("ceshop-book", $sender, $ce->getRarityColor($int) . $rarity, $price));
		}
		$sender->sendForm($form);
		return true;
	}
	
}