<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\listener\MiscListener2;

class ReportCommand extends BaseCommand{
	/** @var int */
	private $cooldown = [];
	
	public function __construct(){
		parent::__construct(
			"report",
			"Report a player",
			"/report",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$list = [];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player){
			if(!$this->getPlugin()->isStaff($player)){
				$list[] = $player->getName();
			}
		}
        $form = new CustomForm(function(Player $sender, ?array $data) use($list){
			if(!is_array($data)){
    			return;
            }
            $player = $data[3];
            $reportReason = $data[4];
            //Remove formatting codes/line breaks
            $additionalNotes = str_replace(["*", "~", "_", "\n", "@"], "", $data[5] ?? "");
            $confirm = $data[6];
            if(!isset($list[$player])){
                return;
            }
            $rules = $this->getPlugin()->getConfig()->getNested("rules.list");
            if(!$confirm){
                $sender->sendMessage("report-confirm");
                return;
            }
            if(!isset($this->cooldown[$sender->getName()]) || time() - $this->cooldown[$sender->getName()] >= 300){
                $name = $list[$player];
                $reason = $rules[$reportReason];
				$cfg = $this->getPlugin()->getConfig()->get("links-api");
                $this->getPlugin()->makeHttpGetRequest($cfg["url"], [
                    "serverID" => $cfg["server-id"],
                    "serverKey" => $cfg["server-key"],
                    "action" => "sendDiscordWebhook",
                    "url" => $this->getPlugin()->getConfig()->getNested("discord-webhooks.reports"),
                    "message" => urlencode("**{$name} report (Case #" . $this->createCase($name, $reportReason) . ")**\n\nReported By: {$sender->getName()}\nReport Reason: {$reason}\nAdditional Notes: {$additionalNotes}")
                ], 1, 1, true, 1, []);
                $this->cooldown[$sender->getName()] = time();
                $sender->sendMessage("report-filled");
            }else{
                $sender->sendMessage("in-cooldown");
            }
        });
        $form->setTitle(LangManager::translate("report-title", $sender));
        $form->addLabel(LangManager::translate("report-desc", $sender));
        $form->addLabel(LangManager::translate("report-desc2", $sender));
        $form->addLabel(LangManager::translate("report-desc3", $sender));
        if(empty($list)){
            $sender->sendMessage("noplayers");
            return true;
        }
        $form->addDropdown(LangManager::translate("player", $sender), $list);
        $form->addDropdown(LangManager::translate("report-reportreason", $sender), $this->getPlugin()->getConfig()->getNested("rules.list"));
        $form->addInput(LangManager::translate("report-notes", $sender));
        $form->addToggle(LangManager::translate("report-checkbox", $sender));
        $sender->sendForm($form);
        return true;
	}
	
	/**
	 * @return int
	 */
	private function findCaseId() : int{
		$min = 1000;
		$max = 9999;
    	$count = count($this->getPlugin()->cases->getAll());
    	while($count >= $max - $min + 1){
    		$min *= 2; //1000 -> 10000 -> 100000 ...
    		$max += ($max * 10) + 10; //9999 -> 99999 -> 999999 ...
        }
        find: {
    		$id = \kenygamer\Core\Main::mt_rand($min, $max);
    	}
        while($this->getPlugin()->cases->get($id)){
        	goto find;
        }
        return $id;
	}
	
    /**
	 * @param string $player
	 * @param int $reason
	 * @return int Case ID
	 */
	private function createCase(string $player, int $reason) : int{
		$messages = MiscListener2::$lastMessages[$player] ?? [];
        $this->getPlugin()->cases->set($id = $this->findCaseId(), [
            "player" => $player,
            "messages" => $messages,
            "reason" => $reason,
            "timeOpened" => time()
        ]);
        return $id;
    }
	
}