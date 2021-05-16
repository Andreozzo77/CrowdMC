<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main2;
use kenygamer\Core\LangManager;
use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;

class CosmeticCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"cosmetic",
			"Wear a cosmetic",
			"/cosmetic",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$form = new SimpleForm(function(Player $player, ?int $option){
			switch($option){
				case 0:
					$capes = [];
				    foreach($this->getPlugin()->designs as $name => $raw){
				    	if(strpos($name, "_cape") !== false){ //assumed *_cape.js, but we cannot check extension
				    		$niceName = str_replace("_cape", "", $name);
				    		$niceName = implode(" ", array_map(function($part){
				    			return ucfirst($part);
				    		}, explode("_", $niceName)));
				    		$capes[] = [$name, $niceName];
				    	}
				    }
				    $form = new SimpleForm(function(Player $player, ?int $cape) use($capes){
				    	if($cape !== null){
				    		if(isset($capes[$cape])){
				    			list($name, $niceName) = $capes[$cape];
				    			Main2::updateCape($player, $name);
				    			Main2::$cosmetics->setNested($player->getName() . ".cape", $name);
				    			$player->sendMessage("cosmetic-cape-changed", $niceName);
				    		}else{
				    			Main2::updateCape($player, "");
				    			$data = Main2::$cosmetics->get($player->getName());
								unset($data["cape"]);
								Main2::$cosmetics->set($player->getName(), $data);
				    			$player->sendMessage("cosmetic-cape-removed");
				    		}
				    	}
				    });
				    $form->setTitle(LangManager::translate("cosmetic-cape-title", $player));
				    $form->setContent(LangManager::translate("cosmetic-cape-desc", $player));
				    foreach($capes as $cape){
				    	list($name, $niceName) = $cape;
				    	$form->addButton(LangManager::translate("cosmetic-cape-cape", $player, $niceName));
				    }
				    $form->addButton(LangManager::translate("cosmetic-cape-remove", $player));
				    $player->sendForm($form);
				   	break;
				case 1:
					$hats = [];
				    foreach($this->getPlugin()->models as $name => $raw){
				    	if(strpos($name, "_hat") !== false){
				    		$niceName = str_replace("_hat", "", $name);
				    		$niceName = implode(" ", array_map(function($part){
				    			return ucfirst($part);
				    		}, explode("_", $niceName)));
				    		$hats[] = [$name, $niceName];
				    	}
				    }
				    $form = new SimpleForm(function(Player $player, ?int $hat) use($hats){
				    	if($hat !== null){
				    		if(isset($hats[$hat])){
				    			list($name, $niceName) = $hats[$hat];
				    			Main2::updateCosmetic($player, $name, true);
								
				    			Main2::$cosmetics->setNested($player->getName() . ".cosmetic", $name);
				    			$player->sendMessage("cosmetic-hat-changed", $niceName);
				    		}else{
				    			Main2::updateCosmetic($player, "", true);
				    			$data = Main2::$cosmetics->get($player->getName());
								unset($data["cosmetic"]);
								Main2::$cosmetics->set($player->getName(), $data);
				    			$player->sendMessage("cosmetic-hat-removed");
				    		}
				    	}
				    });
				    $form->setTitle(LangManager::translate("cosmetic-hat-title", $player));
				    $form->setContent(LangManager::translate("cosmetic-hat-desc", $player));
				    foreach($hats as $hat){
				    	list($name, $niceName) = $hat;
				    	$form->addButton(LangManager::translate("cosmetic-hat-cosmetic", $player, $niceName));
				    }
				    $form->addButton(LangManager::translate("cosmetic-hat-remove", $player));
				    $player->sendForm($form);
					break;
				case 2: //Same as hat, alters the geometry
					$suits = [];
				    foreach($this->getPlugin()->models as $name => $raw){
				    	if(strpos($name, "_suit") !== false){
				    		$niceName = str_replace("_suit", "", $name);
				    		$niceName = implode(" ", array_map(function($part){
				    			return ucfirst($part);
				    		}, explode("_", $niceName)));
				    		$suits[] = [$name, $niceName];
				    	}
				    }
				    $form = new SimpleForm(function(Player $player, ?int $suit) use($suits){
				    	if($suit !== null){
				    		if(isset($suits[$suit])){
				    			list($name, $niceName) = $suits[$suit];
				    			Main2::updateCosmetic($player, $name, false);
								
				    			Main2::$cosmetics->setNested($player->getName() . ".cosmetic", $name);
				    			$player->sendMessage("cosmetic-suit-changed", $niceName);
				    		}else{
				    			Main2::updateCosmetic($player, "", false);
				    			$data = Main2::$cosmetics->get($player->getName());
								unset($data["cosmetic"]);
								Main2::$cosmetics->set($player->getName(), $data);
				    			$player->sendMessage("cosmetic-suit-removed");
				    		}
				    	}
				    });
				    $form->setTitle(LangManager::translate("cosmetic-suit-title", $player));
				    $form->setContent(LangManager::translate("cosmetic-suit-desc", $player));
				    foreach($suits as $suit){
				    	list($name, $niceName) = $suit;
				    	$form->addButton(LangManager::translate("cosmetic-suit-suit", $player, $niceName));
				    }
				    $form->addButton(LangManager::translate("cosmetic-suit-remove", $player));
				    $player->sendForm($form);
					break;

			}
		});
		$form->setTitle(LangManager::translate("cosmetic-title", $sender));
		$form->addButton(LangManager::translate("cosmetic-cape", $sender));
		//Perhaps these should be in a single category?:
		$form->addButton(LangManager::translate("cosmetic-hat", $sender));
		$form->addButton(LangManager::translate("cosmetic-suit", $sender));
		$sender->sendForm($form);
		return true;
	}
	
	}