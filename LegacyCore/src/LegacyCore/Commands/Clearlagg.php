<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\StringTag;

use kenygamer\Core\entity\EasterEgg;
use kenygamer\Core\LangManager;

class Clearlagg extends PluginCommand{
	/** @var array */
    private $exemptedEntities = [];
	/** @var Core */
	private $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Remove all server mobs/drops!");
        $this->setUsage("/lagg <clear|check|killmobs|clearall>");
        $this->setAliases(["lagg"]);
        $this->setPermission("core.command.clearlagg");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.clearlagg")) {
            LangManager::send("cmd-noperm", $sender);
            return false;
        }
        if (count($args) < 1) {
			LangManager::send("core-clearlagg-usage", $sender);
            return false;
        }
		if (isset($args[0])) {
			switch($args[0]) {
				case "clear":
				case "c":
				LangManager::send("core-clearlagg-entities", $sender, $this->removeEntities());
				return true;
				case "check":
				case "count":
				$c = $this->getEntityCount();
				LangManager::send("core-clearlagg-check", $sender, $c[1], $c[2]);
				return true;
				case "killmobs":
				case "mobs":
				LangManager::send("core-clearlagg-mobs", $sender, $this->removeMobs());
				return true;
				case "clearall":
				case "cl":
				case "all":
				LangManager::send("core-clearlagg-all", $sender, $this->removeMobs(), $this->removeEntities());
				return true;
				default:
				LangManager::send("core-clearlagg-usage", $sender);
				return false;
			}
		}
		return false;
	}
	
	private function isBomby(Entity $entity){
		return $entity::NETWORK_ID === Entity::CREEPER && $entity->namedtag->hasTag("Bomby", StringTag::class);
	}

	/**
	 * @return int
	 */
	public function removeEntities(): int {
        $i = 0;
        foreach($this->plugin->getServer()->getLevels() as $level) {
            foreach($level->getEntities() as $entity) {
                if (!$this->isEntityExempted($entity) && !($entity instanceof Creature) && !$entity instanceof Human && !($entity instanceof EasterEgg) && !$this->isBomby($entity)){
                    $entity->close();
                    $i++;
				}
			}
		}
        return $i;
	}

	/**
	 * @return int
	 */
    public function removeMobs(): int {
        $i = 0;
        foreach($this->plugin->getServer()->getLevels() as $level) {
            foreach($level->getEntities() as $entity) {
                if (!$this->isEntityExempted($entity) && $entity instanceof Creature && !($entity instanceof Human) && !($entity instanceof EasterEgg) && !$this->isBomby($entity)){
                	$entity->close();
                	$i++;
				}
			}
		}
        return $i;
	}

	/**
	 * @return array
	 */
    public function getEntityCount(): array {
        $ret = [0, 0, 0];
        foreach($this->plugin->getServer()->getLevels() as $level) {
            foreach($level->getEntities() as $entity) {
                if ($entity instanceof Human) {
                    $ret[0]++;
                } else {
                    if ($entity instanceof Creature && !($entity instanceof EasterEgg) && !$this->isBomby($entity)){
                        $ret[1]++;
                    } else {
                        $ret[2]++;
					}
				}
			}
		}
        return $ret;
	}

	/**
	 * @param Entity $entity
	 */
    public function exemptEntity(Entity $entity): void {
        $this->plugin->exemptedEntities[$entity->getID()] = $entity;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
    public function isEntityExempted(Entity $entity): bool {
        return isset($this->plugin->exemptedEntities[$entity->getID()]);
	}
}