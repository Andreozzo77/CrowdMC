<?php

namespace CustomEnchants;

use CustomEnchants\Blocks\PiggyObsidian;
use CustomEnchants\Commands\CustomEnchantCommand;
use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Entities\MagicFireball;
use CustomEnchants\Entities\PiggyFireball;
use CustomEnchants\Entities\PiggyWitherSkull;
use CustomEnchants\Entities\PigProjectile;
use CustomEnchants\Entities\VolleyArrow;
use CustomEnchants\Tasks\AutoAimTask;
use CustomEnchants\Tasks\BleedingTask;
use CustomEnchants\Tasks\ChickenTask;
use CustomEnchants\Tasks\EffectTask;
use CustomEnchants\Tasks\FreezeTask;
use CustomEnchants\Tasks\FlameCircleTask;
use CustomEnchants\Tasks\ForcefieldTask;
use CustomEnchants\Tasks\HealthTask;
use CustomEnchants\Tasks\HellForgedTask;
use CustomEnchants\Tasks\JetpackTask;
use CustomEnchants\Tasks\MeditationTask;
use CustomEnchants\Tasks\ParachuteTask;
use CustomEnchants\Tasks\RadarTask;
use CustomEnchants\Tasks\TotemTask;
use CustomEnchants\Tasks\SizeTask;
use CustomEnchants\Tasks\SpiderTask;
use CustomEnchants\Tasks\EndermanTask;
use pocketmine\block\BlockFactory;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Hoe;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shears;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

/**
 * Class Main
 * @package CustomEnchants
 */
class Main extends PluginBase
{
    const MAX_LEVEL = 0;
    const NOT_COMPATIBLE = 1;
    const NOT_COMPATIBLE_WITH_OTHER_ENCHANT = 2;
    const MORE_THAN_ONE = 3;

    const ROMAN_CONVERSION_TABLE = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];

    const COLOR_CONVERSION_TABLE = [
        "BLACK" => TextFormat::BLACK,
        "DARK_BLUE" => TextFormat::DARK_BLUE,
        "DARK_GREEN" => TextFormat::DARK_GREEN,
        "DARK_AQUA" => TextFormat::DARK_AQUA,
        "DARK_RED" => TextFormat::DARK_RED,
        "DARK_PURPLE" => TextFormat::DARK_PURPLE,
        "GOLD" => TextFormat::GOLD,
        "GRAY" => TextFormat::GRAY,
        "DARK_GRAY" => TextFormat::DARK_GRAY,
        "BLUE" => TextFormat::BLUE,
        "GREEN" => TextFormat::GREEN,
        "AQUA" => TextFormat::AQUA,
        "RED" => TextFormat::RED,
        "LIGHT_PURPLE" => TextFormat::LIGHT_PURPLE,
        "YELLOW" => TextFormat::YELLOW,
        "WHITE" => TextFormat::WHITE
    ];

    const PIGGY_ENTITIES = [
        PiggyFireball::class,
        PigProjectile::class,
		MagicFireball::class,
        VolleyArrow::class,
        PiggyWitherSkull::class
    ];

	/** @var array */
    public $angeliccd;
    public $blindcd;
    public $berserkercd;
    public $bountyhuntercd;
    public $cloakingcd;
    public $cripplingstrikecd;
    public $cursecd;
    public $cursedcd;
    public $defensecd;
    public $doomedcd;
    public $drunkcd;
    public $endershiftcd;
    public $enlightedcd;
    public $frozencd;
    public $growcd;
    public $iceaspectcd;
	public $lifestealcd;
    public $implantscd;
    public $insomniacd;
    public $jetpackcd;
    public $gravitycd;
    public $hardenedcd;
    public $paralyzecd;
    public $poisoncd;
    public $poisonedcd;
    public $revulsioncd;
    public $shrinkcd;
    public $vampirecd;
    public $venomcd;
    public $viruscd;
    public $withercd;

	/** @var array */
    public $growremaining;
    public $jetpackDisabled;
    public $shrinkremaining;
    public $flyremaining;

	/** @var array */
    public $chickenTick;
    public $forcefieldParticleTick;
    public $gasParticleTick;
	public $hellfireTick;
    public $jetpackChargeTick;
    public $meditationTick;

	/** @var array */
    public $blockface;

	/** @var array */
	public $applesick;
    public $glowing;
	public $flamecircle;
    public $grew;
	public $overload;
    public $flying;
    public $hallucination;
	public $cobweb;
    public $implants;
    public $mined;
    public $moved;
    public $nofall;
    public $using;
    public $shrunk;
	
	/** @var array */
	public $cold = 10;
	public $bleed = 10;
	
	/** @var array */
	public $freeze = array();
	public $bleeding = array();

	/** @var bool */
    public $formsEnabled = false;

	/** @var bool */
    public static $lightningFlames = false;
    public static $blazeFlames = false;

	/** @var array */
    public $enchants = [
        //id => ["name", "slot", "trigger", "rarity", maxlevel", "description"]
        CustomEnchantsIds::AERIAL => ["Aerial", "Weapons", "Damage", "Common", 5, "Increases damage when jumping"],
		CustomEnchantsIds::ADHESIVE => ["Adhesive", "Chestplate", "Damage", "Rare", 1, "Gives you inmunity to bleeding"],
        CustomEnchantsIds::ARMORED => ["Armored", "Armor", "Damage", "Rare", 5, "Decreases damage from sword by 10%"],
        CustomEnchantsIds::ANGEL => ["Angel", "Armor", "Equip", "Common", 3, "Gives effect regeneration"],
        CustomEnchantsIds::ANGELIC => ["Angelic", "Armor", "Damage", "Uncommon", 5, "Heals you when taking damage"],
		CustomEnchantsIds::ANTITRAP => ["Anti Trap", "Chestplate", "Damage", "Rare", 1, "Gives you inmunity to Hallucination and Spits Web"],
        CustomEnchantsIds::ANTITOXIN => ["Antitoxin", "Helmets", "Effect", "Rare", 1, "Gives you inmunity to Poison"],
        CustomEnchantsIds::ANTIKNOCKBACK => ["Anti Knockback", "Armor", "Damage", "Rare", 1, "Reduces knockback by 25% per armor piece"],
        CustomEnchantsIds::AQUATIC => ["Aquatic", "Helmets", "Equip", "Common", 1, "Gives effect water breathing"],
        CustomEnchantsIds::AUTOAIM => ["Auto Aim", "Bow", "Held", "Mythic", 1, "Aim at nearest target"],
        CustomEnchantsIds::AUTOREPAIR => ["Autorepair", "Damageable", "Move", "Uncommon", 10, "Repair your items while you move"],
        CustomEnchantsIds::BACKSTAB => ["Backstab", "Weapons", "Damage", "Common", 5, "When hitting from behind, you deal more damage"],
        CustomEnchantsIds::BERSERKER => ["Berserker", "Armor", "Damaged", "Rare", 5, "Gives strength on low health"],
        CustomEnchantsIds::BLAZE => ["Blaze", "Bow", "Shoot", "Rare", 1, "Shoots fireballs"],
        CustomEnchantsIds::BLESSED => ["Blessed", "Weapons", "Damage", "Uncommon", 3, "+15% chance to remove bad effects"],
		CustomEnchantsIds::BLEEDING => ["Bleeding", "Weapons", "Damage", "Rare", 5, "+5% chance a level to bleed enemies (prevents regeneration for 10s)"],
        CustomEnchantsIds::BLIND => ["Blind", "Weapons", "Damage", "Common", 5, "Gives blindness to enemies"],
        CustomEnchantsIds::BOWLIFESTEAL => ["Bow Lifesteal", "Bow", "Damage", "Common", 5, "Heals when bowing enemies"],
        CustomEnchantsIds::BOWLIGHTNING => ["Bow Lightning", "Bow", "Damage", "Rare", 5, "+10% chance a level to strike enemies with lightning"],
        CustomEnchantsIds::BOUNTYHUNTER => ["Bounty Hunter", "Bow", "Damage", "Rare", 5, "Collect bounties (items) when hitting enemies"],
        CustomEnchantsIds::CHARGE => ["Charge", "Weapons", "Damage", "Common", 5, "Increases damage when sprinting"],
        CustomEnchantsIds::CLARITY => ["Clarity", "Helmets", "Effect", "Rare", 1, "Gives inmunity to Blindness"],
        CustomEnchantsIds::CLOAKING => ["Cloaking", "Armor", "Damaged", "Common", 5, "Makes you invisible when hit"],
		CustomEnchantsIds::CREEPERARMOR => ["Creeper Armor", "Leggings", "Damaged", "Rare", 3, "+20% chance a level for inmunity to explosions"],
        CustomEnchantsIds::CHICKEN => ["Chicken", "Chestplate", "Equip", "Uncommon", 5, "Lays egg every 5 minutes. +5% chance a level of rare drop"],
        CustomEnchantsIds::CRIPPLINGSTRIKE => ["Cripple", "Weapons", "Damage", "Uncommon", 5, "Gives nausea and slowness to enemies"],
        CustomEnchantsIds::CRIPPLE => ["Cripple", "Weapons", "Damage", "Uncommon", 5, "Gives nausea and slowness to enemies"],
        CustomEnchantsIds::CRITICAL => ["Critical", "Weapons", "Damage", "Rare", 5, "+10% chance a level to double damage"],
        CustomEnchantsIds::CORRUPT => ["Corrupt", "Weapons", "Damage", "Mythic", 5, "Corrupt some good effects from enemies"],
        CustomEnchantsIds::CURSE => ["Curse", "Weapons", "Damage", "Rare", 5, "Gives  slowness, weakness and mining fatigue to enemies"],
        CustomEnchantsIds::CURSED => ["Cursed", "Armor", "Damaged", "Uncommon", 5, "Gives wither to enemy when hit"],
		CustomEnchantsIds::DARKROOT => ["Dark Root", "Trident", "Damage", "Uncommon", 5, "Gives nausea and blindness to enemies"],
        CustomEnchantsIds::DEATHBRINGER => ["Deathbringer", "Sword", "Damage", "Rare", 6, "Increases the damage you take"],
        CustomEnchantsIds::DEFENSE => ["Defense", "Armor", "Damaged", "Rare", 5, "Gives resistance to damage and regeneration when low on health"],
		CustomEnchantsIds::DEMISE => ["Demise", "Weapons", "Damage", "Uncommon", 5, "Ignores armor when dealing damage and increase damage"],
		CustomEnchantsIds::DEMONFORGED => ["Demonforged", "Weapons", "Damage", "Common", 5, "+5% chance a level to damage enemy armor"],
        CustomEnchantsIds::DISARMING => ["Disarming", "Weapons", "Damage", "Uncommon", 10, "+3% chance a level to drop item held by enemy"],
        CustomEnchantsIds::DISARMOR => ["Disarmor", "Weapons", "Damage", "Rare", 10, "+2% chance a level to take off an enemy armor piece"],
		CustomEnchantsIds::DISARMPROTECTION => ["Disarm Protection", "Protection", "Damage", "Rare", 10, "Gives inmunity to disarming"],
		CustomEnchantsIds::DISARMORPROTECTION => ["Disarmor Protection", "Protection", "Damage", "Rare", 10, "Gives inmunity to disarmor"],
        CustomEnchantsIds::DIVINE => ["Divine", "Armor", "Damage", "Mythic", 3, "Decreases all damage from weapons by 20%"],
		CustomEnchantsIds::DOOMED => ["Doomed", "Armor", "Damaged", "Mythic", 3, "Gives 5 negative effects to enemy when hit"],
        CustomEnchantsIds::DRAIN => ["Drain", "Weapons", "Damage", "Rare", 10, "Drain money when taking damage from enemies"],
        CustomEnchantsIds::DRILLER => ["Driller", "Tools", "Break", "Uncommon", 5, "Breaks a 3 by 3 (+1 a level)"],
        CustomEnchantsIds::DRUNK => ["Drunk", "Armor", "Damaged", "Uncommon", 5, "Gives slowness, mining fatigue, and nausea to enemies when hit"],
        CustomEnchantsIds::ENDERSHIFT => ["Endershift", "Armor", "Damaged", "Rare", 5, "Gives speed and extra health when low on health"],
        CustomEnchantsIds::ENERGIZING => ["Energizing", "Tools", "Break", "Common", 5, "Gives haste when block is broken"],
        CustomEnchantsIds::ENLIGHTED => ["Enlighted", "Armor", "Damaged", "Uncommon", 5, "Gives regeneration when hit"],
        CustomEnchantsIds::ENRAGED => ["Enraged", "Bow", "Held", "Uncommon", 5, "Gives strength when hold"],
		CustomEnchantsIds::EVASION => ["Evasion", "Chestplate", "Damaged", "Uncommon", 7, "+10% chance a level to dodge damage"],
        CustomEnchantsIds::EXCALIBUR => ["Excalibur", "Sword", "Damage", "Mythic", 5, "Increases massive damage"],
        CustomEnchantsIds::FARMER => ["Farmer", "Hoe", "Break", "Uncommon", 1, "Automatically regrows crops when harvested"],
		//CustomEnchantsIds::FLAMECIRCLE => ["Flame Circle", "Chestplate", "Equip", "Mythic", 5, "Makes a flame circle around you. Gives strength and resistance"],
        CustomEnchantsIds::FERTILIZER => ["Fertilizer", "Hoe", "Interact", "Uncommon", 3, "Makes a farmland with higher radius a level"],
        CustomEnchantsIds::FOCUSED => ["Focused", "Helmets", "Effect", "Uncommon", 5, "Nausea will effect you less"],
        CustomEnchantsIds::FORCEFIELD => ["Forcefield", "Armor", "Equip", "Mythic", 5, "Deflects projectiles and living entities in a 0.75x (x = # of armor pieces)"],
		CustomEnchantsIds::FREEZE => ["Freeze", "Weapons", "Damage", "Rare", 5, "+2% chance a level to freeze enemies (cannot move in 15s)"],
        CustomEnchantsIds::FROZEN => ["Frozen", "Armor", "Damaged", "Common", 5, "Gives slowness to enemy when hit"],
        CustomEnchantsIds::GEARS => ["Gears", "Boots", "Equip", "Common", 3, "Gives speed effect"],
        CustomEnchantsIds::GLOWING => ["Glowing", "Helmets", "Equip", "Common", 1, "Gives night vision effect"],
        CustomEnchantsIds::GOOEY => ["Gooey", "Weapons", "Damage", "Uncommon", 5, "+5% chance a level to fling enemies into the air"],
        CustomEnchantsIds::GRAPPLING => ["Grappling", "Bow", "Projectile_Hit", "Rare", 1, "Pulls you to location of arrow. If enemy is hit, the enemy will be pulled to you."],
        CustomEnchantsIds::GRAVITY => ["Gravity", "Weapons", "Damage", "Rare", 5, "Gives levitation to enemies"],
        CustomEnchantsIds::GRIND => ["Grind", "Tools", "Break", "Rare", 15, "Earn more EXP when breaking blocks"],
        CustomEnchantsIds::GROW => ["Grow", "Armor", "Sneak", "Uncommon", 5, "Increases size on sneak (Must be wearing full set of Grow armor)"],
        CustomEnchantsIds::HALLUCINATION => ["Hallucination", "Weapons", "Damage", "Rare", 5, "+5% chance a level of trapping enemies in a fake prison"],
        CustomEnchantsIds::HASTE => ["Haste", "Tools", "Held", "Common", 5, "Gives haste when hold"],
        CustomEnchantsIds::HARVEST => ["Harvest", "Hoe", "Break", "Uncommon", 3, "Harvest crops in a level radius around the block"],
        CustomEnchantsIds::HARDENED => ["Hardened", "Armor", "Damaged", "Uncommon", 5, "Gives weakness to enemy when hit"],
        CustomEnchantsIds::HEADHUNTER => ["Headhunter", "Bow", "Damage", "Mythic", 5, "Increases damage if enemy is shot in the head"],
		CustomEnchantsIds::HEADLESS => ["Headless", "Weapons", "Killer", "Uncommon", 1, "Give you the head of enemy you killed"],
        CustomEnchantsIds::HEALING => ["Healing", "Bow", "Damage", "Common", 5, "Heals target when shot"],
        CustomEnchantsIds::HEAVY => ["Heavy", "Armor", "Damage", "Rare", 5, "Decreases damage from bow by 10%"],
		CustomEnchantsIds::HELLFORGED => ["Hellforged", "Weapons", "Held", "Mythic", 3, "+10% chance a level to fire enemy, increase damage and remove 2 effects"],
        CustomEnchantsIds::JETPACK => ["Jetpack", "Boots", "Sneak", "Rare", 3, "Enable flying (you fly where you look) when you sneak"],
		CustomEnchantsIds::KEYPLUS => ["Keyplus", "Tools", "Break", "Rare", 10, "Increase by +10% chance a level to find crate keys"],
		CustomEnchantsIds::KILLERMONEY => ["Killer Money", "Weapons", "Killer", "Rare", 10, "Earn \$5,000 money a level by killing enemies"],
        CustomEnchantsIds::ICEASPECT => ["Ice Aspect", "Weapons", "Damage", "Common", 5, "Gives slowness to enemies"],
		CustomEnchantsIds::INSANITY => ["Insanity", "Axe", "Damage", "Rare", 8, "Increase axe massive damage"],
        CustomEnchantsIds::INSOMNIA => ["Insomnia", "Bow", "Damage", "Uncommon", 5, "Gives nausea and slowness to enemies"],
        CustomEnchantsIds::IMPLANTS => ["Implants", "Helmets", "Move", "Rare", 5, "Replenishes hunger and air when walking"],
        CustomEnchantsIds::LIFESTEAL => ["Lifesteal", "Weapons", "Damage", "Common", 5, "Heals when damaging enemies"],
        CustomEnchantsIds::LONGBOW => ["Longbow", "Bow", "Damage", "Rare", 5, "Increase damage inflicted by bow"],
        CustomEnchantsIds::LUMBERJACK => ["Lumberjack", "Axe", "Break", "Uncommon", 1, "Mines all logs connected to log when broken"],
        CustomEnchantsIds::MAGMAWALKER => ["Magma Walker", "Boots", "Move", "Uncommon", 2, "Turns lava into magma around you"],
		CustomEnchantsIds::NATUREWRATH => ["Nature Wrath", "Leggings", "Damaged", "Mythic", 5, "+5% chance a level to freeze within radius, enemies cannot move in 10 sec. Gives slowness and mining fatigue to enemies"],
        CustomEnchantsIds::MEDITATION => ["Meditation", "Helmets", "Equip", "Uncommon", 5, "Replenish health and hunger every 20 seconds (half a hunger bar/heart per level)"],
        CustomEnchantsIds::MINERLUCK => ["Miner Luck", "Tools", "Break", "Rare", 10, "Increase relic chance when breaking blocks"],
        CustomEnchantsIds::MOLTEN => ["Molten", "Armor", "Damaged", "Uncommon", 5, "Sets enemy on fire when hit"],
        CustomEnchantsIds::MONEYFARM => ["Money Farm", "Tools", "Break", "Rare", 20, "Earn more money when breaking blocks"],
		CustomEnchantsIds::NAUTICA => ["Nautica", "Trident", "Damage", "Rare", 8, "Increase trident damage"],
		CustomEnchantsIds::NUTRITION => ["Nutrition", "Leggings", "Consume", "Common", 5, "Decrease apple cooldown"],
        CustomEnchantsIds::OBLITERATE => ["Obliterate", "Weapons", "Damage", "Uncommon", 3, "+2% chance a level to increase knockback"],
        CustomEnchantsIds::OBSIDIANSHIELD => ["Obsidian Shield", "Armor", "Equip", "Uncommon", 1, "Gives fire resistance while worn"],
        CustomEnchantsIds::OVERLOAD => ["Overload", "Armor", "Equip", "Rare", 24, "Gives 2 extra hearts per level per armor piece"],
        CustomEnchantsIds::PARACHUTE => ["Parachute", "Chestplate", "Equip", "Uncommon", 1, "Slows your fall (above 3 blocks)"],
        CustomEnchantsIds::PARALYZE => ["Paralyze", "Bow", "Damage", "Rare", 5, "Gives slowness, blindness and weakness to enemies"],
        CustomEnchantsIds::PIERCING => ["Piercing", "Bow", "Damage", "Rare", 5, "Ignores armor when dealing damage and increase damage"],
        CustomEnchantsIds::PLACEHOLDER => ["Placeholder", "Bow", "Shoot", "Rare", 1, ""],
        CustomEnchantsIds::POISON => ["Poison", "Weapons", "Damage", "Common", 5, "Gives enemies to poison"],
        CustomEnchantsIds::POISONED => ["Poisoned", "Armor", "Damaged", "Common", 5, "Gives poison to enemy when hit"],
        CustomEnchantsIds::PORKIFIED => ["Porkified", "Bow", "Shoot", "Mythic", 5, "Shoots pigs"],
        CustomEnchantsIds::QUICKENING => ["Quickening", "Tools", "Break", "Common", 5, "Gives speed when block is broken"],
        CustomEnchantsIds::RADAR => ["Radar", "Compass", "Inventory", "Uncommon", 5, "Points to nearest player in a 50 block range"],
        CustomEnchantsIds::RAGE => ["Rage", "Weapons", "Held", "Uncommon", 5, "Gives you strength when hold"],
        CustomEnchantsIds::REMEDY => ["Remedy", "Chestplate", "Effect", "Mythic", 1, "Gives you inmunity to all negative effects"],
        CustomEnchantsIds::REVIVE => ["Revive", "Armor", "Death", "Uncommon", 5, "Will revive you when you die (will lower/remove enchantment)"],
        CustomEnchantsIds::REVULSION => ["Revulsion", "Armor", "Damaged", "Uncommon", 5, "Gives nausea to enemy when hit"],
        CustomEnchantsIds::SASH => ["Sash", "Leggings", "Effect", "Rare", 1, "Gives inmunity to slowness"],
		CustomEnchantsIds::SKILLSWIPE => ["Skill Swipe", "Weapons", "Damage", "Rare", 10, "Steal some of your opponent's EXP every time you hit enemies"],
		CustomEnchantsIds::SHOCKWAVE => ["Shockwave", "Weapons", "Damage", "Mythic", 5, "+5% chance a level to damage in a radius"],
        CustomEnchantsIds::SHRINK => ["Shrink", "Armor", "Sneak", "Uncommon", 5, "Decreases size on sneak (Must be wearing full set of shrink armor)"],
        CustomEnchantsIds::SHUFFLE => ["Shuffle", "Bow", "Damage", "Rare", 5, "+5% chance a level to switch positions with target"],
        CustomEnchantsIds::SMELTING => ["Smelting", "Tools", "Break", "Common", 1, "Automatically smelts drops when block broken"],
        CustomEnchantsIds::SOULBOUND => ["Soulbound", "Global", "Death", "Rare", 5, "Keeps item after death (will lower/remove enchantment)"],
        CustomEnchantsIds::SPIDER => ["Spider", "Chestplate", "Equip", "Mythic", 1, "Climb walls"],
		CustomEnchantsIds::SPITSWEB => ["Spits Web", "Weapons", "Damage", "Uncommon", 5, "Spits webs to stop you in your tracks"],
        CustomEnchantsIds::SPRINGS => ["Springs", "Boots", "Equip", "Common", 3, "Gives a jump boost"],
        CustomEnchantsIds::SHILEDED => ["Shileded", "Armor", "Equip", "Uncommon", 5, "Gives damage resistance"],
        CustomEnchantsIds::STOMP => ["Stomp", "Boots", "Fall_Damage", "Uncommon", 5, "Deal part of fall damage to enemy when taking fall damage"],
        CustomEnchantsIds::TANK => ["Tank", "Armor", "Damage", "Rare", 5, "Decreases damage from axe by 10%"],
		CustomEnchantsIds::TREASUREHUNTER => ["Treasure Hunter", "Tools", "Break", "Rare", 10, "+10% chance a level to drop special loot"],
        CustomEnchantsIds::VAMPIRE => ["Vampire", "Weapons", "Damage", "Common", 1, "Absorbs part of damage dealt"],
        CustomEnchantsIds::VIRUS => ["Virus", "Bow", "Damage", "Uncommon", 4, "Gives poison and wither to enemies"],
        CustomEnchantsIds::VENOM => ["Venom", "Bow", "Damage", "Common", 5, "Gives poison to enemies"],
        CustomEnchantsIds::VITAMINS => ["Vitamins", "Chestplate", "Effect", "Rare", 1, "Gives inmunity to weakness"],
        CustomEnchantsIds::VOLLEY => ["Volley", "Bow", "Shoot", "Uncommon", 5, "Shoot multiple arrows in a cone"],
		CustomEnchantsIds::WARMER => ["Warmer", "Boots", "Damaged", "Rare", 1, "Gives inmunity to freeze"],
        CustomEnchantsIds::WITHER => ["Wither", "Weapons", "Damage", "Common", 5, "Gives wither to enemies"],
        CustomEnchantsIds::WITHERSKULL => ["Wither Skull", "Bow", "Shoot", "Mythic", 1, "Shoots an explosive Wither Skull"],
        
        CustomEnchantsIds::COMPRESS => ["Compress", "Weapons", "Death", "Mythic", 1, "Drops a road with murdered loot"],
        CustomEnchantsIds::LIGHTNING => ["Lightning", "Weapons", "Damage", "Mythic", 9, "+10% chance a level to strike enemies with lightning!"],
        CustomEnchantsIds::ENDERMAN => ["Enderman", "Chestplate", "Equip", "Mythic", 6, "Teleport to target by looking at them"],
        CustomEnchantsIds::FROSTWALKER => ["Frost Walker", "Boots", "Move", "Mythic", 2, "Turns water into ice as you walk"],
        CustomEnchantsIds::LOOTING => ["Looting", "Weapons", "Death", "Mythic", 3, "Cause mobs to drop more items"],
        CustomEnchantsIds::ACCURACY => ["Accuracy", "Weapons", "Damage", "Rare", 5, "The precise hit inflicts more damage"],
        CustomEnchantsIds::FIREASPECT => ["Fire Aspect", "Weapons", "Damage", "Rare", 2, "Sets the target on fire"],
        CustomEnchantsIds::JACKHAMMER => ["Jackhammer", "Tools", "Break", "Rare", 5, "Breaks a row of blocks"],
        CustomEnchantsIds::ROCKET => ["Rocket", "Weapons", "Damage", "Rare", 2, "Causes player to levitate"],
        CustomEnchantsIds::ANTIGRAVITY => ["Anti Gravity", "Weapons", "Damage", "Rare", 2, "Causes player to sink into the ground"],
        CustomEnchantsIds::INVULNERABILITY => ["Invulnerability", "Weapons", "Damage", "Rare", 3, "5% chance to become invincible for 3 or more seconds when hit"],
        CustomEnchantsIds::SAVIOR => ["Savior", "Weapons", "Damage", "Rare", 3, "0.5% chance to teleport far away when hit"],
        CustomEnchantsIds::GHOST => ["Ghost", "Helmets", "Equip", "Mythic", 1, "Hides you from being seen in /near"],
        CustomEnchantsIds::INQUISITIVE => ["Inquisitive", "Weapons", "Damage", "Rare", 3, "Gives more EXP when slaying mobs"],
        CustomEnchantsIds::BLOODLUST => ["Blood Lust", "Chestplate", "Damage", "Mythic", 2, "+5% chance a level to regain health enemy is bleeding"],
        CustomEnchantsIds::HEX => ["Hex", "Weapons", "Damage", "Mythic", 3, "+3% chance a level to reflect outgoing damage, 3 seconds per level"],
        CustomEnchantsIds::COWLAUNCHER => ["Cow Launcher", "Bow", "Shoot", "Mythic", 3, "Shoots an explosive cow"],
        CustomEnchantsIds::DOUBLESTRIKE => ["Double Strike", "Weapons", "Damage", "Mythic", 3, "+2.5% chance a level to attack twice in one swing"],
        CustomEnchantsIds::TRICKSTER => ["Trickster", "Weapons", "Damage", "Mythic", 2, "+2.5% chance a level to teleport directly behind your opponent"],
        CustomEnchantsIds::BLOODING => ["Blooding", "Weapons", "Damage", "Rare", 3, "Causes the enemy to blood"],
        CustomEnchantsIds::TOKENMASTER => ["Token Master", "Tools", "Break", "Mythic", 5, "Increase tokens you get from mining"],
        CustomEnchantsIds::KENYSFORTUNE => ["Kenys Fortune", "Tools", "Break", "Mythic", 10, "Chance to multiply tokens you find 1% per level. Requires Token Master to process."],
        CustomEnchantsIds::MARTYRDOM => ["Martyrdom", "Armor", "Death", "Mythic", 3, "Makes an explosion when player dies"],
        CustomEnchantsIds::THIEF => ["Thief", "Weapons", "Damage", "Mythic", 2, "+0.3% chance a level to take a random item from enemy"],
        CustomEnchantsIds::ANTITHEFT => ["Antitheft", "Weapons", "Equip", "Rare", 4, "Reduce rob chance by 25% a level"],
        CustomEnchantsIds::BLOODCURDLE => ["Blood Curdle", "Weapons", "Damage", "Mythic", 3, "+0.5% chance to drop blood when you hit an enemy"],
        CustomEnchantsIds::FRENZY => ["Frenzy", "Weapons", "Death", "Mythic", 1, "Gives you regeneration and resistance when you kill an enemy"],
        CustomEnchantsIds::FROSTBITE => ["Frostbite", "Weapons", "Damage", "Mythic", 10, "+10% chance a level to disable warmer immunity"],
        CustomEnchantsIds::EXPLOSIVE => ["Explosive", "Tools", "Break", "Mythic", 15, "Cause an explosion when block is broken"],
        CustomEnchantsIds::VACUUM => ["Vacuum", "Weapons", "Damage", "Rare", 4, "Automatically put the mobs drops in your inventory"],
        CustomEnchantsIds::TORNADO => ["Tornado", "Weapons", "Damage", "Mythic", 3, "+0.1% chance a level to make your enemy spin for 5 seconds"],
        CustomEnchantsIds::SOLARPOWDERED => ["Solar Powdered", "Armor", "Equip", "Mythic", 3, "Gives you regeneration and strength in the day time"],
        CustomEnchantsIds::NIGHTOWL => ["Night Owl", "Armor", "Equip", "Mythic", 3, "Gives you speed and resistance in the night time"],
        CustomEnchantsIds::PENETRATING => ["Penetrating", "Armor", "Equip", "Mythic", 10, "Disables Remedy inmunity by +10% a level"],
        CustomEnchantsIds::WHIRL => ["Whirl", "Armor", "Equip", "Mythic", 5, "Gives you inmunity to Tornado by +20% a level"],
        CustomEnchantsIds::BOMBYPROTECTION => ["Bomby Protection", "Armor", "Equip", "Mythic", 1, "Gives you immunity to Bomby"],
        CustomEnchantsIds::AUTOSTACK => ["Auto Stack", "Tools", "Equip", "Mythic", 1, "Automatically stack all your money notes and experience bottles"],
		CustomEnchantsIds::KILLCOUNTER => ["Kill Counter", "Weapons", "Death", "Mythic", 1, "Display the amount of kills in your item"],
		CustomEnchantsIds::KABOOM => ["Kaboom", "Weapons", "Damage", "Mythic", 3, "Knockbacks your opponent, makes an explosion and makes a beautiful effect"],
		CustomEnchantsIds::UPLIFT => ["Uplift", "Weapons", "Damage", "Rare", 1, "Knockbacks your opponent"],
		CustomEnchantsIds::HADES => ["Hades", "Weapons", "Damage", "Uncommon", 2, "Scorch your opponent, deal more damage and make a beautiful effect"],
		CustomEnchantsIds::DAZE => ["Daze", "Weapons", "Damage", "Uncommon", 3, "Makes your opponent dazed"],
		CustomEnchantsIds::FROST => ["Frost", "Weapons", "Damage", "Uncommon", 3, "Makes your opponent slower"],
		CustomEnchantsIds::OOF => ["OOF", "Weapons", "Damage", "Common", 2, "OOF"],
		CustomEnchantsIds::BUNNY => ["Bunny", "Armor", "Equip", "Rare", 2, "Jump like a bunny"],
		CustomEnchantsIds::OVERLORD => ["Overlord", "Armor", "Equip", "Mythic", 2, "Gives 2 extra hearts per level per armor piece"],
		CustomEnchantsIds::RELOCATE => ["Relocate", "Bow", "Shoot", "Rare", 1, "Teleports you to the position of the arrow"],
		CustomEnchantsIds::SCORCH => ["Scorch", "Armor", "Equip", "Common", 5, "Scorch your opponent when attacked"],
		CustomEnchantsIds::ADRENALINE => ["Adrenaline", "Armor", "Equip", "Uncommon", 1, "Gives you a boost when you are low on health"],
		CustomEnchantsIds::FEED => ["Feed", "Tools", "Break", "Uncommon", 1, "Feeds you when you break blocks"]
    ];

    public $incompatibilities = [
        CustomEnchantsIds::EXPLOSIVE => [CustomEnchantsIds::DRILLER, CustomEnchantsIds::LUMBERJACK, CustomEnchantsIds::JACKHAMMER],
		CustomEnchantsIds::DRILLER => [CustomEnchantsIds::LUMBERJACK, CustomEnchantsIds::JACKHAMMER, CustomEnchantsIds::EXPLOSIVE],
        CustomEnchantsIds::JACKHAMMER => [CustomEnchantsIds::DRILLER, CustomEnchantsIds::EXPLOSIVE, CustomEnchantsIds::LUMBERJACK],
        CustomEnchantsIds::DEATHBRINGER => [CustomEnchantsIds::EXCALIBUR, CustomEnchantsIds::INSANITY],
        CustomEnchantsIds::ARMORED => [CustomEnchantsIds::DIVINE],
        CustomEnchantsIds::HEAVY => [CustomEnchantsIds::DIVINE],
        CustomEnchantsIds::TANK => [CustomEnchantsIds::DIVINE],
		CustomEnchantsIds::SPITSWEB => [CustomEnchantsIds::HALLUCINATION],
		CustomEnchantsIds::DEMISE => [CustomEnchantsIds::BACKSTAB, CustomEnchantsIds::CHARGE, CustomEnchantsIds::AERIAL],
		CustomEnchantsIds::FLAMECIRCLE => [CustomEnchantsIds::FORCEFIELD],
        CustomEnchantsIds::DRUNK => [CustomEnchantsIds::DOOMED],
        CustomEnchantsIds::PORKIFIED => [CustomEnchantsIds::BLAZE, CustomEnchantsIds::WITHERSKULL],
        CustomEnchantsIds::GROW => [CustomEnchantsIds::SHRINK, CustomEnchantsIds::JETPACK],
        CustomEnchantsIds::VOLLEY => [CustomEnchantsIds::GRAPPLING],
    ];
    
    public function onLoad(){
    	if (!ItemFactory::isRegistered(Item::ENCHANTED_BOOK)) { //Check if it isn't already registered by another plugin
    	    ItemFactory::registerItem(new Item(Item::ENCHANTED_BOOK, 0, "Enchanted Book")); //This is a temporary fix for name being Unknown when given due to no implementation in PMMP. Will remove when implemented in PMMP
    	}
    	$this->initCustomEnchants();
    }

    public function onEnable()
    {
    	$this->formsEnabled = true;
        if ($this->getConfig()->getNested("blaze.flames")) {
            self::$blazeFlames = true;
        }
        if ($this->getConfig()->getNested("lightning.flames")) {
            self::$lightningFlames = true;
        }
        $this->jetpackDisabled = $this->getConfig()->getNested("jetpack.disabled") ?? [];
        BlockFactory::registerBlock(new PiggyObsidian(), true);
        foreach (self::PIGGY_ENTITIES as $piggyEntity) {
            Entity::registerEntity($piggyEntity, true);
        }

        $this->getServer()->getCommandMap()->register("customenchant", new CustomEnchantCommand("customenchant", $this));
        $this->getScheduler()->scheduleRepeatingTask(new AutoAimTask($this), 1);
		$this->getScheduler()->scheduleRepeatingTask(new BleedingTask($this, $this->bleed), 20);
        $this->getScheduler()->scheduleRepeatingTask(new ChickenTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new FreezeTask($this, $this->cold), 20);
		$this->getScheduler()->scheduleRepeatingTask(new FlameCircleTask($this), 15);
        $this->getScheduler()->scheduleRepeatingTask(new ForcefieldTask($this), 10);
        $this->getScheduler()->scheduleRepeatingTask(new EffectTask($this), 3);
        $this->getScheduler()->scheduleRepeatingTask(new JetpackTask($this), 1);
		$this->getScheduler()->scheduleRepeatingTask(new HealthTask($this), 10);
		$this->getScheduler()->scheduleRepeatingTask(new HellForgedTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new MeditationTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new ParachuteTask($this), 2);
        $this->getScheduler()->scheduleRepeatingTask(new RadarTask($this), 10);
        $this->getScheduler()->scheduleRepeatingTask(new SizeTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new SpiderTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new TotemTask($this), 20);
		
		$this->getScheduler()->scheduleRepeatingTask(new EndermanTask($this), 2);
		
        $this->getServer()->getPluginManager()->registerEvents(new CustomListener($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        
        ItemFactory::registerItem(new Bow(), true);
        Entity::registerEntity(CowArrow::class, false, ['CowArrow']);
    }

    public function initCustomEnchants()
    {
        CustomEnchants::init();
        foreach ($this->enchants as $id => $data) {
            $ce = $this->translateDataToCE($id, $data);
            CustomEnchants::registerEnchantment($ce);
        }
    }
	
	/**
	 * Checks if the item reached the enchant limit.
	 *
	 * @param Player $target
	 * @param string $enchantment
	 *
	 * @return bool
	 */
	public function canEnchant(Player $target, string $enchantment) : bool{
		if($target->hasPermission("customenchants.bypassenchantlimit")){
			return true;
		}
		$item = $target->getInventory()->getItemInHand();
		$less = 0;
		foreach($item->getEnchantments() as $enchant){
			if($enchant->getId() === CustomEnchants::getEnchantmentByName($enchantment)->getId()){
				$less = 1;
			}
		}
		$enchantLimit = 20;
		if(count($item->getEnchantments()) - $less >= $enchantLimit){
			$target->sendMessage(TextFormat::RED . "You have reached the maximum amount of enchants you can add to this item.");
			return false;
		}
		return true;
	}

    /**
     * Registers enchantment from id, name, trigger, rarity, and max level
     *
     * @param $id
     * @param $name
     * @param $type
     * @param $trigger
     * @param $rarity
     * @param $maxlevel
     * @param $description
     */
    public function registerEnchantment($id, $name, $type, $trigger, $rarity, $maxlevel, $description = "")
    {
        $data = [$name, $type, $trigger, $rarity, $maxlevel, $description];
        $this->enchants[$id] = $data;
        $ce = $this->translateDataToCE($id, $data);
        CustomEnchants::registerEnchantment($ce);
    }

    /**
     * Unregisters enchantment by id
     *
     * @param $id
     * @return bool
     */
    public function unregisterEnchantment($id)
    {
        if (isset($this->enchants[$id]) && CustomEnchants::getEnchantment($id) !== null) {
            unset($this->enchants[$id]);
            CustomEnchants::unregisterEnchantment($id);
            return true;
        }
        return false;
    }

    /**
     * Add an enchant incompatibility
     *
     * @param int $id
     * @param array $incompatibilities
     * @return bool
     */
    public function addIncompatibility(int $id, array $incompatibilities)
    {
        if (!isset($this->incompatibilities[$id])) {
            $this->incompatibilities[$id] = $incompatibilities;
            return true;
        }
        return false;
    }

    /**
     * Translates data from strings to int
     *
     * @param $id
     * @param $data
     * @return CustomEnchants
     */
    public function translateDataToCE($id, $data)
    {
        $slot = CustomEnchants::SLOT_NONE;
        switch ($data[1]) {
            case "Global":
                $slot = CustomEnchants::SLOT_ALL;
                break;
            case "Weapons":
                $slot = CustomEnchants::SLOT_SWORD;
                break;
            case "Bow":
                $slot = CustomEnchants::SLOT_BOW;
                break;
            case "Tools":
                $slot = CustomEnchants::SLOT_TOOL;
                break;
            case "Pickaxe":
                $slot = CustomEnchants::SLOT_PICKAXE;
                break;
            case "Axe":
                $slot = CustomEnchants::SLOT_AXE;
                break;
            case "Shovel":
                $slot = CustomEnchants::SLOT_SHOVEL;
                break;
            case "Hoe":
                $slot = CustomEnchants::SLOT_HOE;
                break;
            case "Armor":
                $slot = CustomEnchants::SLOT_ARMOR;
                break;
            case "Helmets":
                $slot = CustomEnchants::SLOT_HEAD;
                break;
            case "Chestplate":
                $slot = CustomEnchants::SLOT_TORSO;
                break;
            case "Leggings":
                $slot = CustomEnchants::SLOT_LEGS;
                break;
            case "Boots":
                $slot = CustomEnchants::SLOT_FEET;
                break;
            case "Compass":
                $slot = 0b10000000000000;
                break;
        }
        $rarity = CustomEnchants::RARITY_COMMON;
        switch ($data[3]) {
            case "Common":
                $rarity = CustomEnchants::RARITY_COMMON;
                break;
            case "Uncommon":
                $rarity = CustomEnchants::RARITY_UNCOMMON;
                break;
            case "Rare":
                $rarity = CustomEnchants::RARITY_RARE;
                break;
            case "Mythic":
                $rarity = CustomEnchants::RARITY_MYTHIC;
                break;
        }
        $ce = new CustomEnchants($id, $data[0], $rarity, $slot, CustomEnchants::SLOT_NONE, $data[4]);
        return $ce;
    }
    
    /**
     * @see Main::reduceEnchantLevel()
     */
    public function reduceEnchantmentLevel(Item &$item, int $enchant, int $level){
    	$this->reduceEnchantLevel($item, $enchant, $level);
    }
    /**
     * Reduce the level of the item's applied $enchant by $levels
     *
     * @param Item &$item
     * @param int|Enchantment $enchant
     * @param int $levels
     *
     * @return bool
     */
    public function reduceEnchantLevel(Item &$item, $enchant, int $levels = 1) : bool{
    	if(is_int($enchant)){
    		$enchantment = $item->getEnchantment($enchant);
    	}elseif($enchant instanceof Enchantment){
    		$enchantment = $enchant;
    	}else{
    		return false;
    	}
    	if($enchantment !== null){
    		$finalLevel = $enchantment->getLevel() - $levels;
    		if($finalLevel < 1){
    			$item = $this->removeEnchantment($item, $enchant);
    		}else{
    			$item = $this->addEnchantment($item, $enchant, $finalLevel);
    		}
    		return true;
    	}
    	return false;
    }
    
    
    /**
     * Refresh the description of an item regarding enchants.
     *
     * @param Item &$item
     */
    public function updateItemDescription(Item &$item) : void{
        if(empty(count($item->getEnchantments()))){
        	$itemname = TextFormat::RESET . TextFormat::WHITE . TextFormat::clean(explode(TextFormat::EOL, $item->getName())[0]);
        }else{
        	$itemname = TextFormat::RESET . TextFormat::RED . explode(TextFormat::EOL, $item->getName())[0];
        	
        	$enchantments = $item->getEnchantments();
        	usort($enchantments, function(EnchantmentInstance $instanceA, EnchantmentInstance $instanceB) : int{
        		return $instanceA->getType()->getRarity() < $instanceB->getType()->getRarity() ? -1 : 1;
        	});
        	foreach($enchantments as $instance){
        		
        		$enchantment = $instance->getType();
        	
        	    if(CustomEnchants::getEnchantment($enchantment->getId()) instanceof CustomEnchants){
        	    	if($item->getId() === Item::ENCHANTED_BOOK){
        	    		
        	    		$item->setLore([
        	    		    TextFormat::RESET . TextFormat::YELLOW . str_replace("%", "%%", wordwrap($this->enchants[$enchantment->getId()][5], 30)),
        	    		    TextFormat::RESET . TextFormat::AQUA . $this->enchants[$enchantment->getId()][1] . " Enchantment",
        	    		    TextFormat::RESET . TextFormat::GRAY . "Combine with an item to enchant"
        	    		]);
        	    	}
        	    		    
        			
        	    	$itemname .= TextFormat::EOL . $this->getRarityColor($enchantment->getRarity()) . $enchantment->getName() . " " . $this->getRomanNumber($instance->getLevel());
        	    }else{
        	    	//Vanilla
        	    }
        	}
        }
        $item->setCustomName($itemname);
    }

    /**
     * Adds enchantment to item
     *
     * @param Item $item
     * @param $enchants
     * @param $levels
     * @param bool $check
     * @param CommandSender|null $sender
     * @return Item
     */
    public function addEnchantment(Item $item, $enchants, $levels, $check = true, CommandSender $sender = null)
    {
        if (!is_array($enchants)) {
            $enchants = [$enchants];
        }
        if (!is_array($levels)) {
            $levels = [$levels];
        }
        if (count($enchants) > count($levels)) {
            for ($i = 0; $i <= count($enchants) - count($levels); $i++) {
                $levels[] = 1;
            }
        }
        $combined = array_combine($enchants, $levels);
        foreach ($enchants as $enchant) {
            $level = $combined[$enchant];
            if (!$enchant instanceof CustomEnchants) {
                if (is_numeric($enchant)) {
                    $enchant = CustomEnchants::getEnchantment((int)$enchant);
                } else {
                    $enchant = CustomEnchants::getEnchantmentByName($enchant);
                }
            }
            if ($enchant == null) {
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::RED . "Invalid enchantment.");
                }
                continue;
            }
            $result = $this->canBeEnchanted($item, $enchant, $level);
            if ($result === true || $check !== true) {
                if ($item->getId() == Item::BOOK) {
                    $item = Item::get(Item::ENCHANTED_BOOK, $level);
                }
                $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
                $found = false;
                if (!($ench instanceof ListTag)) {
                    $ench = new ListTag(Item::TAG_ENCH, [], NBT::TAG_Compound);
                } else {
                    foreach ($ench as $k => $entry) {
                        if ($entry->getShort("id") === $enchant->getId()) {
                            $ench->set($k, new CompoundTag("", [
                                new ShortTag("id", $enchant->getId()),
                                new ShortTag("lvl", $level)
                            ]));
                            //$item->setCustomName(str_replace($this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry["lvl"]), $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level), $item->getName()));
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $ench->push(new CompoundTag("", [
                        new ShortTag("id", $enchant->getId()),
                        new ShortTag("lvl", $level)
                    ]));
                    //$item->setCustomName(TextFormat::RESET . TextFormat::RED . $item->getName() . "\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level));
                }
                $item->setNamedTagEntry($ench);
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::GREEN . "Enchanting succeeded.");
                }
                continue;
            }
            if ($sender !== null) {
                switch ($result) {
                    case self::NOT_COMPATIBLE:
                        $sender->sendMessage(TextFormat::RED . "The item is not compatible with this enchant.");
                        break;
                    case self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT:
                        $sender->sendMessage(TextFormat::RED . "The enchant is not compatible with another enchant.");
                        break;
                    case self::MAX_LEVEL:
                        $sender->sendMessage(TextFormat::RED . "The max level is " . $this->getEnchantMaxLevel($enchant) . ".");
                        break;

                    case self::MORE_THAN_ONE:
                        $sender->sendMessage(TextFormat::RED . "You can only enchant one item at a time.");
                        break;
                }
            }
            continue;
        }
        $this->updateItemDescription($item);
        return $item;
    }

    /**
     * Removes enchantment from item
     *
     * @param Item $item
     * @param $enchant
     * @param int $level
     * @return bool|Item
     */
    public function removeEnchantment(Item $item, $enchant, $level = -1)
    {
        if (!$item->hasEnchantments()) {
            return false;
        }
        if(is_int($enchant)){
        	$id = $enchant;
        }
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        }
        if($enchant instanceof Enchantment){
        	$id = $enchant->getId();
        }
        $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
        if (!($ench instanceof ListTag)) {
            return false;
        }
        foreach ($ench as $k => $entry) {
            if ($entry->getShort("id") === $id and ($level === -1 or $entry->getShort("lvl") === $level)) {
                $ench->remove($k);
                /*$item->setCustomName(str_replace("\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry->getShort("lvl")), "", $item->getCustomName()));*/
                break;
            }
        }
    
        $item->setNamedTagEntry($ench);
        $this->updateItemDescription($item);
        return $item;
    }

    /**
     * Returns enchantment type
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantType(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[1];
            }
        }
        return "Unknown";
    }

    /**
     * Returns rarity of enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantRarity(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[3];
            }
        }
        return "Common";
    }

    /**
     * Returns the max level the enchantment can have
     *
     * @param CustomEnchants $enchant
     * @return int
     */
    public function getEnchantMaxLevel(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[4];
            }
        }
        return 5;
    }

    /**
     * Returns the description of the enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantDescription(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[5];
            }
        }
        return "Unknown";
    }

    /**
     * Sorts enchantments by type.
     *
     * @return array
     */
    public function sortEnchants()
    {
        $sorted = [];
        foreach ($this->enchants as $id => $data) {
            $type = $data[1];
            if (!isset($sorted[$type])) {
                $sorted[$type] = [$data[0]];
            } else {
                array_push($sorted[$type], $data[0]);
            }
        }
        return $sorted;
    }

    /**
     * Returns roman numeral of a number
     *
     * @param $integer
     * @return string
     */
    public function getRomanNumber($integer) //Thank you @Muqsit!
    {
        $romanString = "";
        while ($integer > 0) {
            foreach (self::ROMAN_CONVERSION_TABLE as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }

    /**
     * Returns the color of a rarity
     *
     * @param $rarity
     * @return string
     */
    public function getRarityColor($rarity)
    {
        switch ($rarity) {
            case CustomEnchants::RARITY_COMMON:
                $color = mb_strtoupper($this->getConfig()->getNested("color.common"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::AQUA : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_UNCOMMON:
                $color = mb_strtoupper($this->getConfig()->getNested("color.uncommon"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::YELLOW : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_RARE:
                $color = mb_strtoupper($this->getConfig()->getNested("color.rare"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::GOLD : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_MYTHIC:
                $color = mb_strtoupper($this->getConfig()->getNested("color.mythic"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::RED : $this->translateColorNameToTextFormat($color);
            default:
                return TextFormat::GRAY;
        }
    }

    /**
     * Translates color name to TextFormat constant
     *
     * @param $color
     * @return bool|mixed
     */
    public function translateColorNameToTextFormat($color)
    {
        foreach (self::COLOR_CONVERSION_TABLE as $name => $textformat) {
            if ($color == $name) {
                return $textformat;
            }
        }
        return false;
    }

    /**
     * Checks if an item can be enchanted with a specific enchantment and level
     *
     * @param Item $item
     * @param $enchant
     * @param $level
     * @return int|bool
     */
    public function canBeEnchanted(Item $item, $enchant, $level)
    {
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        } elseif ($enchant instanceof CustomEnchants !== true) {
            $this->getLogger()->error("Argument must be an instance EnchantmentInstance or CustomEnchants.");
            return false;
        }
        $type = $this->getEnchantType($enchant);
        if ($this->getEnchantMaxLevel($enchant) < $level) {
            return self::MAX_LEVEL;
        }
        foreach ($this->incompatibilities as $enchantment => $incompatibilities) {
            if ($item->getEnchantment($enchantment) !== null) {
                if (in_array($enchant->getId(), $incompatibilities)) {
                    return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                }
            } else {
                foreach ($incompatibilities as $incompatibility) {
                    if ($item->getEnchantment($incompatibility) !== null) {
                        if ($enchantment == $enchant->getId() || in_array($enchant->getId(), $incompatibilities)) {
                            return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                        }
                    }
                }
            }
        }
        if ($item->getCount() > 1) {
            return self::MORE_THAN_ONE;
        }
        if ($item->getId() == Item::BOOK) {
            return true;
        }
        switch ($type) {
            case "Global":
			    if ($item instanceof Durable || $item->getId() == Item::ELYTRA || $item->getId() == Item::TRIDENT || $item->getId() == Item::BLAZE_ROD) {
			        return true;
				}
			case "Protection":
		     	if ($item instanceof Sword || $item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shovel || $item instanceof Hoe || $item instanceof Shears || $item->getId() == Item::BOW || $item->getId() == Item::TRIDENT) {
                    return true;
				}
				break;
            case "Damageable":
                if ($item instanceof Durable) {
                    return true;
                }
                break;
            case "Weapons":
                if ($item instanceof Sword || $item instanceof Axe) {
                    return true;
                }
                break;
            case "Bow":
                if ($item->getId() == Item::BOW) {
                    return true;
                }
                break;
            case "Tools":
                if ($item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shovel || $item instanceof Hoe || $item instanceof Shears) {
                    return true;
                }
                break;
            case "Pickaxe":
                if ($item instanceof Pickaxe) {
                    return true;
                }
                break;
            case "Axe":
                if ($item instanceof Axe) {
                    return true;
                }
                break;
            case "Shovel":
                if ($item instanceof Shovel) {
                    return true;
                }
                break;
			case "Sword":
                if ($item instanceof Sword) {
                    return true;
                }
                break;
            case "Hoe":
                if ($item instanceof Hoe) {
                    return true;
                }
                break;
            case "Armor":
                if ($item instanceof Armor || $item->getId() == Item::ELYTRA) {
                    return true;
                }
                break;
            case "Helmets":
                switch ($item->getId()) {
                    case Item::LEATHER_CAP:
                    case Item::CHAIN_HELMET:
                    case Item::IRON_HELMET:
                    case Item::GOLD_HELMET:
                    case Item::DIAMOND_HELMET:
					//case Item::NETHERITE_HELMET:
                    case Item::SKULL:
                        return true;
                }
                break;
            case "Chestplate":
                switch ($item->getId()) {
                    case Item::LEATHER_TUNIC:
                    case Item::CHAIN_CHESTPLATE;
                    case Item::IRON_CHESTPLATE:
                    case Item::GOLD_CHESTPLATE:
                    case Item::DIAMOND_CHESTPLATE:
					//case Item::NETHERITE_CHESTPLATE:
                    case Item::ELYTRA:
                        return true;
                }
                break;
            case "Leggings":
                switch ($item->getId()) {
                    case Item::LEATHER_PANTS:
                    case Item::CHAIN_LEGGINGS:
                    case Item::IRON_LEGGINGS:
                    case Item::GOLD_LEGGINGS:
                    case Item::DIAMOND_LEGGINGS:
					//case Item::NETHERITE_LEGGINGS:
                        return true;
                }
                break;
            case "Boots":
                switch ($item->getId()) {
                    case Item::LEATHER_BOOTS:
                    case Item::CHAIN_BOOTS:
                    case Item::IRON_BOOTS:
                    case Item::GOLD_BOOTS:
                    case Item::DIAMOND_BOOTS:
					//case Item::NETHERITE_BOOTS:
                        return true;
                }
                break;
            case "Compass":
                if ($item->getId() == Item::COMPASS) {
                    return true;
                }
                break;
			case "Wand":
                if ($item->getId() == Item::BLAZE_ROD) {
                    return true;
                }
                break;
			case "Trident":
                if ($item->getId() == Item::TRIDENT) {
                    return true;
                }
                break;
            case "Snowball":
                if ($item->getId() == Item::SNOWBALL) {
                    return true;
                }
                break;
        }
        return self::NOT_COMPATIBLE;
    }

    /**
     * Checks for a certain block under a position
     *
     * @param Position $pos
     * @param $ids
     * @param $deep
     * @return bool
     * @internal param $id
     */
    public function checkBlocks(Position $pos, $ids, $deep = 0)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        if ($deep == 0) {
            $block = $pos->getLevel()->getBlock($pos);
            if (!in_array($block->getId(), $ids)) {
                return false;
            }
        } else {
            for ($i = 0; $deep < 0 ? $i >= $deep : $i <= $deep; $deep < 0 ? $i-- : $i++) {
                $block = $pos->getLevel()->getBlock($pos->subtract(0, $i));
                if (!in_array($block->getId(), $ids)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Position $position
     * @param int $range
     * @param string $type
     * @param Player|null $player
     * @return null|Entity
     */
    public function findNearestEntity(Position $position, int $range = 50, string $type = Player::class, Player $player = null)
    {
        assert(is_a($type, Entity::class, true));
        $nearestEntity = null;
        $nearestEntityDistance = $range;
        foreach ($position->getLevel()->getEntities() as $entity) {
            $distance = $position->distance($entity);
            if ($distance <= $range && $distance < $nearestEntityDistance && $entity instanceof $type && $player !== $entity && $entity->isAlive() && $entity->isClosed() !== true && $entity->isFlaggedForDespawn() !== true) {
                $nearestEntity = $entity;
                $nearestEntityDistance = $distance;
            }
        }
        return $nearestEntity;
    }
}
