<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;

class ReferralUITask extends Task{
	/** @var Player */
	private $player;
	
	/**
	 * @param Player $player
	 */
	public function __construct(Player $player){
		$this->player = $player;
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		if($this->player->isOnline()){
			
			$form = new ModalForm(function(Player $player, $data) use($currentTick){
				if($player->isOnline()){
					if($data){
						$form = new CustomForm(function(Player $player, $data) use($currentTick){
							$referrer = !isset($data[1]) || !is_string($data[1]) ? "" : $data[1];
							if(mb_strtolower($referrer) === $player->getLowerCaseName()){
								LangManager::send("username-other", $player);
								$this->onRun($currentTick);
							}elseif($referrer !== "" && !$player->getServer()->getOfflinePlayer($referrer)->hasPlayedBefore()){
								LangManager::send("player-notfound", $player);
								$this->onRun($currentTick);
							}else{
								LangManager::send("referral-valid", $player);
								JoinTask::sendChangelog($player, true);
								MiscListener::$referred_playing[$player->getLowerCaseName()] = [mb_strtolower($referrer), 0];
							}
						});
						$form->setTitle(LangManager::translate("referral-title", $player));
						$form->addLabel(LangManager::translate("referral-content", $player));
						$form->addInput(LangManager::translate("referral-referredby", $player));
						$player->sendForm($form);
					}elseif($data === false){
						JoinTask::sendChangelog($player, true);
					}elseif($data === null){ //opened up chat, inventory or pause before we could send form
					    $this->onRun($currentTick);
					}
				}
			});
			$form->setTitle(LangManager::translate("referral-title", $this->player));
			$form->setContent(LangManager::translate("referral-invite", $this->player));
			$form->setButton1(LangManager::translate("yes", $this->player));
			$form->setButton2(LangManager::translate("no", $this->player));
			$this->player->sendForm($form);
		}
	}
}