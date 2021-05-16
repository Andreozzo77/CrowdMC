# PocketMine edits
## `Living.php`

`getArmorPoints()`: Check if `Living->armorInventory` is not null.
`getArmorInventory()`: Change return typehint `ArmorInventory` to `?ArmorInventory`. 

## `Level.php`

`destroyBlockInternal()`: Disable creating particles, always.

## `RakLibInterface.php`

`handleEncapsulated()`: Catch `\Throwable`'s and create a GitHub issue automatically.

## `EntityIds.php`

Add `EntityIds::FOX`, `EntityIds::BEE`

## `Entity.php`

`onUpdate()`: Change `$this->motion->x != 0 or $this->motion->y != 0 or $this->motion->z != 0` condition to `!($this->motion->x != 0 or $this->motion->y != 0 or $this->motion->z != 0)`

## `AddActorPacket.php`

Add `EntityIds::FOX`, `EntityIds::BEE` to legacy entity IDs map