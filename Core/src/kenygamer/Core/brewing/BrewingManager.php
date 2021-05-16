<?php

declare(strict_types=1);

namespace kenygamer\Core\brewing;

use pocketmine\inventory\CraftingManager;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Potion;

class BrewingManager extends CraftingManager{
	private static $instance = null;
	
	/** @var BrewingRecipe[] */
	protected $brewingRecipes = [];

	public function __construct(){
		parent::__construct();
		self::$instance = $this;
	}
	
	public static function getInstance() : ?self{
		return self::$instance;
	}

	public function init() : void{
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::AWKWARD, 1), ItemFactory::get(Item::NETHER_WART, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::THICK, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_MUNDANE, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::GHAST_TEAR, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::GLISTERING_MELON, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::BLAZE_POWDER, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::MAGMA_CREAM, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::SUGAR, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::RABBIT_FOOT, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER, 1)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::THICK, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_WEAKNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::WEAKNESS, 1)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::REGENERATION, 1), ItemFactory::get(Item::GHAST_TEAR, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_REGENERATION, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::REGENERATION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_REGENERATION, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::REGENERATION, 1)));
		
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRENGTH, 1), ItemFactory::get(Item::BLAZE_POWDER, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_STRENGTH, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::STRENGTH, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_STRENGTH, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::STRENGTH, 1)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::POISON, 1), ItemFactory::get(Item::SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_POISON, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_POISON, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::HEALING, 1), ItemFactory::get(Item::GLISTERING_MELON, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_HEALING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::WATER_BREATHING, 1), ItemFactory::get(Item::PUFFERFISH, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_WATER_BREATHING, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER_BREATHING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::WATER_BREATHING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::HARMING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::STRONG_HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_POISON, 1)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::SWIFTNESS, 1), ItemFactory::get(Item::SUGAR, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_SWIFTNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_SWIFTNESS, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), ItemFactory::get(Item::MAGMA_CREAM, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LEAPING, 1), ItemFactory::get(Item::RABBIT_FOOT, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_LEAPING, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::STRONG_LEAPING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::LEAPING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::SLOWNESS, 1)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::NIGHT_VISION, 1), ItemFactory::get(Item::GOLDEN_CARROT, 0, 1), ItemFactory::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::INVISIBILITY, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::POTION, Potion::INVISIBILITY, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1)));
		//SPLASH_POTION
		//WATER
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1), ItemFactory::get(Item::NETHER_WART, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::THICK, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_MUNDANE, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::GHAST_TEAR, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::GLISTERING_MELON, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::BLAZE_POWDER, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::MAGMA_CREAM, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::SUGAR, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), ItemFactory::get(Item::RABBIT_FOOT, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::THICK, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_WEAKNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_WEAKNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::REGENERATION, 1), ItemFactory::get(Item::GHAST_TEAR, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_REGENERATION, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_REGENERATION, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRENGTH, 1), ItemFactory::get(Item::BLAZE_POWDER, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_STRENGTH, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_STRENGTH, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::POISON, 1), ItemFactory::get(Item::SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_POISON, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_POISON, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::HEALING, 1), ItemFactory::get(Item::GLISTERING_MELON, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_HEALING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1), ItemFactory::get(Item::PUFFERFISH, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_WATER_BREATHING, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::HARMING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_POISON, 1)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1), ItemFactory::get(Item::SUGAR, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_SWIFTNESS, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1), ItemFactory::get(Item::MAGMA_CREAM, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LEAPING, 1), ItemFactory::get(Item::RABBIT_FOOT, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::STRONG_LEAPING, 1), ItemFactory::get(Item::GLOWSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1), ItemFactory::get(Item::GOLDEN_CARROT, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), ItemFactory::get(Item::REDSTONE_DUST, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), ItemFactory::get(Item::FERMENTED_SPIDER_EYE, 0, 1), ItemFactory::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1)));
		
		$ref = new \ReflectionClass(Potion::class);
		/** @var int[] */
		$potions = array_diff_assoc($ref->getConstants(), $ref->getParentClass()->getConstants());
		foreach($potions as $potion){
			$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::SPLASH_POTION, $potion, 1), ItemFactory::get(Item::GUNPOWDER, 0, 1), ItemFactory::get(Item::POTION, $potion, 1)));
			$this->registerBrewingRecipe(new BrewingRecipe(ItemFactory::get(Item::LINGERING_POTION, $potion, 1), ItemFactory::get(Item::DRAGON_BREATH, 0, 1), ItemFactory::get(Item::SPLASH_POTION, $potion, 1)));
		}
	}

	/**
	 * @param BrewingRecipe $recipe
	 */
	public function registerBrewingRecipe(BrewingRecipe $recipe) : void{
		$input = $recipe->getInput();
		$potion = $recipe->getPotion();
		$this->brewingRecipes[$input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage())] = $recipe;
	}

	/**
	 * @param Item $input
	 * @param Item $potion
	 *
	 * @return BrewingRecipe|null
	 */
	public function matchBrewingRecipe(Item $input, Item $potion) : ?BrewingRecipe{
		$subscript = $input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage());
		if(isset($this->brewingRecipes[$subscript])){
			return $this->brewingRecipes[$subscript];
		}

		return null;
	}
	
}
