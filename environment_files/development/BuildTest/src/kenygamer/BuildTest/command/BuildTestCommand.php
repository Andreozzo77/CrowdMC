<?php

declare(strict_types=1);

namespace kenygamer\BuildTest\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use kenygamer\BuildTest\Loader;
use kenygamer\BuildTest\BuildTest;

class BuildTestCommand implements CommandExecutor{
    /** @var Loader */
    private $plugin;
    
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }
    
    /**
     * Command executor for the buildtest command.
     *
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
        if(!isset($args[0])){
            return false;
        }
        $name = $sender->getName();
        $action = $args[0] ?? null;
        switch($action){
            case "create":
                if(!isset($args[1])){
                    $msg = "Usage: /buildtest or /bt create <player> [build] [timeFrame]" . TextFormat::EOL;
                    $msg .= TextFormat::AQUA . "Builds: " . TextFormat::GOLD . implode(TextFormat::AQUA . ", " . TextFormat::GOLD, BuildTest::getBuildTestBuilds(true));
                    break;
                }
                $player = $args[1];
                if($this->plugin->getRole($player) === Loader::PLAYER_ROLE_ADMIN){
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . "Admin cannot run build test.";
                    break;
                }
                if(!$this->plugin->canRunBuildTest($player)){
                    $buildTests = $this->plugin->getBuildTests($player);
                    $buildTest = end($buildTests);
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . $player . " is already running a build test. Build: " . $buildTest->getBuild()["id"];
                    break;
                }
                if(count($this->plugin->getBuildTests($player)) >= $this->plugin->getConfig()->get("build-test-attempts")){
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . $player . " reached the maximum of build test attempts.";
                    break;
                }
                $builds = BuildTest::getBuildTestBuilds(true);
                $build = isset($args[2]) ? mb_strtolower($args[2]) : "pickable";
                if(!in_array($build, $builds) && $build !== "pickable"){
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . "Invalid build." . TextFormat::EOL;
                    $msg .= TextFormat::AQUA . "Builds: " . TextFormat::GOLD . implode(TextFormat::AQUA . ", " . TextFormat::GOLD, BuildTest::getBuildTestBuilds(true)) . TextFormat::EOL;
                    break;
                }
                $defaultTimeFrame = $this->plugin->getConfig()->get("time-frame");
                $timeFrame = isset($args[3]) ? (int) $args[3] : (int) $defaultTimeFrame;
                if($timeFrame < $defaultTimeFrame){
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . "Please enter a time frame greater than " . $defaultTimeFrame . "h.";
                    break;
                }
                if($build === "pickable"){
                    $this->plugin->createBuildTest($player, $timeFrame, []);
                }else{
                    $this->plugin->createBuildTest($player, $timeFrame, BuildTest::getBuildTestBuildById($build));
                }
                $this->plugin->loadBuildTests(true);
                $msg = Loader::MSG_PREFIX . TextFormat::GREEN . "Started build test for " . $player . ". Build: " . TextFormat::AQUA . (isset($build["id"]) ? $build["id"] : $build);
                break;
            case "list":
                $buildTests = $this->plugin->getBuildTests();
                $review = [];
                foreach($buildTests as $buildTest){
                    if($buildTest->getStatus() === BuildTest::STATUS_FINISHED){
                        $review[] = $buildTest;
                    }
                }
                if(empty($review)){
                    $msg = Loader::MSG_PREFIX . TextFormat::AQUA . "There are no build tests requiring review.";
                    break;
                }
                $msg = Loader::MSG_PREFIX . TextFormat::AQUA . "Build tests requiring review:" . TextFormat::EOL;
                foreach($review as $buildTest){
                    if($buildTest->getPlotId() === -1){
                        $msg .= TextFormat::AQUA . "- " . TextFormat::WHITE . $buildTest->getPlayer() . TextFormat::GOLD . " with Discord " . TextFormat::WHITE . $buildTest->getPlayerDiscord() . TextFormat::GOLD . " started " . TextFormat::WHITE . Loader::getFormattedStart($buildTest->getStart()) . TextFormat::GOLD . " (did not claim any plot)";
                    }else{
                        $msg .= TextFormat::AQUA . "- " . TextFormat::WHITE . $buildTest->getPlayer() . TextFormat::GOLD . " with Discord " . TextFormat::WHITE . $buildTest->getPlayerDiscord() . TextFormat::GOLD . " started " . TextFormat::WHITE . Loader::getFormattedStart($buildTest->getStart()) . TextFormat::GOLD . " and built a / an " . TextFormat::WHITE . $buildTest->getBuild()["id"] . TextFormat::GOLD . " at plot " . TextFormat::WHITE . $buildTest->getPlotId();
                    }
                    if($buildTest !== end($review)){
                        $msg .= TextFormat::EOL;
                    }
                }
                break;
            case "instructions":
                if(!isset($args[1])){
                    $msg = "Usage: /buildtest or /bt instructions <build>";
                    break;
                }
                $build = BuildTest::getBuildTestBuildById($args[1]);
                if($build !== null){
                    $msg = Loader::MSG_PREFIX . TextFormat::AQUA . "Viewing instructions for build test build " . TextFormat::WHITE . $build["id"] . TextFormat::AQUA . ":" . TextFormat::EOL;
                    $msg .= TextFormat::GOLD . str_replace(TextFormat::EOL, "", $build["instructions"]);
                    break;
                }
                $msg = Loader::MSG_PREFIX .TextFormat::RED . "No build test build named " . $args[1] . " exists.";
                break;
            case "info":
                if(!isset($args[1])){
                    $msg = "Usage: /buildtest or /bt info <id: player or playerDiscord>";
                    break;
                }
                $id = $args[1];
                foreach($this->plugin->getBuildTests() as $buildTest){
                    if(mb_strtolower($buildTest->getPlayer()) === mb_strtolower($id)){
                        $bt = ["timeframe" => $buildTest->getTimeFrame(), "build" => $buildTest->getBuild()["id"], "playerdiscord" => $buildTest->getPlayerDiscord(), "plotid" => $buildTest->getPlotId(), "creation" => $buildTest->getCreation()];
                    }elseif(mb_strtolower((string) $buildTest->getPlayerDiscord()) === mb_strtolower($id)){
                        $bt = ["player" => $buildTest->getPlayer(), "timeframe" => $buildTest->getTimeFrame(), "build" => $buildTest->getBuild()["id"], "plotid" => $buildTest->getPlotId(), "creation" => $buildTest->getCreation()];
                    }else{
                        continue;
                    }
                    $buildTests = $this->plugin->getBuildTests($buildTest->getPlayer());
                    if($buildTest !== end($buildTests)){
                        continue;
                    }
                    $status = "STATUS_UNKNOWN";
                    foreach((new \ReflectionClass($buildTest))->getConstants() as $name => $value){
                        if($buildTest->getStatus() === $value){
                            $status = $name;
                        }
                    }
                    $msg = Loader::MSG_PREFIX . TextFormat::AQUA . "Viewing info of " . TextFormat::WHITE . $id . "'s " . TextFormat::AQUA . " build test:" . TextFormat::EOL;
                    $msg .= TextFormat::GOLD . json_encode($bt, JSON_PRETTY_PRINT) . TextFormat::EOL;
                    if($buildTest->getStart() === null){
                        $msg .= TextFormat::AQUA . "Not started." . TextFormat::EOL;
                    }else{
                        $msg .= TextFormat::AQUA . "Started At: " . Loader::getFormattedStart($buildTest->getStart(), TextFormat::AQUA, TextFormat::WHITE) . TextFormat::EOL;
                    }
                    $msg .= TextFormat::AQUA . "Status: " . TextFormat::WHITE . $status;
                    break 2;
                }
                $msg = Loader::MSG_PREFIX . TextFormat::RED . $id . " did not ever run a build test.";
                break;
            case "approve":
                if(!isset($args[1])){
                    $msg = "Usage: /buildtest or /bt approve <player>";
                    break;
                }
                $player = $args[1];
                if(!$this->plugin->canRunBuildTest($player)){
                    $buildTests = $this->plugin->getBuildTests($player);
                    $buildTest = end($buildTests);
                    if($buildTest->getStatus() === BuildTest::STATUS_BEING_RUN or $buildTest->getStatus() === BuildTest::STATUS_FINISHED){
                        $buildTest->updateStatus(BuildTest::STATUS_APPROVED);
                        $this->plugin->loadBuildTests(true);
                        $pl = $this->plugin->getServer()->getPlayer($player);
                        if($pl instanceof Player){
                            $pl->kick(Loader::MSG_PREFIX . TextFormat::GREEN . "Your build test was approved.");
                        }
                        $msg = Loader::MSG_PREFIX . TextFormat::AQUA . $player . "'s " . TextFormat::GREEN . "build test was approved.";
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                            if($player->getName() !== $name){
                                $player->sendMessage($msg);
                            }
                        }
                        break;
                    }
                }
                $msg = Loader::MSG_PREFIX . TextFormat::RED . $player . " does not have an active build test.";
                break;
            case "reject":
                if(!isset($args[1])){
                    $msg = "Usage: /buildtest or /bt reject <player>";
                    break;
                }
                $player = $args[1];
                if(!$this->plugin->canRunBuildTest($player)){
                    $buildTests = $this->plugin->getBuildTests($player);
                    $buildTest = end($buildTests);
                    if($buildTest->getStatus() === BuildTest::STATUS_BEING_RUN or $buildTest->getStatus() === BuildTest::STATUS_FINISHED){
                        $buildTest->updateStatus(BuildTest::STATUS_REJECTED);
                        $this->plugin->loadBuildTests(true);
                        $pl = $this->plugin->getServer()->getPlayer($player);
                        if($pl instanceof Player){
                            $canTryAgain = count($buildTests) < $this->plugin->getConfig()->get("build-test-attempts");
                            $pl->kick(Loader::MSG_PREFIX . TextFormat::RED . "Your build test was rejected." . ($canTryAgain ? " Please try again in a week." : ""));
                        }
                        $msg = Loader::MSG_PREFIX . TextFormat::AQUA . $player . "'s " . TextFormat::GREEN . "build test was rejected.";
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                            if($player->getName() !== $name){
                                $player->sendMessage($msg);
                            }
                        }
                        break;
                    }
                }
                $msg = Loader::MSG_PREFIX . TextFormat::RED . $player . " does not have a build test.";
                break;
            default:
                return false;
        }
        $sender->sendMessage($msg);
        return true;
    }
    
}
