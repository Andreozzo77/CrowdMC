<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Effect as PMEffect;
use pocketmine\entity\EffectInstance;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
class Effect extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Buy an effect for money");
        $this->setUsage("/effect");
        $this->setPermission("core.command.effects");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.effects")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$this->EffectUI($sender);
		return true;
	}
	
	/**
	 * @param EffectUI
	 * @param Player $player
     */
	public function EffectUI(Player $player) : void{
		$form = new SimpleForm(function (Player $player, int $data = null) {
        $result = $data;
        if ($result === null) {
            return;
        }
		switch($result) {
			case 0:
			$this->SpeedUI($player);
			break;
			case 1:
			$this->HasteUI($player);
			break;
			case 2:
			$this->StrengthUI($player);
			break;
			case 3:
			$this->JumpUI($player);
			break;
			case 4:
			$this->RegenerationUI($player);
			break;
			case 5:
			$this->DamageResistanceUI($player);
			break;
			case 6:
			$this->FireResistanceUI($player);
			break;
			case 7:
			$this->WaterBreathingUI($player);
			break;
			case 8:
			$this->InvisiblityUI($player);
			break;
			case 9:
			$this->NightVisionUI($player);
			break;
			case 10:
			$this->HealthBootsUI($player);
			break;
			case 11:
			$this->AbsorptionUI($player);
			break;
		    }
		});
		$form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->setContent(LangManager::translate("core-effect-desc", $player));
		$form->addButton(TextFormat::GREEN . "Speed\n§b$500,000");
		$form->addButton(TextFormat::GREEN . "Haste\n§b$100,000");
		$form->addButton(TextFormat::GREEN . "Strength\n§b$500,000");
		$form->addButton(TextFormat::GREEN . "Jump Boost\n§b$250,000");
		$form->addButton(TextFormat::GREEN . "Regeneration\n§b$500,000");
		$form->addButton(TextFormat::GREEN . "Damage Resistance\n§b$500,000");
		$form->addButton(TextFormat::GREEN . "Fire Resistance\n§b$100,000");
		$form->addButton(TextFormat::GREEN . "Water Breathing\n§b$100,000");
		$form->addButton(TextFormat::GREEN . "Invisiblity\n§b$1,000,000");
		$form->addButton(TextFormat::GREEN . "Night Vision\n§b$100,000");
		$form->addButton(TextFormat::GREEN . "Health Boost\n§b$500,000");
		$form->addButton(TextFormat::GREEN . "Absorption\n§b$250,000");
		$form->sendToPlayer($player);
	}
	
	/**
	 * @param SpeedUI
	 * @param Player $player
     */
    public function SpeedUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 500000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::SPEED), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Speed", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
         $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aSpeed $500,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 5, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param HasteUI
	 * @param Player $player
     */
    public function HasteUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 100000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                	LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::HASTE), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Haste", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aHaste $100,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 5, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param StrengthUI
	 * @param Player $player
     */
    public function StrengthUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 500000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::STRENGTH), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Strength", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aStrength $500,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 10, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param JumpUI
	 * @param Player $player
     */
    public function JumpUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 250000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::JUMP), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Jump", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aJump Boost $250,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 3, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param RegenerationUI
	 * @param Player $player
     */
    public function RegenerationUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 500000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::REGENERATION), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Regeneration", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle("§l§eElite§6Star §r§aEffects Regeneration!");
		$form->addLabel("§aRegeneration $500,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 5, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param DamageResistanceUI
	 * @param Player $player
     */
    public function DamageResistanceUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 500000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::DAMAGE_RESISTANCE), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Damage Resistance", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aDamage Resistance $500,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 5, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param FireResistanceUI
	 * @param Player $player
     */
    public function FireResistanceUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 100000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::FIRE_RESISTANCE), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Fire Resistance", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aFire Resistance $100,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 1, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param WaterBreathingUI
	 * @param Player $player
     */
    public function WaterBreathingUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 100000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::WATER_BREATHING), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Water Breathing", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aWater Breathing $100,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 1, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param InvisiblityUI
	 * @param Player $player
     */
    public function InvisiblityUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 1000000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::INVISIBILITY), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Invisibility", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aInvisiblity $1,000,000 ");
        $form->addSlider("Second" , 100, 1000, 1);
		$form->addSlider("Level" , 1, 1, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param NightVisionUI
	 * @param Player $player
     */
    public function NightVisionUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 100000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::NIGHT_VISION), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Night Vision", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
        $form->setTitle("§l§eElite§6Star §r§aEffects Night Vision!");
		$form->addLabel("§aNight Vision $100,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 1, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param HealthBootsUI
	 * @param Player $player
     */
    public function HealthBootsUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 500000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::HEALTH_BOOST), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Health Boost", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
         $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aHealth Boost $500,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 5, 1);
        $form->sendToPlayer($player);
	}
	
	/**
	 * @param AbsorptionUI
	 * @param Player $player
     */
    public function AbsorptionUI(Player $player) : void{
		$form = new CustomForm(function (Player $player, array $data = null) {
        $result = $data[1];
		$result2 = $data[2];
        if ($result != null || $result2 != null) {
                $cost = 250000 * $result2;
                if (!Main::getInstance()->reduceMoney($player, $cost)) {
                    LangManager::send("money-needed", $player, $cost);
                    return true;
				}
			$effect = new EffectInstance(PMEffect::getEffect(PMEffect::ABSORPTION), $result * 20, $result2 - 1, true);
			LangManager::send("core-effect", $player, "Absorption", $result2, $result, $cost);
			$player->addEffect($effect);
			return true;
        }
        });
         $form->setTitle(LangManager::translate("core-effect-title", $player));
		$form->addLabel("§aAbsorption $250,000 ");
        $form->addSlider("Second" , 100, 5000, 1);
		$form->addSlider("Level" , 1, 20, 1);
        $form->sendToPlayer($player);
	}
}