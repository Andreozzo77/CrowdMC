<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\RemoteConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand{
	/** @var Main */
	private $plugin;
	/** @var array */
	private $syntax;
	/** @var int */
	private $executors;
	
	public const EXECUTOR_PLAYER = 0x1;
	public const EXECUTOR_CONSOLE = 0x2;
	public const EXECUTOR_RCON = 0x4;
	public const EXECUTOR_ALL = 0x8;
	
	private const PERMISSION_NODE = "core.command.{command}";
	
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [], int $executors = self::EXECUTOR_ALL, string $defaultPermission = Permission::DEFAULT_OP, string $permissionNode = null){
		parent::__construct($name, $description, $usageMessage, $aliases);
		
		if(!is_string($permissionNode)){
			$permissionNode = str_replace("{command}", $name, self::PERMISSION_NODE);
		}
		PermissionManager::getInstance()->addPermission(new Permission($permissionNode, "Allows access to the " . $name . " command", $defaultPermission, []));
		
		$this->setPermission($permissionNode);
		
		$this->plugin = Main::getInstance();
		$this->syntax = $this->plugin->getConfig()->getNested("commands." . $name, []);
		$this->executors = $executors;
	}
	
	public function getPlugin() : Plugin{
		return $this->plugin;
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
	    $argsCopy = $args;
		if(!$this->plugin->isEnabled()){
			return false;
		}
		if(!$this->testPermission($sender)){
			return false;
		}
	    $canRun = $this->executors & self::EXECUTOR_ALL;
	    if(!$canRun){
			if($sender instanceof Player){
				$canRun = $this->executors & self::EXECUTOR_PLAYER;
			}
			if($sender instanceof RemoteConsoleCommandSender && !($sender instanceof ConsoleCommandSender)){
				$canRun = $this->executors & self::EXECUTOR_RCON;
			}
			if($sender instanceof ConsoleCommandSender){
				$canRun = $this->executors & self::EXECUTOR_CONSOLE;
			}
		}
		if(!$canRun){
		 	$sender->sendMessage(TextFormat::RED . "You can't use this command from here.");
			return false;
		}
		$hasSubcommands = false;
		foreach($this->syntax as $overload => $mixed){
		    if(!empty($mixed)){
				if(!isset($mixed["type"])){
					$hasSubcommands = true;
				}
				
				unset($mixed["isOptional"]);
				assert(!empty($mixed));
			}
		}
		
		$gotOptionalArg = false;
		if($hasSubcommands){
		    if(!isset($this->syntax[$args[0] ?? null])){
		    	throw new InvalidCommandSyntaxException();
		    }
		    $subcommand = $this->syntax[$args[0]];
		    array_shift($args);
		   
			
			foreach($subcommand as $arg => $validation){
				if(!self::isValidArgument(array_shift($args), $validation)){
					throw new InvalidCommandSyntaxException();
				}
				if(!($isOptional = $validation["isOptional"] ?? false) && $gotOptionalArg){ //TODO this should not be induced by the user
					throw new \LogicException("Arguments after an optional argument cannot be non-optional");
				}
				if($isOptional){
					$gotOptionalArg = true;
				}
			}
		}else{
			foreach($this->syntax as $arg => $validation){
				if(!self::isValidArgument(array_shift($args), $validation)){
					throw new InvalidCommandSyntaxException();
				}
				if(!($isOptional = $validation["isOptional"] ?? false) && $gotOptionalArg){ //TODO this should not be induced by the user
					throw new \LogicException("Arguments after an optional argument cannot be non-optional");
				}
				if($isOptional){
					$gotOptionalArg = true;
				}
			}
		}
		
		//HACK! Resolves lang strings for console and RCON senders
		if($sender instanceof RemoteConsoleCommandSender){
			//Remote console sender is an immutable class, as RCON::check() will keep a reference to the old object to
			//read its messages property. This would cause messages not to send, unless we hack it into as follows:
			//preprocessing the message in a fake RemoteConsoleCommandSender class, then actually sending it or better said,
			//appending it to the messages buffer of the real sender.
			$sender = new class($sender) extends RemoteConsoleCommandSender{
				private $realSender = null;
				public function __construct(RemoteConsoleCommandSender $sender){
					$this->realSender = $sender;
				}
				public function sendMessage($msg, ...$params){
					$lang = LangManager::getInstance();
					if($lang instanceof LangManager && is_string($msg) && $lang->langExists($msg, LangManager::LANG_DEFAULT)){
						$msg = LangManager::translate($msg, ...$params);
					}
					$this->realSender->sendMessage($msg);
				}
			};
		}elseif($sender instanceof ConsoleCommandSender){
			$sender = new class() extends ConsoleCommandSender{
				public function sendMessage($msg, ...$params){
					$lang = LangManager::getInstance();
					if($lang instanceof LangManager && is_string($msg) && $lang->langExists($msg, LangManager::LANG_DEFAULT)){
						LangManager::send($msg, $this, ...$params);
					}else{
						parent::sendMessage($msg);
					}
				}
			};
		}
		return $this->onExecute($sender, $argsCopy);
	}
	
	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	abstract protected function onExecute($sender, array $args) : bool;
	
	private static function isValidArgument(?string $arg, array $validation) : bool{
		if($arg === null){
			if($validation["isOptional"] ?? false){
				return true;
			}
			return false;
		}
		switch($validation["type"] ?? null){
			case "string":
			case "text":
			    return true;
			    break;
			case "int":
			    return is_numeric($arg) && strpos($arg, ".") === false;
			    break;
			case "float":
			    return is_numeric($arg);
			    break;
			case "target":
			    return Server::getInstance()->getPlayer($arg) !== null;
				break;
			case "enum":
			    return in_array($arg, $validation["enum"] ?? []);
				break;
			default:
			    return true;
		}
	}
	
}