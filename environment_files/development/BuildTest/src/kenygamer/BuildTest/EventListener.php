<?php

declare(strict_types=1);

namespace kenygamer\BuildTest;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\FormAPI;
use jojoe77777\FormAPI\SimpleForm;

class EventListener implements Listener{
    /** @var Loader */
    private $plugin;
    /** @var FormAPI|null */
    private $formApi;
    
    /** @var array */
    private $session;
    
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
        $this->plugin = $plugin;
    }
    
    /**
     * Returns the FormAPI plugin instance, or null if not enabled.
     * @return FormAPI|null
     */
    private function getFormApi() : ?FormAPI{
        if(!$this->formApi instanceof FormAPI){
            $this->formApi = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        }
        return $this->formApi;
    }
    
    /**
     * Join window
     * @param Player $player
     */
    private function sendJoinWindow(Player $player) : void{
        sleep(2); // :facepalm: use delayed task
        $text = TextFormat::DARK_BLUE . "------------------------------" . TextFormat::EOL;
        $text .= TextFormat::GOLD . "Welcome to EliteStar Build Tests!" . TextFormat::EOL;
        $text .= TextFormat::DARK_BLUE . "------------------------------" . TextFormat::EOL . TextFormat::EOL;
        $role = $this->plugin->getRole($player->getName());
        switch($role){
            case Loader::PLAYER_ROLE_ADMIN:
                $notStarted = [];
                $review = 0;
                foreach($this->plugin->getBuildTests() as $buildTest){
                    if($buildTest->getStatus() === BuildTest::STATUS_NOT_STARTED){
                        $notStarted[] = $buildTest->getPlayer();
                    }elseif($buildTest->getStatus() === BuildTest::STATUS_FINISHED){
                        ++$review;
                    }
                }
                if(count($notStarted) === 0){
                    $text .= TextFormat::AQUA . "- There are no build tests that haven't been started.";
                }else{
                	if(count($notStarted) > 1){
                		$text .= TextFormat::AQUA . "- There are " . count($notStarted) . " build tests that were not started yet (" . TextFormat::GREEN . implode(TextFormat::AQUA . ", " . TextFormat::GREEN, $notStarted) . TextFormat::AQUA . ")";
                	}else{
                		$text .= TextFormat::AQUA . "- There is 1 build test that was not started yet (" . TextFormat::GREEN . implode(TextFormat::AQUA . ", " . TextFormat::GREEN, $notStarted) . TextFormat::AQUA . ")";
                	}
                }
                $text .= TextFormat::EOL;
                if($review === 0){
                    $text .= TextFormat::AQUA . "- There are no build tests pending for reviewal.";
                }else{
                    $text .= TextFormat::AQUA . "- There are " . $review . " build tests pending for reviewal.";
                }
                $text .= TextFormat::EOL . TextFormat::EOL;
                $text .= TextFormat::EOL . TextFormat::GRAY . "To manage build tests, use " . TextFormat::EOL;
                $text .= TextFormat::YELLOW . $this->plugin->getDescription()->getCommands()["buildtest"]["usage"];
                break;
            case Loader::PLAYER_ROLE_SPECTATOR:
                $text .= TextFormat::AQUA . "You are a spectator." . TextFormat::EOL . TextFormat::EOL;
                $text .= TextFormat::GRAY . "To apply for Builder, follow the link: " . TextFormat::YELLOW . "https://mcpe.life/apply4builder";
                break;
            case Loader::PLAYER_ROLE_APPLICANT:
                $buildTests = $this->plugin->getBuildTests($player->getName());
                $buildTest = end($buildTests);
                if($buildTest->getStatus() === BuildTest::STATUS_BEING_RUN){
                    $text .= TextFormat::AQUA . "You are running a build test (" . TextFormat::WHITE . (count($buildTests) - 1) . TextFormat::AQUA . " out of " . TextFormat::WHITE . $this->plugin->getConfig()->get("build-test-attempts") . TextFormat::AQUA . " attempts)" . TextFormat::EOL . TextFormat::EOL;
                    $text .= Loader::getFormattedCountdown($buildTest->getTimeLeft(), TextFormat::AQUA, TextFormat::WHITE) . TextFormat::EOL;
                    $text .= TextFormat::AQUA . "Your plot #: " . TextFormat::WHITE . ($buildTest->getPlotId() ?? "None, type '/plot claim' to claim a plot.") . TextFormat::EOL;
                    $text .= TextFormat::AQUA . "Build: " . TextFormat::WHITE . $buildTest->getBuild()["id"] . TextFormat::EOL;
                    $text .= TextFormat::AQUA . "Instructions: " . TextFormat::WHITE . str_replace(TextFormat::EOL, " ", $buildTest->getBuild()["instructions"]) . TextFormat::EOL . TextFormat::EOL;
                    $text .= TextFormat::GRAY . "The timer cannot be paused once you start a build test, if you have any questions contact an admin."; 
                    break;
                }
        }
        $form = $this->getFormApi()->createCustomForm(function(Player $player, $data){});
        $form->setTitle(TextFormat::BOLD . TextFormat::GREEN . "Welcome");
        $form->addLabel($text);
        $form->sendToPlayer($player);
    }
    
    /**
     * Sends the discord verification window to the player.
     *
     * @param Player $player
     */
    private function sendDiscordVerifyWindow(Player $player) : void{
        $form = $this->getFormApi()->createCustomForm(function(Player $player, $data){
            $discord = isset($data["discord"]) && $data["discord"] !== null ? trim($data["discord"]) : "";
            switch(Loader::isValidDiscord($player->getName(), $discord)){
                case Loader::VALIDATE_DISCORD_NOT_VALID:
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . "Enter the full Discord username. Example: example#1234";
                    break;
                case Loader::VALIDATE_DISCORD_NOT_UNIQUE:
                    $msg = Loader::MSG_PREFIX . TextFormat::RED . "The Discord username you entered is already in use.";
                    break;
                case Loader::VALIDATE_DISCORD_VALID:
                    $buildTests = $this->plugin->getBuildTests($player->getName());
                    $buildTest = end($buildTests);
                    $buildTest->setPlayerDiscord($player->getName(), $discord);
                    $this->plugin->loadBuildTests(true);
                    if($buildTest->getStatus() === BuildTest::STATUS_NOT_STARTED && $buildTest->getBuild()["id"] !== "pickable"){
                        $this->relog[$player->getName()] = true;
                        $buildTest->updateStatus(BuildTest::STATUS_BEING_RUN);
                        $this->plugin->loadBuildTests(true);
                    }
                    $msg = Loader::MSG_PREFIX . TextFormat::GREEN . "Discord username set to: " . TextFormat::AQUA . $discord;
                    break;
            }
            $player->sendMessage($msg);
            return;
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::GREEN . "Build Test");
        $form->addLabel("We have reviewed your Builder application and decided to give you a try.");
        $form->addLabel("To get started, enter your full Discord username:");
        $form->addInput("", "example#1234", null, "discord");
        $form->sendToPlayer($player);
        return;
    }
    
    /**
     * Sends the build test build select window to the player.
     *
     * @param Player $player
     */
    private function sendBuildTestBuildSelectWindow(Player $player) : void{
        $form = $this->getFormApi()->createSimpleForm(function(Player $player, $data){
            if($data === null){
                $player->sendMessage(Loader::MSG_PREFIX . TextFormat::RED . "Select a build.");
                return;
            }
            $build = BuildTest::getBuildTestBuildById($data);
            $infoForm = $this->getFormApi()->createModalForm(function(Player $player, $data){
                if($data === false){
                    $this->sendBuildTestBuildSelectWindow($player);
                    return;
                }
                if($data === true){
                    $buildTests = $this->plugin->getBuildTests($player->getName());
                    $buildTest = end($buildTests);
                    $buildTest->updateBuild($this->session[$player->getName()]["build"]);
                    $buildTest->updateStatus(BuildTest::STATUS_BEING_RUN);
                    $this->plugin->loadBuildTests(true);
                    $this->relog[$player->getName()] = true;
                    $player->sendMessage(Loader::MSG_PREFIX . TextFormat::GREEN . "Great choice! You are building: " . TextFormat::AQUA . $this->session[$player->getName()]["build"]["id"]);
                    return;
                }
            });
            $infoForm->setTitle(TextFormat::BOLD . TextFormat::GREEN . mb_strtoupper($build["title"]) . " instructions");
            $infoForm->setContent($build["instructions"]);
            $infoForm->setButton1("Pick this build");
            $infoForm->setButton2("Back");
            $infoForm->sendToPlayer($player);
            $this->session[$player->getName()]["build"] = $build;
            return;
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::GREEN . "Build Test");
        $form->setContent("Excellent! Please now select a build:");
        foreach(BuildTest::getBuildTestBuilds() as $buildTestBuild){
            $form->addButton($buildTestBuild["title"], SimpleForm::IMAGE_TYPE_URL, $buildTestBuild["image"], $buildTestBuild["id"]);
        }
        $form->sendToPlayer($player);
        return;
    }
    
    /**
     * Event Listener.
     *
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $role = $this->plugin->getRole($player->getName());
        if($role === Loader::PLAYER_ROLE_ADMIN){
            $event->setCancelled();
            $player->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "You may not do that.");
        }
    }
    
    /**
     * Event Listener.
     *
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event) : void{
        $player = $event->getPlayer();
        $role = $this->plugin->getRole($player->getName());
        if($role === Loader::PLAYER_ROLE_ADMIN){
            $event->setCancelled();
            $player->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "You may not do that.");
        }
    }
    
    /**
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event) : void{
        $player = $event->getPlayer();
        if($this->plugin->getConfig()->get("anti-spam")){
            $seconds = $this->plugin->getConfig()->get("anti-spam-seconds");
            if(isset($this->session[$player->getName()]["lastChat"]) && time() - $this->session[$player->getName()]["lastChat"] < $seconds){
                $event->setCancelled();
                $player->sendMessage(Loader::MSG_PREFIX . TextFormat::RED . "Woah, slow down! Spam is not allowed.");
            }
            $this->session[$player->getName()]["lastChat"] = time();
        }
        $role = $this->plugin->getRole($player->getName());
        switch($role){
            case Loader::PLAYER_ROLE_ADMIN:
                $format = TextFormat::AQUA . TextFormat::BOLD . "ADMIN " . TextFormat::RESET . TextFormat::WHITE . "{PLAYER}:" . TextFormat::GRAY . " {MSG}";
                break;
            case Loader::PLAYER_ROLE_SPECTATOR:
                $format = TextFormat::GRAY . TextFormat::BOLD . "SPECTATOR " . TextFormat::RESET . TextFormat::WHITE . "{PLAYER}:" . TextFormat::GRAY . " {MSG}";
                break;
            case Loader::PLAYER_ROLE_APPLICANT:
                $format = TextFormat::DARK_RED . TextFormat::BOLD . "APPLICANT " . TextFormat::RESET . TextFormat::WHITE . "{PLAYER}:" . TextFormat::GRAY . " {MSG}";
                break;
        }
        $msg = $role !== Loader::PLAYER_ROLE_ADMIN ? TextFormat::clean($event->getMessage()) : $event->getMessage();
        $format = str_replace(["{PLAYER}", "{MSG}"], [$player->getName(), $msg], $format);
        $event->setFormat($format);
    }
    
    /**
     * Event listener.
     *
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        if($this->plugin->getRole($player->getName()) === Loader::PLAYER_ROLE_APPLICANT){
            $buildTests = $this->plugin->getBuildTests($player->getName());
            $buildTest = end($buildTests);
            if($buildTest->getStatus() === BuildTest::STATUS_NOT_STARTED){
                if($buildTest->getPlayerDiscord() === null){
                    $event->setCancelled();
                    if(isset($this->session[$player->getName()]["lastWindow"]) && time() - $this->session[$player->getName()]["lastWindow"] < 15){
                        return;
                    }
                    $this->session[$player->getName()]["lastWindow"] = time();
                    $player->setGamemode(Player::SURVIVAL);
                    $this->sendDiscordVerifyWindow($player);
                    return;
                }
                if($buildTest->getBuild()["id"] === "pickable"){
                    $event->setCancelled();
                    if(isset($this->session[$player->getName()]["lastWindow"]) && time() - $this->session[$player->getName()]["lastWindow"] < 15){
                        return;
                    }
                    $this->session[$player->getName()]["lastWindow"] = time();
                    $player->setGamemode(Player::SURVIVAL);
                    $this->sendBuildTestBuildSelectWindow($player);
                    return;
                }
            }else{
                $player->setGamemode(Player::CREATIVE);
            }
            if(isset($this->relog[$player->getName()])){
                $event->setCancelled();
                if(isset($this->session[$player->getName()]["lastRelogMessage"]) && time() - $this->session[$player->getName()]["lastRelogMessage"] < 5){
                    return;
                }
                $this->session[$player->getName()]["lastRelogMessage"] = time();
                $player->sendMessage(Loader::MSG_PREFIX . TextFormat::AQUA . "Please relog.");
            }
        }
    }
    
    /**
     * Event listener.
     *
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $this->plugin->getServer()->dispatchCommand($player, "xyz");
        if(!isset($this->session[$player->getName()])){
            $this->session[$player->getName()] = [];
        }
        $location = $player->getLevel()->getSafeSpawn(new Vector3($player->getFloorX(), $player->getFloorY(), $player->getFloorZ()));
        $player->teleport(new Vector3($location->getFloorX(), $location->getFloorY() + 1, $location->getFloorZ()));
        $role = $this->plugin->getRole($player->getName());
        switch($role){
            case Loader::PLAYER_ROLE_ADMIN:
                $event->setJoinMessage(Loader::MSG_PREFIX . TextFormat::AQUA . "[+] " . $player->getName() . TextFormat::BOLD . TextFormat::AQUA . " ADMIN");
                $player->setGamemode(Player::CREATIVE);
                $this->sendJoinWindow($player);
                break;
            case Loader::PLAYER_ROLE_SPECTATOR:
                $event->setJoinMessage(Loader::MSG_PREFIX . TextFormat::AQUA . "[+] " . $player->getName() . TextFormat::BOLD . TextFormat::GRAY . " SPECTATOR");
                $player->setGamemode(Player::SPECTATOR);
                $this->sendJoinWindow($player);
                break;
            case Loader::PLAYER_ROLE_APPLICANT:
                $event->setJoinMessage(Loader::MSG_PREFIX . TextFormat::AQUA . "[+] " . $player->getName() . TextFormat::BOLD . TextFormat::DARK_RED . " APPLICANT");
                $buildTests = $this->plugin->getBuildTests($player->getName());
                $buildTest = end($buildTests);
                if(isset($this->relog[$player->getName()])){
                    unset($this->relog[$player->getName()]);
                    $this->plugin->getServer()->broadcastMessage(Loader::MSG_PREFIX . TextFormat::AQUA . $player->getName() . TextFormat::GREEN . " started a build test. Build: " . TextFormat::AQUA . $buildTest->getBuild()["id"]);
                    $player->sendMessage(Loader::MSG_PREFIX . TextFormat::GREEN . "Timer started! You have " . TextFormat::AQUA . $buildTest->getTimeFrame() . "h" . TextFormat::GREEN . " to finish the test. The timer cannot be paused, even if you log off.");
                }
                if($buildTest->getPlayerDiscord() !== null && $buildTest->getBuild()["id"] !== "pickable"){
                    $this->sendJoinWindow($player);
                }
                break;
        }
    }
    
    /**
     * Event listener.
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        $role = $this->plugin->getRole($player->getName());
        $event->setQuitMessage(Loader::MSG_PREFIX . TextFormat::RED . "[-] " . $player->getName());
    }
    
    /**
     * @param PlayerPreLoginEvent $event
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void{
        $player = $event->getPlayer();
        $buildTests = $this->plugin->getBuildTests($player->getName());
        if($this->plugin->getRole($player->getName()) === Loader::PLAYER_ROLE_APPLICANT || count($buildTests) >= 1){
            $buildTest = end($buildTests);
            switch($buildTest->getStatus()){
                case BuildTest::STATUS_FINISHED:
                    $event->setCancelled();
                    $event->setKickMessage(Loader::MSG_PREFIX . TextFormat::AQUA . "You have finished your build test.\nLet an admin know to review it.");
                    break;
                case BuildTest::STATUS_APPROVED:
                    $event->setCancelled();
                    $event->setKickMessage(Loader::MSG_PREFIX . TextFormat::GREEN . "Your build test was approved.");
                    break;
                case BuildTest::STATUS_REJECTED:
                case BuildTest::STATUS_START_EXPIRED:
                    $canTryAgain = count($buildTests) < $this->plugin->getConfig()->get("build-test-attempts");
                    $event->setCancelled();
                    $event->setKickMessage(Loader::MSG_PREFIX . TextFormat::RED . "Your build test was rejected." . ($canTryAgain ? " You can rerun the build test." : "You have no chances left over."));
                    break;
            }
        }
    }
    
    /**
     * Event listener.
     *
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
        $player = $event->getPlayer();
        if($this->plugin->getRole($player->getName()) === Loader::PLAYER_ROLE_SPECTATOR){
            $command = explode(" ", $event->getMessage())[0];
            if(in_array($command, ["/p", "/plot"])){
                $event->setCancelled();
                $player->sendMessage(Loader::MSG_PREFIX . TextFormat::RED . "You can't use this command!");
            }
        }
    }
    
}
