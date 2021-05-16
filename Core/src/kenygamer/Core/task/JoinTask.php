<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\ClosureTask;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\task\ReferralUITask;
use LegacyCore\Tasks\GuardianTask;
use jojoe77777\FormAPI\SimpleForm;

class JoinTask extends Task{
	/** @var Player */
	private $player;
	
	/** @var bool */
	private $firstJoin;
	/** @var int */
	private $introductionProgress = 0;
	
	/**
	 * @param Player $player
	 */
	public function __construct(Player $player, bool $firstJoin = false){
		$this->player = $player;
		
		$this->firstJoin = $firstJoin;
		if($this->firstJoin){
			$this->player->setImmobile();
		}
	}
	
	private function cancelTask() : void{
		Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
	}
	
	private function getLevel(string $level) : ?Level{
		return Main::getInstance()->getServer()->getLevelByName($level);
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		if(!$this->player->isOnline()){
			$this->cancelTask();
			return;
		}
		if($this->firstJoin){
			switch($this->introductionProgress++){
				case 0:
				    Main::getInstance()->fileReadMode[] = $this->player->getName();
				    $this->player->teleport(new Location(-45057, 58, -42508, 360, 0, $this->getLevel("hub")));
				    $this->player->addTitle(LangManager::translate("season-name", $this->player), LangManager::translate("introduction-1", $this->player));
			        break;
			    case 1:
			        $this->player->teleport(new Location(45056, 39, -42512, 180, 0, $this->getLevel("hub")));
			        $parts = explode("\n", LangManager::translate("introduction-2", $this->player));
			        $this->player->addTitle($parts[0], $parts[1] ?? "");
			        break;
			    case 2:
			        $this->player->teleport(new Location(45056, 40, -42576, 180, 0, $this->getLevel("hub")));
			        $parts = explode("\n", LangManager::translate("introduction-3", $this->player));
			        $this->player->addTitle($parts[0], $parts[1] ?? "");
			        break;
			    case 3:
			        $this->player->chat("/warp mine");
			        $parts = explode("\n", LangManager::translate("introduction-4", $this->player));
			        $this->player->addTitle($parts[0], $parts[1] ?? "");
			        break;
			    case 4:
			        $this->player->chat("/warp warzone");
			        $parts = explode("\n", LangManager::translate("introduction-5", $this->player));
			        $this->player->addTitle($parts[0], $parts[1] ?? "");
			        break;
			    case 5:
			        unset(Main::getInstance()->fileReadMode[array_search($this->player->getName(), Main::getInstance()->fileReadMode)]);
			        $parts = explode("\n", LangManager::translate("introduction-6", $this->player));
			        $this->player->addTitle($parts[0], $parts[1] ?? "");
			        $this->player->teleport($this->player->getServer()->getDefaultLevel()->getSpawnLocation());
			        $this->player->setImmobile(false);
			        $this->cancelTask();
			        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReferralUITask($this->player), 65);
			        break;
			}
			return;
		}
		self::sendChangelog($this->player, true);
	}
	
	/**
	 * @param Player $player
	 * @param bool $welcome
	 */
	public static function sendChangelog(Player $player, bool $welcome) : void{
		$pk = new LevelEventPacket();
		$pk->evid = LevelEventPacket::EVENT_SOUND_ANVIL_FALL;
		$pk->data = 0;
		$pk->position = $player->asVector3();
		$player->dataPacket($pk);
		Main::getInstance()->closeWindow($player, ContainerIds::INVENTORY);
		$form = new SimpleForm(function(Player $player, ?int $data) use($welcome){
			if($player->isOnline()){
				if($data !== 0 && $welcome){
					self::sendChangelog($player, true);
					return;
				}
				if($welcome){
					$msg[] = LangManager::translate("last-login", $player, Main::getInstance()->formatTime(Main::getInstance()->getTimeEllapsed(intval(floor($player->getLastPlayed() / 1000)))));
					$msg[] = LangManager::translate("welcome-message", $player);
					$player->sendMessage(TextFormat::colorize(implode("\n", $msg)));
					$player->addTitle(LangManager::translate("season-name", $player));
					$core = $player->getServer()->getPluginManager()->getPlugin("LegacyCore");
					$core->getScheduler()->scheduleTask(new GuardianTask($core, $player));
					
					$surveys = Main::getInstance()->getSurveyManager()->getSurveys();
					foreach($surveys as $survey){
						if($survey->canVote($player)){
							Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($player, $survey) : void{
								$player->chat("/survey vote " . $survey->getName());
							}), 20);
						}
					}
				}
        	}
        	    
		});
		$form->setTitle(LangManager::translate("season-name", $player));
		$changelog = LangManager::translate("changelog", $player) . "\n\n";
		$cchangelog = Main::getInstance()->changelog;
		
		foreach(["additions", "patchNotes"] as $section){
			if(isset($cchangelog[$section]) && !empty($notes = $cchangelog[$section])){
				$changelog .= LangManager::translate("changelog-" . $section, $player);
				foreach($notes as $note){
					$changelog .= "\n" . LangManager::translate("changelog-note", $player, $note);
				}
			}
			$changelog .= "\n\n";
		}
		$form->setContent($changelog);
		$form->addButton($player->hasPlayedBefore() ? LangManager::translate("continue", $player) : LangManager::translate("newprofile", $player));
		$form->sendToPlayer($player);
	}
	
}