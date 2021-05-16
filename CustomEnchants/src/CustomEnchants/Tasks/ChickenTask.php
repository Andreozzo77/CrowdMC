<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Main;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

/**
 * Class ChickenTask
 * @package CustomEnchants\Tasks
 */
class ChickenTask extends Task
{
    private $plugin;

    /**
     * ChickenTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $enchantment = $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::CHICKEN);
            if ($enchantment !== null) {
                if (!isset($this->plugin->chickenTick[$player->getName()])) {
                    $this->plugin->chickenTick[$player->getName()] = 0;
                }
                $this->plugin->chickenTick[$player->getName()]++;
                if ($this->plugin->chickenTick[$player->getName()] >= 5 * 1200) {
                    $random = \kenygamer\Core\Main::mt_rand(0, 100);
                    if ($random <= 5 * $enchantment->getLevel()) {
                        $drops = $this->plugin->getConfig()->getNested("chicken.rare-drop");
                        if (!is_array($drops)) {
                            $drops = ["266:0:1"];
                        }
                        $drop = array_rand($drops, 1);
                        $drop = explode(":", $drops[$drop]);
                        $item = count($drop) < 3 ? Item::get(Item::GOLD_INGOT, 0, 1) : Item::get($drop[0], $drop[1], $drop[2]);
                        $vowels = ["a", "e", "i", "o", "u"];
                        $player->getLevel()->dropItem($player, $item, $player->getDirectionVector()->multiply(-0.4));
                        $player->sendTip(TextFormat::GREEN . "You have layed a" . (in_array(mb_strtolower($item->getName()[0]), $vowels) ? "n " : " ") . $item->getName() . "...");
                    } else {
                        $player->getLevel()->dropItem($player, Item::get(Item::EGG, 0, 1), $player->getDirectionVector()->multiply(-0.4));
                        $player->sendTip(TextFormat::GREEN . "You have layed an egg.");
                    }
                    $this->plugin->chickenTick[$player->getName()] = 0;
                }
            }
        }
    }
}