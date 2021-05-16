<?php

declare(strict_types=1);

namespace kenygamer\Core\survey;

use pocketmine\Player;

use function in_array;
use function is_bool;
use function intval;
use function abs;

class Survey{
	public const CANVOTE_NOPERMISSION = -3;
	public const CANVOTE_EXPIRED = -2;
	public const CANVOTE_ENDED = -1;
	public const CANVOTE_VOTED = 0;
	public const CANVOTE = 1;
	
	/** @var string */
	private $name;
	/** @var int */
	private $expiry;
	/** @var bool */
	private $voteStats;
	/** @var array */
	private $formData;
	/** @var array */
	public $votes;
	
	public function __construct(string $name, int $expiry, bool $voteStats, array $formData, array $votes){
		$this->name = $name;
		$this->expiry = $expiry;
		$this->voteStats = $voteStats;
		$this->formData = $formData;
		$this->votes = $votes;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}
	
	/**
	 * @api
	 * @return int
	 */
	public function getExpiry() : int{
		return $this->expiry;
	}
	
	/**
	 * @api
	 * @return bool
	 */
	public function hasEnded() : bool{
		if($this->expiry > 0){
			return time() >= $this->expiry;
		}
		if($this->expiry !== 0){
			return ($this->getVoteCount(false) + $this->getVoteCount(true)) > abs($this->expiry);
		}
		return false;
	}
	
	/**
	 * @api
	 * @return bool
	 */
	public function voteStatsEnabled() : bool{
		return $this->voteStats;
	}
	
	/**
	 * @api
	 * @param bool|int $opt
	 * @return int
	 */
	public function getVoteCount($opt) : int{
		$c = 0;
		if(!$this->voteStats){
			return $c;
		}
		foreach($this->votes as $player => $data){
			if($data["opt"] == $opt){
				++$c;
			}
		}
		return $c;
	}
	
	/**
	 * @api
	 * @param Player $player
	 * @return int
	 */
	public function canVote(Player $player) : int{
		if(!($player->hasPermission("eliteextras.command.survey") || $player->hasPermission("eliteextras.survey." . $this->getName()))){
			return self::CANVOTE_NOPERMISSION;
		}
		if($this->hasEnded()){
			return self::CANVOTE_ENDED;
		}
		if($this->hasVoted($player)){
			return self::CANVOTE_VOTED;
		}
		return self::CANVOTE;
	}
	
	/**
	 * @api
	 * @param Player $player
	 * @return bool
	 */
	public function hasVoted(Player $player) : bool{
		foreach($this->votes as $issuer => $data){
			if($issuer === $player->getName() || $data["ip"] === $player->getAddress() || ($player->isAuthenticated() && $data["xuid"] === $player->getXuid())){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @api
	 * @param Player $player
	 * @param bool|int $opt
	 * @return bool
	 */
	public function addVote(Player $player, $opt) : bool{
		if($this->hasVoted($player)){
			return false;
		}
		$this->votes[$player->getName()] = [
		    "ip" => $player->getAddress(),
		    "xuid" => $player->getXuid(),
		    "opt" => intval($opt)
		];
		return true;
	}
				
	
	/**
	 * @api
	 * @return array
	 */
	public function getFormData() : array{
		return $this->formData;
	}
	
}