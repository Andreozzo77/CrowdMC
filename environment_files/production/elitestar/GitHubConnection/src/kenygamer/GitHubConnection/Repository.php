<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

final class Repository{
	public const REPO_PUBLIC = "public";
	public const REPO_PRIVATE = "private";
	
	/** @var string */
	private $user;
	/** @var string */
	private $repo;
	/** @var string */
	private $password = "";
	/** @var string */
	private $visibility = self::REPO_PUBLIC;
	/** @var string */
	private $branch = "master";
	/** @var string[] */
	private $plugins = [];
	
	/** @var string */
	private $repository;
	/** @var array */
	private $data;
	
	public function __construct(string $repository, array $data){
		$this->repository = $repository;
		$this->data = $data;
		$this->validate();
		echo "Repository " . $repository;
	}
	
	private function validate() : void{
		if(count($parts = explode("/", $this->repository)) !== 2){
			throw new \InvalidArgumentException("Repository must be written in the format: user/repo");
		}
		$this->user = $parts[0];
		$this->repo = $parts[1];
		
		$branch = $this->data["branch"] ?? "";
		if(trim($branch) === ""){
			throw new \InvalidArgumentException("Repo branch must not be empty");
		}
		$this->branch = $branch;
		
		$visibility = $this->data["visibility"] ?? "";
		if($visibility !== self::REPO_PUBLIC && $visibility !== self::REPO_PRIVATE){
			throw new \InvalidArgumentException("Invalid visibility, must be " . self::REPO_PUBLIC . " or " . self::REPO_PRIVATE);
		}
		$this->visibility = $visibility;
		
		$password = $this->data["password"] ?? "";
		if($visibility === self::REPO_PRIVATE && trim($password) === ""){
			throw new \InvalidArgumentException("Auth password must not be empty if repo is private");
		}
		$this->password = $password;
		
		$plugins = $this->data["plugins"] ?? "";
		array_walk($plugins, function(string $plugin, int $key){});
		$this->plugins = $plugins;
	}
	
	public function getRepository() : string{
		return $this->repository;
	}
	
	public function getUser() : string{
		return $this->user;
	}
	
	public function getRepo() : string{
		return $this->repo;
	}
	
	public function getBranch() : string{
		return $this->branch;
	}
	
	public function getVisibility() : string{
		return $this->visibility;
	}
	
	public function getPassword() : string{
		return $this->password;
	}
	
	public function getPlugins() : array{
		return $this->plugins;
	}
}