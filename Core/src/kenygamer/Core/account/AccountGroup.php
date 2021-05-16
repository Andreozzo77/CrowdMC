<?php

declare(strict_types=1);

namespace kenygamer\Core\account;

class AccountGroup{
	/** @var string[] */
	private $usernames;
	
	/**
	 * @param string ...$usernames
	 */
	public function __construct(string ...$usernames){
		$this->usernames = $usernames;
	}
	
	/**
	 * @return string[]
	 */
	public function getUsernames() : array{
		return $this->usernames;
	}
	
	/**
	 * @return string[]
	 */
	public function exempt(string $username) : array{
		$usernames = $this->getUsernames();
		unset($usernames[array_search($username, $usernames)]);
		return $usernames;
	}
	
	/**
	 * @return bool
	 */
	public function contains(string $username) : bool{
		return in_array($username, $this->getUsernames());
	}
	
	/**
	 * Add an username to the group.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function addUsername(string $username) : bool{
		if(!$this->contains($username)){
			$this->usernames[] = $username;
			return true;
		}
		return false;
	}
	
	/**
	 * Remove an username from the group.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function removeUsername(string $username) : bool{
		if($this->contains($username)){
			unset($this->usernames[array_search($username, $this->usernames)]);
			return true;
		}
		return false;
	}
	
}