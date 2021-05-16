<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\math\AxisAlignedBB;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use kenygamer\Core\LangManager;

class Near extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("List players around you");
        $this->setUsage("/near");
        $this->setPermission("core.command.near");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.near")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$player = $sender;
        $near = $this->getNearPlayers($player);
        LangManager::send("core-near", $sender, implode(", ", $near));
		return true;
	}
	
	/**
     * Let you see who is near a specific player
     * @param Player $player
     * @param int $radius
     * @return null|Player[]
     */
    public function getNearPlayers(Player $player, int $radius = null): ?array{
        if ($radius === null || !is_numeric($radius)) {
            $radius = 200;
        }
        if (!is_numeric($radius)) {
            return null;
        }
        /** @var Player[] $players */
        $players = [];
        foreach($player->getLevel()->getNearbyEntities(new AxisAlignedBB($player->getFloorX() - $radius, $player->getFloorY() - $radius, $player->getFloorZ() - $radius, $player->getFloorX() + $radius, $player->getFloorY() + $radius, $player->getFloorZ() + $radius), $player) as $e) {
            if ($e instanceof Player) {
                $players[] = TextFormat::clean($e->getDisplayName());
            }
        }
        return $players;
    }
}