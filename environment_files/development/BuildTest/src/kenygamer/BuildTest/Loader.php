<?php

declare(strict_types=1);

namespace kenygamer\BuildTest;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\BuildTest\command\BuildTestCommand;
use kenygamer\BuildTest\task\BuildTestCheckTask;

class Loader extends PluginBase{
    public const MSG_PREFIX = TextFormat::GREEN . "[BT] " . TextFormat::RESET;
    
    public const PLAYER_ROLE_SPECTATOR = "spectator";
    public const PLAYER_ROLE_APPLICANT = "applicant";
    public const PLAYER_ROLE_ADMIN = "admin";
    
    public const VALIDATE_DISCORD_NOT_VALID = -1;
    public const VALIDATE_DISCORD_NOT_UNIQUE = 0;
    public const VALIDATE_DISCORD_VALID = 1;
    
    /** @var Loader */
    private static $instance;
    
    /** @var BuildTest[] */
    private $buildTests;
    
    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->loadBuildTests();
        self::$instance = $this;
        new EventListener($this);
        $this->getScheduler()->scheduleRepeatingTask(new BuildTestCheckTask($this), 20 * $this->getConfig()->get("update-interval"));
        $this->getServer()->getCommandMap()->getCommand("buildtest")->setExecutor(new BuildTestCommand($this));
        foreach($this->getConfig()->get("admin-list") as $admin){
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "op \"" . $admin . "\"");
        }
        if($this->getConfig()->get("always-day")){
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "time set day");
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "time stop");
        }
    }
    
    public function onDisable() : void{
        $this->loadBuildTests(true);
    }
    
    /**
     * @param null|string $player If set, it will return a specific player build tests.
     * @return BuildTest[]
     */
    public function getBuildTests($player = null) : array{
        if($player !== null){
            $buildTests = [];
            foreach($this->buildTests as $buildTest){
                if($buildTest->getPlayer() === $player){
                    $buildTests[] = $buildTest;
                }
            }
            return $buildTests;
        }
        return $this->buildTests;
    }
    
    /**
     * Check if the player is fit to run the build test
     * @param string $player
     * @return bool
     */
    public function canRunBuildTest(string $player) : bool{
        $buildTests = $this->getBuildTests($player);
        if(count($buildTests) === 0){
            return true;
        }
        $buildTest = end($buildTests);
        if(in_array($buildTest->getStatus(), [BuildTest::STATUS_NOT_STARTED, BuildTest::STATUS_BEING_RUN, BuildTest::STATUS_FINISHED])){
            return false;
        }
        return true;
    }
    
    /**
     * Load/save build tests from/to disk
     * @param bool $save
     */
    public function loadBuildTests(bool $save = false) : void{
        $buildTests = new Config($this->getConfig()->get("data-path") . "build-tests.js", Config::JSON);
        if($save){
            $buildTest = [];
            foreach($this->buildTests as $bt){
                $buildTest[] = ["player" => $bt->getPlayer(), "timeframe" => $bt->getTimeFrame(), "build" => $bt->getBuild(), "playerdiscord" => $bt->getPlayerDiscord(), "start" => $bt->getStart(), "plotid" => $bt->getPlotId(), "creation" => $bt->getCreation(), "status" => $bt->getStatus()];
            }
            $buildTests->setAll($buildTest);
            $buildTests->save();
        }else{
            $this->buildTests = [];
            foreach($buildTests->getAll() as $buildTest){
                $this->buildTests[] = new BuildTest($buildTest["player"], $buildTest["timeframe"], $buildTest["build"], $buildTest["playerdiscord"], $buildTest["start"], $buildTest["plotid"], $buildTest["creation"], $buildTest["status"]);
            }
        }
    }
    
    /**
     * Create a build test.
     * @see canRunBuildTest() before calling this
     * @param string $player
     * @param int $timeframe
     * @param array $build if empty the player can pick a build
     */
    public function createBuildTest(string $player, int $timeframe, array $build = []) : void{
        if(empty($build)){
            $build = ["id" => "pickable", "title" => "", "instructions" => "", "image" => ""];
        }
        $this->buildTests[] = new BuildTest($player, $timeframe, $build);
    }
    
    /**
     * @param string $player
     * @return string Player role
     */
    public function getRole(string $player) : string{
        if(in_array($player, $this->getConfig()->get("admin-list"))){
            return self::PLAYER_ROLE_ADMIN;
        }
        if($this->canRunBuildTest($player)){
            return self::PLAYER_ROLE_SPECTATOR;
        }
        return self::PLAYER_ROLE_APPLICANT;
    }
    
    /**
     * Returns the plugin instance.
     *
     * @return Loader
     */
    public static function getInstance() : Loader{
        return self::$instance;
    }
    
    /**
     * @param int $timeLeft
     * @param string $color1
     * @param string $color2
     * @return string Countdown message
     */
    public static function getFormattedCountdown(int $timeLeft, string $color1 = TextFormat::RESET, string $color2 = TextFormat::RESET) : string{
        $hours = gmdate("H", $timeLeft);
        $minutes = gmdate("i", $timeLeft);
        $seconds = gmdate("s", $timeLeft);
        return $color1 . "Time left: " . $color2 . $hours . $color1 . " hours, " . $color2 . $minutes . $color1 . " minutes and " . $color2 . $seconds . $color1 . " seconds";
    }
    
    /**
     * @param int $start
     * @param string $color1
     * @param string $color2
     * @return string Start message
     */
    public static function getFormattedStart(int $start, string $color1 = TextFormat::RESET, string $color2 = TextFormat::RESET) : string{
        $start = time() - $start;
        if($start < 15){
            $started = "A moment ago";
        }elseif($start >= 15 && $start < 60){
            $started = $color2 . $start . $color1 . " second" . ($start > 1 ? "s" : "") . " ago";
        }elseif($start >= 60 && $start < 3600){
            $minutes = floor(($start / 60) % 60);
            $started = $color2 . $minutes . $color1 . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        }elseif($start >= 3600 && $start < 86400){
            $hours = floor($start / 3600);
            $started = $color2 . $hours . $color1 . " hour" . ($hours > 1 ? "s" : "") . " ago";
        }elseif($start >= 86400 && $start < 2592000){
            $days = floor($start / 86400);
            $started = $color2 . $days . $color1 . " day" . ($days > 1 ? "s" : "") . " ago";
        }elseif($start >= 2592000 && $start < 31104000){
            $months = floor($start / 2592000);
            $started = $color2 . $months . $color1 . " month" . ($months > 1 ? "s" : "") . " ago";
        }else{
            $years = floor($start / 31104000);
            $started = $color2 . $years . $color1 . " year" . ($years > 1 ? "s" : "") . " ago";
        }
        return $started;
    }
    
    /**
     * Checks the validity of a full Discord username
     * @param string $player
     * @param string $discord
     * @return int
     */
    public static function isValidDiscord(string $player, string $discord) : int{
        if(!preg_match("/^[^#]{2,32}#\d{4}$/", $discord)){
            return self::VALIDATE_DISCORD_NOT_VALID;
        }
        foreach(self::getInstance()->getBuildTests() as $buildTest){
            if($buildTest->getPlayerDiscord() === $discord && $buildTest->getPlayer() !== $player){
                return self::VALIDATE_DISCORD_NOT_UNIQUE;
            }
        }
        return self::VALIDATE_DISCORD_VALID;
    }
    
}