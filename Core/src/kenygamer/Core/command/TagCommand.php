<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;

class TagCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"tag",
			"Change your tag",
			"/tag",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$form = new SimpleForm(function(Player $player, ?string $tag){
			if($tag !== null){
				if(in_array($tag, $this->getPlugin()->tags->get($player->getName(), [])) && isset($this->getPlugin()->tagList[$tag])){
					$tagName = $this->getPlugin()->tagList[$tag];
					if($this->getPlugin()->permissionManager->getPlayerSuffix($player) === $tagName){
						$player->sendMessage("tag-using");
					}else{
						$this->getPlugin()->permissionManager->setPlayerSuffix($player, $tagName);
						$player->sendMessage("tag-changed", TextFormat::clean($tagName));
					}
				}elseif(isset($this->getPlugin()->tagList[$tag])){
					$player->sendMessage("tag-notunlocked");
				}else{
					$this->getPlugin()->permissionManager->setPlayerSuffix($player, "");
					$player->sendMessage("tag-removed");
				}
			}
		});
		$form->setTitle(TextFormat::colorize("&a&lChoose Tag"));
		$content = LangManager::translate("tag-content", $sender) . "\n\n";
		$currentTag = $this->getPlugin()->permissionManager->getPlayerSuffix($sender);
		if(empty($currentTag)){
			$content .= LangManager::translate("tag-notset", $sender);
		}else{
			$content .=  LangManager::translate("tag-set", $sender, TextFormat::clean($currentTag));
		}
		$form->setContent(TextFormat::colorize($content));
		foreach($this->getPlugin()->tagList as $i => $tag){
			$form->addButton(TextFormat::colorize("&a" . $tag . "\n" . (in_array($i, $this->getPlugin()->tags->get($sender->getName(), [])) ? LangManager::translate("unlocked", $sender) : LangManager::translate("locked", $sender))), 0, "textures/items/name_tag", strval($i));
		}
		if(!empty($currentTag)){
			$form->addButton(LangManager::translate("tag-remove", $sender), 0, "textures/blocks/barrier", strval($i + 1));
		}
		$sender->sendForm($form);
		return true;
	}
	
}