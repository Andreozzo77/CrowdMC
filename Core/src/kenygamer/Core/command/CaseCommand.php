<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\LangManager;
use pocketmine\utils\TextFormat;

class CaseCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"case",
			"View a report case",
			"/case <id>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$id = str_replace("#", "", $args[0]);
        if(!$this->getPlugin()->cases->get($id)){
            $sender->sendMessage("case-notfound");
            return true;
        }
        $player = $this->getPlugin()->cases->get($id)["player"];
        $reason = $this->getPlugin()->cases->get($id)["reason"];
        $form = new SimpleForm(function(Player $sender, $data) use($id, $player, $reason){
            if($data !== null){
                switch($data){
                    case 0:
                        $status = "approved";
                        $this->getPlugin()->registerWarn($this->getPlugin()->getServer()->getOfflinePlayer($player), $reason, $sender->getName());
                        break;
                    case 1:
                        $status = "rejected";
                        break;
                    case 2:
                        $this->getPlugin()->getServer()->dispatchCommand($sender, "cases");
                        return;
                }
                $this->getPlugin()->cases->remove($id);
                $sender->sendMessage("case-solved", $id);
				$cfg = $this->getPlugin()->getConfig()->get("links-api");
                $this->getPlugin()->makeHttpGetRequest($cfg["url"], [
                    "serverID" => $cfg["server-id"],
                    "serverKey" => $cfg["server-key"],
                    "action" => "sendDiscordWebhook",
                    "url" => $this->getPlugin()->getConfig()->getNested("discord-webhooks.player-reports"),
                    "message" => urlencode("**Case #" . $id . " has been {$status} by {$sender->getName()}.**")
                ], 1, 1, true, 1, []);
            }
        });
        $form->setTitle(LangManager::translate("case-title", $sender, $id));  
        $messages = $this->getPlugin()->cases->get($id)["messages"];
        if(count($messages) > 0){
            $content = LangManager::translate("case-chatlog", $sender, $player) . "\n";
        }else{
            $content = LangManager::translate("case-nochatlog", $sender, $player);
        }
        foreach(array_reverse($messages) as $k => $msg){
            $n = count($messages) - $k;
            //intl extension
            //$n = (new \NumberFormatter($locale, \NumberFormatter::ORDINAL))->format($n);
            $content .= "\n&e{$n} > &6" . TextFormat::clean($msg);
        }
        $content .= "\n\n" . LangManager::translate("case-note", $sender);
        $form->setContent(TextFormat::colorize($content));
        $form->addButton(LangManager::translate("case-warn", $sender, $this->getPlugin()->getConfig()->getNested("rules.list")[$reason]));
        $form->addButton(LangManager::translate("case-reject", $sender));
        $form->addButton(LangManager::translate("case-list", $sender));
        $sender->sendForm($form);
        return true;
	}
	
}