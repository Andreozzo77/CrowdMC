<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

use LegacyCore\Commands\Gift;
use kenygamer\Core\Main;

/**
 * Getter methods for ItemUtils
 *
 * @method static Item[] getMonthlyLootbox()
 * @method static Item getHealingCookie
 * @method static Item getHestiaCrate()
 * @method static Item getAtlasCrate()
 * @method static Item getBankNote(int $money)
 * @method static Item getMythicNote(int $min, int $max = 0)
 * @method static Item getExperienceBottle(int $exp)
 * @method static Item getExperienceBottle2(int $min, int $max = 0)
 * @method static Item getTokenNote(int $tokens)
 * @method static Item getLuckyBlock()
 * @method static Item getEnchantDust()
 * @method static Item getHolyScroll()
 * @method static Item getWhiteScroll()
 * @method static Item getCommonBook(int $successRate = -1)
 * @method static Item getUncommonBook(int $successRate = -1)
 * @method static Item getRareBook(int $successRate = -1)
 * @method static Item getMythicBook(int $successRate = -1)
 * @method static Item getDiamondApple()
 * @method static Item getEnchantedDiamondApple()
 * @method static Item getCommonKey()
 * @method static Item getRareKey()
 * @method static Item getUltraKey()
 * @method static Item getMythicKey()
 * @method static Item getLegendaryKey()
 * @method static Item getMiningMask(int $tier)
 * @method static Item getCoronaMask()
 * @method static Item getDragonMask()
 * @method static Item getBrokenKey()
 * @method static Item getYellowCrystal()
 * @method static Item getRedCrystal()
 * @method static Item getBlueCrystal()
 * @method static Item getPurpleCrystal()
 * @method static Item getYellowKey()
 * @method static Item getBlueKey()
 * @method static Item getRedKey()
 * @method static Item getBlueKey()
 * @method static Item getPurpleKey()
 * @method static Item getKnightNote()
 * @method static Item getFuryNote()
 * @method static Item getHarpyNote()
 * @method static Item getShardNote()
 * @method static Item getLordKnightEgg()
 * @method static Item getGenBucket()
 * @method static Item getCasinoCoin()
 * @method static Item getLemon()
 */
final class CustomItems{
	public const TIER_TAG = "Tier";
	//TODO: Use NBT for items that rely in item damage
	
	/**
	 * @return Item[]
	 */
	public static function getMonthlyLootbox() : array{
		$expBottle = self::getExperienceBottle2(\kenygamer\Core\Main::mt_rand(1000, 2500));
		$atlasGem = ItemUtils::get("atlas_gem")->setCount(10);
		
		$tokens = self::getTokenNote(\kenygamer\Core\Main::mt_rand(50, 250));
    	$paycheck = self::getBankNote(\kenygamer\Core\Main::mt_rand(500000000, 1000000000));
    	
    	$platinumGem = ItemUtils::get("hephaestus_gem");
    	$platinumGem->setCount(\kenygamer\Core\Main::mt_rand(3, 5));
    	
    	$tartarusGem = ItemUtils::get("tartarus_gem");
    	$tartarusGem->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
    	
    	return [
    	    $tokens, $paycheck, $expBottle, $atlasGem, $platinumGem, $tartarusGem
    	];
    }
    
    public static function getHealingCookie() : Item{
    	$item = ItemUtils::get(Item::COOKIE, "&b&lHealing Cookie", [
    	   "&7Gives you &c+ 40 HP&7 when absorbed",
    	   "&7Boost lasts for as long as the server restarts."
    	], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setInt("HealingCookie", 1);
    	$item->setNamedTag($nbt);
    	return $item;
    }
    
    public static function getHestiaCrate() : Item{
    	$item = ItemUtils::get("nether_star:15", "&cHestia Crate", [
    	   "&eRelic",
    	   "&71/9 fragment of a Hestia Gem.",
    	   "",
    	   "&6Tier Level: &eII"
    	], [], "", false);
		return $item;
    }
    
    public static function getAtlasCrate() : Item{
    	$item = ItemUtils::get("nether_star:16", "&9Atlas Crate", [
    	   "&eRelic",
    	   "&71/9 fragment of a Atlas Gem.",
    	   "",
    	   "&6Tier Level: &eI"
    	], [], "", false);
		return $item;
    }
    
    //TODO: > INT_32_MAX support
    
    public static function getMoneyNote(int $money) : ?Item{
    	return self::getBankNote($money);
    }
    
    public static function getBankNote(int $money) : Item{
    	if(!($money >= 1 && $money < 0x7fffffff)){ # (2^32 - 1)
    		return ItemUtils::get(Item::AIR);
    	}
    	$item = ItemUtils::get("paper:100", "&eBank Note", [
           "&dValue: &f" . number_format($money) . "$",
           "&bClick on ground to redeem"
        ], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setTag(new ByteTag("IsValidNote", 1));
    	$nbt->setTag(new IntTag("NoteValue", $money));
    	$item->setCompoundTag($nbt);
    	return $item;
    }
    
    public static function getExperienceBottle2(int $min = 100, int $max = 0) : Item{
    	if($max === 0){
    		static $LEGACY_DAMAGE_TABLE = [
    		   100 => [1, 2],
    		   101 => [3, 4],
    		   102 => [5, 6],
    		   103 => [7, 8],
    		   104 => [9, 10],
    		   105 => [11, 12],
    		   106 => [13, 14],
    		   107 => [15, 16],
    		   108 => [17, 18]
    		];
    		if(isset($LEGACY_DAMAGE_TABLE[$min])){
    			list($min, $max) = $LEGACY_DAMAGE_TABLE[$min];
    		}
    	}
    	if(!($min >= 1 && $min < 0x7fffffff) || !($max >= 1 && $max < 0x7fffffff) || $min > $max){
    		return ItemUtils::get(Item::AIR);
    	}
    	$item = ItemUtils::get("384", "&bExperience Bottle", [
           "&dValue: &f" . $min . "-" . $max . " EXP",
           "&bClick on ground to redeem."
        ], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setTag(new ByteTag("IsValidBottle", 1));
    	$nbt->setTag(new IntTag("EXPValue", \kenygamer\Core\Main::mt_rand($min, $max)));
    	$item->setCompoundTag($nbt);
    	return $item;
    }
    
    public static function getMythicNote(int $min = 50, int $max = 0) : Item{
    	if($max === 0){
    		static $LEGACY_DAMAGE_TABLE = [
    		   50 => [1000, 2000],
    		   51 => [3000, 4000],
    		   52 => [5000, 6000],
    		   53 => [7000, 8000],
    		   54 => [9000, 10000],
    		   55 => [11000, 12000],
    		   56 => [13000, 14000] 
    		];
    		if(isset($LEGACY_DAMAGE_TABLE[$min])){
    			list($min, $max) = $LEGACY_DAMAGE_TABLE[$min];
    		}
    	}
    	if(!($min >= 1 && $min < 0x7fffffff) || !($max >= 1 && $max < 0x7fffffff) || $min > $max){
    		return ItemUtils::get(Item::AIR);
    	}
    	$item = ItemUtils::get("paper:100", "&eBank Note", [
           "&dValue: &f" . $min . "-" . $max . "$",
           "&bClick on ground to redeem"
        ], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setTag(new ByteTag("IsValidNote", 1));
    	$nbt->setTag(new IntTag("NoteValue", \kenygamer\Core\Main::mt_rand($min, $max)));
    	$item->setCompoundTag($nbt);
    	return $item;
    }
    
    public static function getExperienceBottle(int $exp) : Item{
    	if(!($exp >= 1 && $exp < 0x7fffffff)){ #" "
    		return ItemUtils::get(Item::AIR);
    	}
    	$item = ItemUtils::get("384", "&bExperience Bottle", [
           "&dValue: &f" . number_format($exp) . " EXP",
           "&bClick on ground to redeem"
        ], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setTag(new ByteTag("IsValidBottle", 1));
    	$nbt->setTag(new IntTag("EXPValue", $exp));
    	$item->setCompoundTag($nbt);
    	return $item;
    }
    
    public static function getTokenNote(int $tokens) : Item{
    	if(!($tokens >= 1 && $tokens < 0x7fffffff)){ #" "
    		return ItemUtils::get(Item::AIR);
    	}
    	$item = ItemUtils::get("paper:100", "&aToken Note", [
           "&dValue: &f" . number_format($tokens) . " Tokens",
           "&bClick on ground to redeem"
        ], [], "", false);
    	$nbt = $item->getNamedTag();
    	$nbt->setTag(new ByteTag("IsValidToken", 1));
    	$nbt->setTag(new IntTag("NoteValue", $tokens));
    	$item->setCompoundTag($nbt);
    	return $item;
    }
    
    public static function getLuckyBlock() : Item{
    	$item = ItemUtils::get("sponge", "&eLucky Block", [
    	   "&e* &7Relic",
    	   "&7May the odds be in your favor!",
    	   "&7Open at survival worlds.",
    	   "",
    	   "&aGood luck: 50%%",
    	   "&cBad luck: 50%%",
    	   "",
    	   "&6Tier Level: &eI"
    	], [], "", false);
		return $item;
    }
    
    public static function getRainbowLuckyBlock() : Item{
    	$item = ItemUtils::get("sponge:1", "&cRainbow &eLucky Block", [
    	   "&e* &7Relic",
    	   "&7May the odds be in your favor!",
    	   "&7Open at survival worlds.",
    	   "",
    	   "&7High rate of rare loot.",
    	   "&cBe careful you can die opening one.",
    	   "",
    	   "&aGood luck: 20%%",
    	   "&cBad luck: 80%%",
    	   "",
    	   "&6Tier Level: &eII"
    	], [], "", false);
		return $item;
    }
	
	public static function getEnchantDust() : Item{
		$item = ItemUtils::get("sugar", "&dEnchant Dust", [
    	   "&7Drag and drop in the enchant book",
    	   "&7to increase its success rate"
    	], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt(ItemUtils::CONSUMABLE_TAG, ItemUtils::CONSUMABLE_ENCHANT_DUST);
		$item->setNamedTag($nbt);
    	return $item;
	}
	
	public static function getHolyScroll() : Item{
		$item = ItemUtils::get("nether_star", "&dHoly Dust", [
    	   "&7Drag and drop in the enchanted item",
    	   "&7to keep it when you die"
    	], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt(ItemUtils::CONSUMABLE_TAG, ItemUtils::CONSUMABLE_HOLY_SCROLL);
		$item->setNamedTag($nbt);
    	return $item;
	}
    
	public static function getWhiteScroll() : Item{
		$item = ItemUtils::get("sugar", "&fWhite Scroll", [
    	   "&7Drag and drop in the enchant book",
    	   "&7to remove its destroy rate"
    	], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt(ItemUtils::CONSUMABLE_TAG, ItemUtils::CONSUMABLE_WHITE_SCROLL);
		$item->setNamedTag($nbt);
    	return $item;
	}
    
    public static function getCommonBook(int $successRate = -1) : Item{
    	$item = ItemUtils::get("book:100", "&bCommon Enchantment Book", [
			"&7Examine to receive a random",
    	    "&bCommon &7Enchantment Book"
    	], [], "", false);
		$item = ItemUtils::addBookChance($item, $successRate);
    	return $item;
    }
    
    public static function getUncommonBook(int $successRate = -1) : Item{
    	$item = ItemUtils::get("book:101", "&eUncommon Enchantment Book", [
	       "&7Examine to receive a random",
    	   "&eUncommon &7Enchantment Book"
    	], [], "", false);
		$item = ItemUtils::addBookChance($item, $successRate);
    	return $item;
    }
    
    public static function getRareBook(int $successRate = -1) : Item{
    	$item = ItemUtils::get("book:102", "&6Rare Enchantment Book", [
    	   "&7Examine to receive a random",
    	   "&6Rare &7Enchantment Book"
    	], [], "", false);
		$item = ItemUtils::addBookChance($item, $successRate);
    	return $item;
    }
    
    public static function getMythicBook(int $successRate = -1) : Item{
    	$item = ItemUtils::get("book:103", "&cMythic Enchantment Book", [
    	   "&7Examine to receive a random",
    	   "&cMythic &7Enchantment Book"
    	], [], "", false);
		$item = ItemUtils::addBookChance($item, $successRate);
    	return $item;
    }
    
    public static function getDiamondApple() : Item{
    	$item = ItemUtils::get("cooked_fish", "&bDiamond Apple", [], [], "", false);
    	return $item;
    }
    
    public static function getEnchantedDiamondApple() : Item{
    	$item = ItemUtils::get("cooked_fish:5", "&bEnchanted Diamond Apple", [], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getCommonKey() : Item{
    	$item = ItemUtils::get("slime_ball:1", "&aCommon Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&eTier Level: &6I"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
	}
	
	public static function getVoteKey() : Item{
    	$item = ItemUtils::get("slime_ball:2", "&aVote Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&eTier Level: &6II"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
	}
	
	public static function getRareKey() : Item{
		$item = ItemUtils::get("slime_ball:3", "&6Rare Key", [
    	   "&7Click on crate chest to redeem",
    	   "",
    	   "&eTier Level: &6III"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
	}
	
	public static function getUltraKey() : Item{
		$item = ItemUtils::get("slime_ball:4", "&dUltra Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&eTier Level: &6IV"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
	}
	
	public static function getMythicKey() : Item{
		$item = ItemUtils::get("slime_ball:5", "&5Mythic Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&eTier Level: &6V"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
	}
	
	public static function getLegendaryKey() : Item{
		$item = ItemUtils::get("slime_ball:6", "&9Legendary Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&eTier Level: &6VI"
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getMiningMask(int $tier = 1) : Item{
    	$tier = max(1, $tier);
		$item = ItemUtils::get("397:3", "&6Mining Mask", [
		   "&7Increases your earning in prison by " . ($tier * 10) . "%%",
		   "",
		   "&eTier Level: &6" . (Main::getInstance()->getPlugin("CustomEnchants")->getRomanNumber($tier))
		], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt("Mask", 0);
		$nbt->setInt(self::TIER_TAG, $tier);
		$item->setNamedTag($nbt);
		ItemUtils::addDummyEnchant($item);
		return $item;
	}
	
	public static function getCoronaMask() : Item{
		$item = ItemUtils::get("397:3", "&cCorona Mask", [
		   "&7Gives you strength."
		], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt("Mask", 1);
		$item->setNamedTag($nbt);
		ItemUtils::addDummyEnchant($item);
		return $item;
	}
	
	public static function getDragonMask() : Item{
		$item = ItemUtils::get("397:5", "&dDragon Mask", [
		   "&7Gives you strength, health boost, regeneration,",
		   "&7jump boost, and fly."
		], [], "", false);
		$nbt = $item->getNamedTag();
		$nbt->setInt("Mask", 2);
		$item->setNamedTag($nbt);
		ItemUtils::addDummyEnchant($item);
		return $item;
	}
	
    public static function getBrokenKey() : Item{
    	$item = ItemUtils::get("slime_ball:10", "&6Broken Key", [
    	   "&cKey is broken.",
    	   "&7Click on anvil with 5 crystals to repair."
    	], [], "", false);
    	return $item;
    }
    
    public static function getYellowCrystal() : Item{
    	$item = ItemUtils::get("emerald:1", "&eYellow Crystal", [
    	   "&7Use to repair a broken key."
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getRedCrystal() : Item{
    	$item = ItemUtils::get("emerald:2", "&cRed Crystal", [
    	   "&7Use to repair a broken key."
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getGreenCrystal() : Item{
    	$item = ItemUtils::get("emerald:3", "&aGreen Crystal", [
    	   "&7Use to repair a broken key."
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getBlueCrystal() : Item{
    	$item = ItemUtils::get("emerald:4", "&9Blue Crystal", [
    	   "&7Use to repair a broken key."
    	], [], "", false);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getPurpleCrystal() : Item{
    	$item = ItemUtils::get("emerald:5", "&5Purple Crystal", [
    	   "&7Use to repair a broken key."
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getBlueKey() : Item{
    	$item = ItemUtils::get("slimeball:10", "&9Blue Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&6Tier Level: &eII"
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getYellowKey() : Item{
    	$item = ItemUtils::get("slimeball:11", "&eYellow Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&6Tier Level: &eII"
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getRedKey() : Item{
    	$item = ItemUtils::get("slimeball:12", "&cRed Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&6Tier Level: &eIII"
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getGreenKey() : Item{
    	$item = ItemUtils::get("slimeball:13", "&aGreen Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&6Tier Level: &eIV"
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getPurpleKey() : Item{
    	$item = ItemUtils::get("slimeball:15", "&5Purple Key", [
    	   "&7Click on crate chest to redeem.",
    	   "",
    	   "&6Tier Level: &eV"
    	]);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
    
    public static function getKnightNote() : Item{
    	$item = ItemUtils::get("paper:203", "&aKnight Note", [
    	   "&e* &7Relic",
    	   "&bClick on ground to redeem.",
    	   "",
    	   "&r&6Requires rank: Fury",
    	   "&r&6Redeem cost: &b500,000 EXP"
    	], [], "", false);
        ItemUtils::addDummyEnchant($item);
        return $item;
    }
    
    public static function getFuryNote() : Item{
    	$item = ItemUtils::get("paper:201", "&eFury Note", [
    	   "&e* &7Relic",
    	   "&bClick on ground to redeem.",
    	   "",
    	   "&r&6Requires rank: Harpy",
    	   "&r&6Redeem cost: &e400,000 EXP"
    	], [], "", false);
        ItemUtils::addDummyEnchant($item);
        return $item;
    }
    
    public static function getHarpyNote() : Item{
    	$item = ItemUtils::get("paper:201", "&eHarpy Note", [
    	   "&e* &7Relic",
    	   "&bClick on ground to redeem.",
    	   "",
    	   "&r&6Requires rank: Shard",
    	   "&r&6Redeem cost: &e300,000 EXP"
    	], [], "", false);
        ItemUtils::addDummyEnchant($item);
        return $item;
    }
    
    public static function getShardNote() : Item{
    	$item = ItemUtils::get("paper:200", "&eShard Note", [
    	   "&e* &7Relic",
    	   "&bClick on ground to redeem.",
    	   "",
    	   "&r&6Requires rank: Member",
    	   "&r&6Redeem cost: &b200,000 EXP"
    	], [], "", false);
        ItemUtils::addDummyEnchant($item);
        return $item;
    }
    
    public static function getLordKnightEgg() : Item{
    	$item = ItemUtils::get("383:1", "&cSpawn Lord Knight Boss", [
    	   "&7Click on ground to spawn a Lord Knight Boss.",
    	   "&7Use at survival worlds.",
    	   "",
    	   "&6Tier Level: &eIV"
    	]);
    	$nbt = $item->getNamedTag();
    	$nbt->setInt("LordKnight", 1);
    	$item->setNamedTag($nbt);
    	return $item;
    }
    
    public static function getKingGoblinEgg() : Item{
    	$item = ItemUtils::get("383:1", "&cSpawn King Goblin Boss", [
    	   "&7Click on ground to spawn a King Goblin Boss.",
    	   "&7Use at survival worlds.",
    	   "",
    	   "&6Tier Level: &eI"
    	]);
    	$nbt = $item->getNamedTag();
    	$nbt->setInt("KingGoblin", 1);
    	$item->setNamedTag($nbt);
    	return $item;
    }
    
    public static function getGenBucket() : Item{
    	$item = ItemUtils::get("325:10", "&bGen Bucket", [
    	   "&7Builds up a wall of any block."
    	]);
    	$nbt = $item->getNamedTag();
    	$nbt->setInt("GenBucket", 1);
    	$nbt->setInt("UsesLeft", 10);
    	$nbt->setInt("NotStackable", 999999999);
    	$item->setNamedTag($nbt);
    	return $item;
    }
    
    public static function getCasinoCoin() : Item{
    	$item = ItemUtils::get("gold_nugget", "&eCasino Coin", [
    	   "&7Use to gamble in &b/warp casino",
    	   "&7Get more casino coins voting daily."
    	]);
		$nbt = $item->getNamedTag();
		$nbt->setInt("CasinoCoin", 1);
		$item->setNamedTag($nbt);
		return $item;
    }
    
    public static function getLemon() : Item{
    	$item = ItemUtils::get("349", "&eLemon", [
    	    "&7Gives you strength, resistance and",
    	    "&7fire resistance when absorbed."
    	]);
    	$nbt = $item->getNamedTag();
    	$nbt->setInt("Lemon", 1);
    	$item->setNamedTag($nbt);
    	ItemUtils::addDummyEnchant($item);
    	return $item;
    }
}