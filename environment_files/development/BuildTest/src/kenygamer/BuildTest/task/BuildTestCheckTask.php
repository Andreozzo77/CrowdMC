<?php

declare(strict_types=1);

namespace kenygamer\BuildTest\task;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use kenygamer\BuildTest\Loader;
use kenygamer\BuildTest\BuildTest;

class BuildTestCheckTask extends Task{
    /** @var Loader */
    private $plugin;
    
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        foreach($this->plugin->getBuildTests() as $buildTest){
            $player = $this->plugin->getServer()->getPlayer($buildTest->getPlayer());
            switch($buildTest->getStatus()){
                case BuildTest::STATUS_NOT_STARTED:
                    $hours = $this->plugin->getConfig()->get("max-start");
                    if($hours !== 0){
                        if(time() - $buildTest->getCreation() >= 3600 * $hours){
                            $buildTest->updateStatus(BuildTest::STATUS_START_EXPIRED);
                            $this->plugin->loadBuildTests(true);
                            if($player instanceof Player){
                                $canTryAgain = count($this->plugin->getBuildTests($player->getName())) < $this->plugin->getConfig()->get("build-test-attempts");
                                $player->kick(Loader::MSG_PREFIX . TextFormat::RED . "You tried to start the build test too late." . ($canTryAgain ? " You can rerun the build test." : " You have no chances left over."));
                            }
                        }
                    }
                    break;
                case BuildTest::STATUS_BEING_RUN:
                    if($buildTest->getTimeLeft() <= 0){
                        $buildTest->updateStatus(BuildTest::STATUS_FINISHED);
                        $this->plugin->loadBuildTests(true);
                        if($player instanceof Player){
                            $player->kick(Loader::MSG_PREFIX . TextFormat::AQUA . "You have finished your build test.\nLet an admin know to review it.");
                        }
                        $this->plugin->getServer()->broadcastMessage(Loader::MSG_PREFIX . TextFormat::AQUA . $buildTest->getPlayer() . TextFormat::GREEN . " completed the build test. Build: " . TextFormat::AQUA . $buildTest->getBuild()["id"]);
                        break;
                    }
                    if($player instanceof Player){
                        $player->sendMessage(Loader::MSG_PREFIX . Loader::getFormattedCountdown($buildTest->getTimeLeft(), TextFormat::GREEN, TextFormat::AQUA) . TextFormat::GREEN . "\nBuild: " . TextFormat::AQUA . $buildTest->getBuild()["id"]);
                    }
                    break;
                case BuildTest::STATUS_START_EXPIRED:
                    //Unused
                    break;
            }
        }
    }
    
}
