<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\LangManager;

class WarnCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"warn",
			"Warn a player",
			"/warn",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
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
	        if($data === null){
	            return;
	        }
	        $player = $data["player"];
	        $warnReason = $data["warnReason"];
	        $sanctionType = $data["sanctionType"];
	        $sanctionTime = $data["sanctionTime"];
	        if(!isset($list[$player])){
	            return;
	        }
	        $rules = $this->getPlugin()->getConfig()->getNested("rules.list");
			$warned = $this->getPlugin()->getServer()->getOfflinePlayer($list[$player]);
	        if($this->getPlugin()->isStaff($warned)){
	            $sender->sendMessage("warn-staff");
	            return;
	        }
	        $allPoints = $this->getPlugin()->getWarnPoints($warned->getName());
	        $warnPoints = $this->getPlugin()->getConfig()->getNested("rules.sanctions")[$warnReason];
	        $resultPoints = $allPoints + $warnPoints;
	        $sanctionTime = (int) $sanctionTime;
	        if($sanctionType === 1){
	        	$this->getPlugin()->mutes[$warned->getName()] = time() + ($sanctionTime * 60);
	        }elseif($sanctionType === 2){
	        	$this->getPlugin()->freezes[$warned->getName()] = time() + ($sanctionTime * 60);
	        }
	        $this->getPlugin()->registerWarn($warned, $warnReason, $sender->getName());
	        $msg = LangManager::translate("warn-warn", $sender, $warned->getName(), $rules[$warnReason]) . " ";
	        $msg .= LangManager::translate($this->getPlugin()->isBanned($warned) ? "warn-banned" : "warn-warned", $sender, $this->getPlugin()->getConfig()->get("warns-before-ban"));
	        $sender->sendMessage($msg);
	    });
	    $form->setTitle(LangManager::translate("warn-title", $sender));
	    $form->addLabel(LangManager::translate("warn-desc", $sender));
	    if(empty($list)){
	        $sender->sendMessage("noplayers");
	        return true;
	    }
	    $form->addDropdown(LangManager::translate("player", $sender), $list, null, "player");
	    $form->addDropdown(LangManager::translate("warn-warnreason", $sender), $this->getPlugin()->getConfig()->getNested("rules.list"), null, "warnReason");
	    $form->addDropdown(LangManager::translate("warn-sanction", $sender), [
	        "None", "Mute", "Freeze"
	    ], null, "sanctionType");
	    $form->addSlider(LangManager::translate("warn-sanctiontime", $sender), 10, 120, -1, -1, "sanctionTime");
	    $sender->sendForm($form);
	    return true;
	}
	
}