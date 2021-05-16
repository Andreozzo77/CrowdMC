<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\item\Durable;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;

use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;

class Kit extends PluginCommand{
	/** @var Core */
	public $plugin;
	/** @var \StdClass[] */
	public $kits = [];
	
	public function __construct(string $name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Get a kit");
        $this->setUsage("/kit");
        $this->setPermission("core.command.kit");
		$this->plugin = $plugin;
		$plugin->saveResource("kits.yml", true);
		
		//Minimize the `loadbefore:` uses as much as we can (in this case CustomEnchants)
		$plugin->getScheduler()->scheduleDelayedTask(new class($this) extends Task{
			/** @var Kit */
			private $cmd;
			public function __construct(Kit $cmd){
				$this->cmd = $cmd;
			}
			
			public function onRun(int $currentTick) : void{
				$kits = (new Config($this->cmd->plugin->getDataFolder() . "kits.yml", Config::YAML))->getAll();
				foreach($kits as $kitName => $entries){
					//Register custom permission default in plugin.yml
					$this->cmd->kits[$kitName] = new \StdClass();
					$this->cmd->kits[$kitName]->cooldown = $entries["cooldown"];
					$this->cmd->kits[$kitName]->items = [];
			
			        $this->cmd->kits[$kitName]->permission = $permission = "core.kit." . $kitName;
			        if(PermissionManager::getInstance()->getPermission($permission) === null){
			        	$this->cmd->plugin->getServer()->getPluginManager()->addPermission(new Permission($permission), "Use the " . ucfirst($kitName) . " Kit", Permission::DEFAULT_OP);
			        }
			        $this->cmd->kits[$kitName]->items = ItemUtils::parseItems($entries["items"]);
				}
			}
		}, 1);
   }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.kit")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$this->KitUI($sender);
		return true;
	}

	/**
	 * @param Player $player
     */
	public function KitUI(Player $player) : void{ 
		$form = new SimpleForm(function(Player $player, ?int $kit){
			if(is_int($kit)){
				$names = array_keys($this->kits);
				if(isset($names[$kit])){
					$kitName = $names[$kit];
					$kit = $this->kits[$kitName];
					if(!$player->hasPermission($kit->permission)){
						$player->chat("/kit");
						return;
					}
					
					$form = new ModalForm(function(Player $player, ?bool $confirm) use($kit, $kitName){
						if(!$confirm){
							$player->chat("/kit");
							return;
						}
						$cooldown = $this->plugin->kitcooldown->getNested($kitName . "." . $player->getName(), time());
						if(time() >= $cooldown || $player->isOp()){
							if(ItemUtils::addItems($player->getInventory(), ...$kit->items)){
								$this->plugin->kitcooldown->setNested($kitName . "." . $player->getName(), time() + $kit->cooldown);
								LangManager::send("core-kit", $player, ucfirst($kitName));
							}else{
								LangManager::send("inventory-nospace", $player);
							}
						}else{
							LangManager::send("core-kit-cooldown", $player, Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft($cooldown), TextFormat::RED, TextFormat::RED));
						}
					});
					$form->setTitle(LangManager::translate("core-kit2-title", $player, ucfirst($kitName)));
					$form->setContent(LangManager::translate("core-kit-details", $player, ItemUtils::getDescription($kit->items), Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft(time() + $kit->cooldown), TextFormat::GOLD, TextFormat::GOLD)));
					$form->setButton1(LangManager::translate("continue", $player));
					$form->setButton2(LangManager::translate("cancel", $player));
					$player->sendForm($form);
				}
			}
		});
		$form->setTitle(LangManager::translate("core-kit-title", $player));
		$form->setContent(LangManager::translate("core-kit-desc", $player));
		foreach($this->kits as $kitName => $kit){
			if($player->hasPermission($kit->permission)){
				$form->addButton(LangManager::translate("core-kit-kit", $player, ucfirst($kitName)));
			}else{
				$form->addButton(LangManager::translate("core-kit-kit", $player, ucfirst($kitName)) . "\n" . LangManager::translate("locked", $player));
			}
		}
		$player->sendForm($form);
	}

}