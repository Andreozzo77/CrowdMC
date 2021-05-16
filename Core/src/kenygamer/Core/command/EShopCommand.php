<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;

class EShopCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"eshop",
			"Buy a vanilla enchant book with EXP",
			"/eshop",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		static $ENCHANTS = [
			Enchantment::PROTECTION => 5000,
			Enchantment::FEATHER_FALLING => 5000,
			Enchantment::RESPIRATION => 5000,
			Enchantment::DEPTH_STRIDER => 5000,
			Enchantment::SHARPNESS => 7500,
			Enchantment::KNOCKBACK => 8000,
			Enchantment::FIRE_ASPECT => 8000,
			Enchantment::EFFICIENCY => 8000,
			Enchantment::SILK_TOUCH => 25000,
			Enchantment::UNBREAKING => 10000,
			Enchantment::FORTUNE => 8000,
			Enchantment::POWER => 4000,
			Enchantment::PUNCH => 8000
		];
		/** @var $NAMES An unordered array of enchant names mapped by IDs */
		static $NAMES = [];
		foreach((new \ReflectionClass(Enchantment::class))->getConstants() as $name => $id){
			foreach($ENCHANTS as $enchant => $exp){
				if($id === $enchant && strpos($name, "SLOT") === false && strpos($name, "RARITY") === false){
					$NAMES[$id] = ucfirst(mb_strtolower(str_replace("_", " ", $name)));
				}
			}
		}
		$form = new SimpleForm(function(Player $player, ?string $id) use($ENCHANTS, $NAMES){
			if($id === null){
				return;
			}
			$name = $NAMES[$id];
			$book = ItemFactory::get(Item::ENCHANTED_BOOK, 0, 1); //Not book
			$book->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment((int) $id), 1));
			$exp = $ENCHANTS[$id];
			if(($diff = $player->getCurrentTotalXp() - $exp) < 0){
				$player->sendMessage("exp-needed-more", abs($diff));
				return;
			}
			if(ItemUtils::addItems($player->getInventory(), $book)){
				$player->subtractXp($exp);
				$player->sendMessage("eshop-buy", $name, number_format($exp));
			}else{
				$player->sendMessage("inventory-nospace");
			}
		});
		$form->setTitle(LangManager::translate("eshop-title", $sender));
		$form->setContent(LangManager::translate("eshop-content", $sender));
		foreach($NAMES as $id => $name){
			$form->addButton(LangManager::translate("eshop-enchant", $sender, $name, number_format($ENCHANTS[$id])), -1, "", (string) $id);
		}
		$sender->sendForm($form);
		return true;
	}
	
}