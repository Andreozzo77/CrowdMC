<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class RadarTask
 * @package CustomEnchants
 */
class RadarTask extends Task
{
    private $plugin;
    private $radars;

    /**
     * RadarTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $radar = false;
            foreach ($player->getInventory()->getContents() as $item) {
                $enchantment = $item->getEnchantment(CustomEnchantsIds::RADAR);
                if ($enchantment !== null) {
                    $detected = $this->plugin->findNearestEntity($player, $enchantment->getLevel() * 50, Player::class, $player);
                    if (!is_null($detected)) {
                        $pk = new SetSpawnPositionPacket();
                        $pk->x = $pk->x2 = (int)$detected->x;
                        $pk->y = $pk->y2 = (int)$detected->y;
                        $pk->z = $pk->z2 = (int)$detected->z;
                        $pk->dimension = DimensionIds::OVERWORLD;
                        $pk->spawnType = SetSpawnPositionPacket::TYPE_WORLD_SPAWN;
                        $player->sendDataPacket($pk);
                        $radar = true;
                        $this->radars[$player->getName()] = true;
                        if ($item->equalsExact($player->getInventory()->getItemInHand())) {
                            $player->sendTip(TextFormat::GREEN . "Nearest player " . round($player->distance($detected), 1) . " blocks away.");
                        }
                        break;
                    } else {
                        if ($item->equalsExact($player->getInventory()->getItemInHand())) {
                            $player->sendTip(TextFormat::RED . "No players found.");
                        }
                    }
                }
            }
            if (!$radar) {
                if (isset($this->radars[$player->getName()])) {
                    $pk = new SetSpawnPositionPacket();
                    $pk->x = $pk->x2 = (int)$player->getLevel()->getSafeSpawn()->x;
                    $pk->y = $pk->y2 = (int)$player->getLevel()->getSafeSpawn()->y;
                    $pk->z = $pk->z2 = (int)$player->getLevel()->getSafeSpawn()->z;
                    $pk->dimension = DimensionIds::OVERWORLD;
                    $pk->spawnType = SetSpawnPositionPacket::TYPE_WORLD_SPAWN;
                    $player->sendDataPacket($pk);
                    unset($this->radars[$player->getName()]);
                }
            }
        }
    }
}