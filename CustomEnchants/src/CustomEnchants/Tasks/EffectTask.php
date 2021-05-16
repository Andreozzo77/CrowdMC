<?php

namespace CustomEnchants\Tasks;

use CustomEnchants\Main;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;

/**
 * Class EffectTask
 * @package CustomEnchants\Tasks
 */
class EffectTask extends Task
{
    private $plugin;
    private $check = [];
    private $checked = [];
    
    /**
     * EffectTask constructor.
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
    	$online = $this->plugin->getServer()->getOnlinePlayers();
    	if(count($online) < 1){
    		return;
    	}
        if(empty($this->check)){
        	$this->check = $online;
        	foreach($this->check as $p){
        		$n = $p->getName();
        		if(isset($this->checked[$n]) && !(microtime(true) - $this->checked[$n] > 0.75)){
        			foreach($this->check as $i => $pp){
        				if($pp->getName() === $n){
        					unset($this->check[$i]);
        					break 2;
        				}
        			}
        		}
        	}
        }
        $check = array_splice($this->check, 0, ceil(count($online) / 5));
        foreach($check as $p){
        	$this->checked[$p->getName()] = microtime(true);
        }
        $this->checkPlayers($check);
    }
    
    private function checkPlayers(array $players) : void{
    	foreach ($players as $player){
    		if(!$player->isOnline()){
    			continue;
    		}
        	$item = $player->getInventory()->getItemInHand();
        	$armor = $player->getArmorInventory();
            $enchantment = $item->getEnchantment(CustomEnchantsIds::HASTE);
            if($enchantment !== null){
				$effect = new EffectInstance(Effect::getEffect(Effect::HASTE), 30, $enchantment->getLevel() - 1, false); 
                $player->addEffect($effect);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::ENRAGED);
            if ($enchantment !== null) {
				$effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 30, $enchantment->getLevel() - 1, false); 
                $player->addEffect($effect);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::RAGE);
            if ($enchantment !== null) {
				$effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 30, $enchantment->getLevel() - 1, false); 
                $player->addEffect($effect);
            }
            $enchantment = $armor->getHelmet()->getEnchantment(CustomEnchantsIds::GLOWING);
            if ($enchantment !== null) {
				$effect = new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 240, 0, false);
                $player->addEffect($effect);
                $this->plugin->glowing[$player->getName()] = true;
            } else {
                if (isset($this->plugin->glowing[$player->getName()])) {
                    $player->removeEffect(Effect::NIGHT_VISION);
                    unset($this->plugin->glowing[$player->getName()]);
                }
            }
            $enchantment = $armor->getHelmet()->getEnchantment(CustomEnchantsIds::AQUATIC);
            if ($enchantment !== null) {
				$effect = new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 30, 0, false);
                $player->addEffect($effect);
            }
            $enchantment = $armor->getBoots()->getEnchantment(CustomEnchantsIds::GEARS);
            if ($enchantment !== null) {
                $effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 30, $enchantment->getLevel() - 1, false); 
                $player->addEffect($effect);
            }
			$enchantment = $armor->getBoots()->getEnchantment(CustomEnchantsIds::SPRINGS);
            if ($enchantment !== null) {
                $effect = new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 30, $enchantment->getLevel() - 1, false); 
                $player->addEffect($effect);
            }
            $solarpoints = 0;
            $nightpoints = 0;
            foreach ($armor->getContents() as $slot => $armor) {
                $enchantment = $armor->getEnchantment(CustomEnchantsIds::SHILEDED);
                if ($enchantment !== null) {
					$effect = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 30, $enchantment->getLevel() - 1, false); 
                    $player->addEffect($effect);
                }
				$enchantment = $armor->getEnchantment(CustomEnchantsIds::ANGEL);
                if ($enchantment !== null) {
					$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 30, $enchantment->getLevel() - 1, false); 
                    $player->addEffect($effect);
                }
                $solar = $armor->getEnchantment(CustomEnchantsIds::SOLARPOWDERED);
                if($solar !== null){
                	$solarpoints += $solar->getLevel();
                }
                $night = $armor->getEnchantment(CustomEnchantsIds::NIGHTOWL);
                if($night !== null){
                	$nightpoints += $night->getLevel();
                }
            }
            if($solarpoints >= 4){
            	$effects = false;
            	$time = $player->getLevel()->getTime();
            	if($time >= Level::TIME_DAY){
            		if($time >= Level::TIME_SUNSET && $time < Level::TIME_NIGHT){
            			if(\kenygamer\Core\Main::mt_rand(0, Level::TIME_NIGHT - Level::TIME_SUNSET) <= Level::TIME_SUNSET - $time){
            				$effects = true;
            			}
            		}elseif($time < Level::TIME_SUNSET){
            			$effects = true;
            		}
            	}
            	if($effects){
            		$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 30, (int) round($solarpoints / 4) - 1, false);
            		$player->addEffect($effect);
            		$effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 30, (int) round($solarpoints / 4) - 1, false);
            		$player->addEffect($effect);
            	}
            }
            if($nightpoints >= 4){
            	$effects = false;
            	$time = $player->getLevel()->getTime();
            	if($time >= Level::TIME_NIGHT){
            		if($time >= Level::TIME_SUNRISE && $time < Level::TIME_FULL){
            			if(\kenygamer\Core\Main::mt_rand(0, Level::TIME_FULL - Level::TIME_SUNRISE) <= Level::TIME_FULL - $time){
            				$effects = true;
            			}
            		}elseif($time < Level::TIME_SUNRISE){
            			$effects = true;
            		}
            	}
            	if($effects){
            		$effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 30, (int) round($nightpoints / 4) -1, false);
            		$player->addEffect($effect);
            		$effect = new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 30, (int) round($nightpoints / 4) - 1, false);
            		$player->addEffect($effect);
            	}
            }
        }
    }
}