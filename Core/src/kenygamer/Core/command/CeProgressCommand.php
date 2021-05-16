<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use pocketmine\item\Item;
use jojoe77777\FormAPI\SimpleForm;
use CustomEnchants\CustomEnchants\CustomEnchants;

class CeProgressCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"ceprogress",
			"View the custom enchants progress in your item",
			"/ceprogress",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$item = $sender->getInventory()->getItemInHand();
		$ce = $this->getPlugin()->getPlugin("CustomEnchants");
		$text = "";
		$maxlvls = $countlvls = 0;
		foreach($ce->enchants as $id => $data){
			$name = $data[0];
			$maxlevel = $data[4];
			$description = $data[5];
			$enchantment = CustomEnchants::getEnchantment($id);
			$havelevel = ($e = $item->getEnchantment($id)) ? $e->getLevel() : 0;
			if($ce->canBeEnchanted($item, $enchantment, $havelevel < 1 ? 1 : $havelevel) === true){
				$countlvls += $havelevel;
				$maxlvls += $maxlevel;
				if($havelevel === 0){
					$text .= LangManager::translate("ceprogress-enchant", $sender, $name) . "\n";
				}elseif($havelevel < $maxlevel){
					$text .= LangManager::translate("ceprogress-enchant-have-incomplete", $sender, $name, round(($havelevel / $maxlevel) * 100)) . "\n";
				}else{
					$text .= LangManager::translate("ceprogress-enchant-have-complete", $sender, $name) . "\n";
				}
			}elseif($havelevel && $havelevel < $maxlevel){
				$countlvls += $havelevel;
				$maxlvls += $maxlevel;
				$text .= LangManager::translate("ceprogress-enchant-have-incomplete", $sender, $name, round(($havelevel / $maxlevel) * 100)) . "\n";
			}
		}
		
	    if($text === "" || $item->getId() === Item::BOOK){
	    	$sender->sendMessage("ceprogress-invalid");
	    	return true;
	    }
		$text = explode("\n", $text);
		arsort($text, SORT_NATURAL);
		$text = implode("\n", $text);
	    $form = new SimpleForm(null);
	    $form->setTitle(LangManager::translate("ceprogress-title"));
	    $form->setContent(LangManager::translate("ceprogress-progress", round(($countlvls / $maxlvls) * 100), ItemUtils::getDescription($item)) . "\n\n" . $text);
	    $form->addButton(LangManager::translate("ok", $sender));
	    $sender->sendForm($form);
		return true;
	}
	
}