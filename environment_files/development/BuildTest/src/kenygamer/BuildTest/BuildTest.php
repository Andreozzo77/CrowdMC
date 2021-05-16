<?php

declare(strict_types=1);

namespace kenygamer\BuildTest;

class BuildTest{
    /** Player has a build test assigned but was not started. */
    public const STATUS_NOT_STARTED = 0;
    /** Player has a build test assigned and is running it. */
    public const STATUS_BEING_RUN = 1;
    /** Player finished the build test and is requiring review. */
    public const STATUS_FINISHED = 2;
    /** Player finished the build test and it was approved. */
    public const STATUS_APPROVED = 3;
    /** Player finished the build test and it was rejected. */
    public const STATUS_REJECTED = 4;
    /** Player did not start the build test as well the max start time expired. */
    public const STATUS_START_EXPIRED = 5;
    
    /** @var string */
    private $player;
    /** @var int */
    private $timeFrame;
    /** @var array */
    private $build;
    
    /** @var string|null */
    private $playerDiscord;
    /** @var int|null */
    private $start;
    /** @var string|null */
    private $plotId;
    /** @var int */
    private $creation;
    /** @var int */
    private $status;
    
    /**
     * @param string $player
     * @param int $timeFrame
     * @param array $build
     * @param string|null $playerDiscord
     * @param int|null $start
     * @param string|null $plotId
     * @param int|null $creation
     * @param int $status
     */
    public function __construct(string $player, int $timeFrame, array $build, ?string $playerDiscord = null, ?int $start = null, ?string $plotId = null, ?int $creation = null, int $status = BuildTest::STATUS_NOT_STARTED){
        $this->player = $player;
        $this->timeFrame = $timeFrame;
        if(!isset($build["id"]) || !isset($build["title"]) || !isset($build["instructions"]) || !isset($build["image"])){
            throw new \InvalidArgumentException("Invalid build test data " . json_encode($build));
        }
        $this->build = $build;
        $this->playerDiscord = $playerDiscord;
        $this->start = $start;
        $this->plotId = $plotId;
        if(!isset($creation)){
            $this->creation = time();
        }else{
            $this->creation = $creation;
        }
        $this->updateStatus($status);
    }
    
    /**
     * Returns the name of the player.
     *
     * @return string
     */
    public function getPlayer() : string{
        return $this->player;
    }
    
    /**
     * Returns the discord of the player.
     * If null, it means player did not start the build test.
     *
     * @return string|null
     */
    public function getPlayerDiscord(){
        return $this->playerDiscord;
    }
    
    /**
     * Returns the start time of the build test.
     * If null, it means player did not start the build test.
     *
     * @return int|null
     */
    public function getStart(){
        return $this->start;
    }
    
    /**
     * Returns the plot id of the build test.
     * If null, it means player did not claim a plot for the build test.
     *
     * @return string|null
     */
    public function getPlotId() : ?string{
        return $this->plotId;
    }
    
    /**
     * Returns the time frame to finish a build test.
     *
     * @return int
     */
    public function getTimeFrame() : int{
        return $this->timeFrame;
    }
    
    /**
     * Returns the build test build info.
     *
     * @return array
     */
    public function getBuild() : array{
        return $this->build;
    }
    
    /**
     * Returns the creation time of the build test.
     * Used to automatically delete non started build tests.
     *
     * @return int
     */
    public function getCreation() : int{
        return $this->creation;
    }
    
    /**
     * Returns the status of the build test.
     *
     * @return int
     */
    public function getStatus() : int{
        return $this->status;
    }
    
    /**
     * Sets the discord of the player.
     *
     * @param string $player
     * @param string $discord
     */
    public function setPlayerDiscord(string $player, string $discord) : void{
        if(!Loader::isValidDiscord($player, $discord)){
            throw new \InvalidArgumentException("Discord {$discord} invalid or already in use");
        }
        $this->playerDiscord = $discord;
    }
    
    /**
     * Sets the start time of the build test.
     * This method is for internal use only. Prefer BuildTest::updateTimeFrame() instead
     *
     * @param int $time
     */
    public function setStart(int $time) : void{
        $this->start = $time;
    }
    
    /**
     * Sets the plot id of the build test.
     * Used by MyPlot (requires manual modification).
     *
     * @param string $plotId
     */
    public function setPlotId(string $plotId) : void{
        $this->plotId = $plotId;
    }
    
    /**
     * Updates the time frame of the build test.
     *
     * @param int $timeframe
     */
    public function updateTimeFrame(int $timeframe) : void{
        $this->timeFrame = $timeFrame;
    }
    
    /**
     * Updates the status of the build test.
     *
     * @param int $status
     */
    public function updateStatus(int $status) : void{
        if($status < BuildTest::STATUS_NOT_STARTED or $status > BuildTest::STATUS_START_EXPIRED){
            throw new \InvalidArgumentException("Invalid build test status {$status}");
        }
        if($status === BuildTest::STATUS_BEING_RUN){
            $this->setStart(time());
        }
        $this->status = $status;
    }
    
    /**
     * Updates the build test info.
     * Only used when the player picks the build test build.
     *
     * @param array $build
     */
    public function updateBuild(array $build) : void{
        if(!isset($build["id"]) || !isset($build["title"]) || !isset($build["instructions"]) || !isset($build["image"])){
            throw new \InvalidArgumentException("Invalid build test data " . json_encode($build));
        }
        $this->build = $build;
    }
    
    /**
     * Returns the seconds left to finish a build test.
     *
     * @return int
     */
    public function getTimeLeft() : int{
        return (3600 * $this->getTimeFrame()) - (time() - $this->getStart());
    }
    
    /**
     * Returns all the build test builds available to select.
     *
     * @param bool $ids
     *
     * @return array|string[]
     */
    public static function getBuildTestBuilds(bool $ids = false) : array{
        $builds = Loader::getInstance()->getConfig()->get("builds");
        if($ids){
            return array_keys($builds);
        }
        $buildTestBuilds = [];
        foreach($builds as $id => $build){
            $buildTestBuilds[] = ["id" => $id, "title" => $build["title"], "instructions" => $build["instructions"], "image" => $build["image"]];
        }
        return $buildTestBuilds;
    }
    
    /**
     * Returns the build test build info by its id.
     *
     * @param string $id
     *
     * @return array|null
     */
    public static function getBuildTestBuildById(string $id){
        foreach(self::getBuildTestBuilds() as $buildTestBuild){
            if($buildTestBuild["id"] === $id){
                return $buildTestBuild;
            }
        }
        return null;
    }
    
}
