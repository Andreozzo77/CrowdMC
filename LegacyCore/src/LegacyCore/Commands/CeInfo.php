<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as Color;

use jojoe77777\FormAPI;
use CustomEnchants\Main;
use CustomEnchants\CustomEnchants\CustomEnchants;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class CeInfo extends PluginCommand{
	/** @var Core */
	private $plugin;
	/** @var Main */
	private $ce;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("View more information of custom enchants");
        $this->setUsage("/ceinfo");
        $this->setAliases(["ces"]);
        $this->setPermission("core.command.ceinfo");
        
		$this->plugin = $plugin;
		$this->ce = $this->plugin->getServer()->getPluginManager()->getPlugin("CustomEnchants");
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$sender->hasPermission("core.command.ceinfo")){
			LangManager::send("cmd-noperm", $sender);
			return true;
		}
		if(!($sender instanceof Player)){
			LangManager::send("run-ingame", $sender);
			return true;
		}
		if(!isset($args[0])){
			return $this->CeInfoUI($sender);
		}else{
			$enchant = implode(" ", $args);
			foreach($this->getCategories() as $name => $category){
				foreach($category as $ench){
					if(strcasecmp(str_replace(" ", "", $enchant), str_replace(" ", "", $ench[0])) === 0){
						$this->sendEnchantInfo($sender, $ench[0], $category);
						return true;
					}
				}
			}
			LangManager::send("enchant-notfound", $sender);
			return true;
		}
	}
	
	private function getCategories() : array{
		$categories = [];
		foreach($this->ce->enchants as $enchantID => $data){
			$categories[$data[1]][] = [$data[0], $data[3], $data[4], $data[5]];
		}
		return $categories;
	}
	
	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	private function CeInfoUI(Player $player) : bool{
		$categories = $this->getCategories();
		$form = new SimpleForm(function(Player $player, ?string $category) use($categories){
			if(is_string($category) && isset($categories[$category])){
				$this->sendCategory($player, $categories[$category]);
			}
		});
		$form->setTitle(LangManager::translate("core-ceinfo-title", $player));
		$form->setContent(LangManager::translate("core-ceinfo-desc", $player));
		foreach($categories as $name => $category){
			$form->addButton(Color::BLUE . $name, -1, "", $name);
		}
		$form->sendToPlayer($player);
		return true;
	}
	
	/**
	 * @param Player $player
	 * @param array $category
	 */
	private function sendCategory(Player $player, array $category) : void{
		$parts = array_chunk($category, 10, true);
		$form = new CustomForm(function(Player $player, ?array $data) use($category, $parts){
			foreach($data ?? [] as $page => $selected){
				if($selected > 0){
					$enchantIndex = ($page * 10) + $selected - 1;
					$enchant = $category[$enchantIndex][0];
					$this->sendEnchantInfo($player, Color::clean($enchant), $category);
				    return;
				}
			}
			$this->CeInfoUI($player);
		});
		foreach($parts as $i => $part){
			foreach($part as $enchant){
				foreach($this->ce->enchants as $enchantID => $ench){
					if($ench[0] === $enchant[0]){
						$categoryStr = $ench[1];
						$form->setTitle(LangManager::translate("core-ceinfo-titlecustom", $player, $categoryStr));
						break 3;
					}
				}
			}
		}
		$names = [];
		foreach($parts as $i => $part){
			foreach($part as $enchant){
				$names[$i][] = Color::BLUE . $enchant[0];
			}
			array_unshift($names[$i], Color::BLACK . "-");
			$form->addDropdown(Color::DARK_GREEN . "Page " . ($i + 1), $names[$i]);
		}
		$form->sendToPlayer($player);
	}
	
	/**
	 * @param Player $player
	 * @param string $enchant
	 * @param array $category
	 */
	private function sendEnchantInfo(Player $player, string $enchant, array $category) : void{
		$form = new CustomForm(function(Player $player, ?array $data) use($category){
			$this->sendCategory($player, $category);
		});
		foreach($category as $entry){
			list($name, $rarity, $maxlevel, $description) = $entry;
			if($name === $enchant) break;
		}
		foreach($this->ce->enchants as $enchantID => $ench){
			if($ench[0] === $enchant){
				$ce = CustomEnchants::getEnchantment($enchantID);
				$categoryStr = $ench[1];
				$form->setTitle(LangManager::translate("core-ceinfo-titlecustom", $player, $name));
				$incompatibilities = [];
				foreach($this->ce->incompatibilities as $_ce => $celist){
					if($enchantID === $_ce || in_array($enchantID, $celist)){
						$incompatibilities = $incompatibilities + array_map(function(int $ce) : string{
							return $this->ce->enchants[$ce][0];
						}, array_unique(array_diff(array_merge([$_ce], $celist), [$enchantID])));
					}
				}
				$form->addLabel(LangManager::translate("core-ceinfo-ce-desc", $player, $name, str_replace("%", "%%", $description), $this->ce->getRarityColor($ce->getRarity()) . $rarity, $maxlevel, $categoryStr, implode(", ", $incompatibilities)));
				$form->sendToPlayer($player);
				break;
			}
		}
	}
	
}