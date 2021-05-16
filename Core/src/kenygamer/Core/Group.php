<?php

declare(strict_types=1);

namespace kenygamer\Core;

class Group{
	/** @var bool[] */
	private $permissions = [];
	
	/** @var string */
	private $group;
	/** @var string[] */
	private $inheritance;
	/** @var string|null */
	private $chat;
	/** @var string|null */
	private $nametag;
	
	public function __construct(string $name, array $permissions, array $inheritance, string $chat, string $nametag){
		$this->name = $name;
		$this->chat = $chat;
		$this->nametag = $nametag;
		$this->inheritance = $inheritance;
		
		$manager = Main::getInstance()->permissionManager;
		 
		foreach($inheritance as $group){
			$obj = $manager->getGroup($group);
			$permissions = array_merge($obj->getPermissions(), $permissions);
		}
		$this->permissions = $permissions;
	}
	
	public function getName() : string{
		return $this->name;
	}
	
	public function getPermissions() : array{
		return $this->permissions;
	}
	
	public function getInheritance() : array{
		return $this->inheritance;
	}
	
	public function getChatFormat() : string{
		return $this->chat;
	}
	
	public function getNametagFormat() : string{
		return $this->nametag;
	}
	
}