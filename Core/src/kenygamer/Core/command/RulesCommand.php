<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\LangManager;

class RulesCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"rules",
			"View the server rules",
			"/rules",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$form = new SimpleForm(null);
        $form->setTitle(LangManager::translate("rules-title", $sender));
        $content = LangManager::translate("rules-desc", $sender, $this->getPlugin()->getWarnPoints($sender->getName()), $this->getPlugin()->getConfig()->get("warns-before-ban")) . "\n";
		$sanctions = $this->getPlugin()->getConfig()->getNested("rules.sanctions");
        foreach($this->getPlugin()->getConfig()->getNested("rules.list") as $i => $rule){
            $content .= "\n&e" . $rule . " - &6" . $sanctions[$i] . " warn point" . ($sanctions[$i] == 1 ? "" : "s");
        }
        $form->setContent(TextFormat::colorize($content));
        $form->addButton(LangManager::translate("ok", $sender)); 
        $sender->sendForm($form);
		return true;
	}
	
}