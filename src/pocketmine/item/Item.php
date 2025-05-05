<?php

/*
 *
 *    _____            _               __  __            
 *   / ____|          (_)             |  \/  |           
 *  | |  __  ___ _ __  _ ___ _   _ ___| \  / | __ ___  __
 *  | | |_ |/ _ \ '_ \| / __| | | / __| |\/| |/ _` \ \/ /
 *  | |__| |  __/ | | | \__ \ |_| \__ \ |  | | (_| |>  < 
 *   \_____|\___|_| |_|_|___/\__, |___/_|  |_|\__,_/_/\_\
 *                            __/ |                      
 *                           |___/                       
 *
 * This program is licensed under the GPLv3 license.
 * You are free to modify and redistribute it under the same license.
 *
 * @author LINUXOV
 * @link vk.com/linuxof
 *
*/



declare(strict_types=1);

/**
 * All the Item classes
 */
namespace pocketmine\item;

use InvalidArgumentException;
use InvalidStateException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\palette\item\ItemPalette;
use pocketmine\network\bedrock\protocol\ProtocolInfo as BedrockProtocolInfo;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\Config;
use RuntimeException;
use function bin2hex;
use function constant;
use function defined;
use function explode;
use function get_class;
use function hex2bin;
use function is_numeric;
use function str_replace;
use function trim;

class Item implements ItemIds, \JsonSerializable{

	private static function parseCompoundTag(string $tag) : CompoundTag{
		return (new LittleEndianNbtSerializer())->read($tag)->mustGetCompoundTag();
	}

	private static function writeCompoundTag(CompoundTag $tag) : string{
		return (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
	}

	/** @var \SplFixedArray */
	public static $list = null;
	/** @var Block|null */
	protected $block;
	/** @var int */
	protected $id;
	/** @var int */
	protected $meta;
	/** @var CompoundTag|null */
	private $nbt;
	/** @var int */
	public $count;
	/** @var string */
	protected $name;

    public static function init(){
        if(self::$list === null) {
            self::$list = new \SplFixedArray(65536);

            self::registerItem(new IronShovel());
            self::registerItem(new IronPickaxe());
            self::registerItem(new IronAxe());
            self::registerItem(new FlintSteel());
            self::registerItem(new Apple());
            self::registerItem(new Bow());
            self::registerItem(new Arrow());
            self::registerItem(new Coal());
            self::registerItem(new Diamond());
            self::registerItem(new IronIngot());
            self::registerItem(new GoldIngot());
            self::registerItem(new IronSword());
            self::registerItem(new WoodenSword());
            self::registerItem(new WoodenShovel());
            self::registerItem(new WoodenPickaxe());
            self::registerItem(new WoodenAxe());
            self::registerItem(new StoneSword());
            self::registerItem(new StoneShovel());
            self::registerItem(new StonePickaxe());
            self::registerItem(new StoneAxe());
            self::registerItem(new DiamondSword());
            self::registerItem(new DiamondShovel());
            self::registerItem(new DiamondPickaxe());
            self::registerItem(new DiamondAxe());
            self::registerItem(new Stick());
            self::registerItem(new Bowl());
            self::registerItem(new MushroomStew());
            self::registerItem(new GoldSword());
            self::registerItem(new GoldShovel());
            self::registerItem(new GoldPickaxe());
            self::registerItem(new GoldAxe());
            self::registerItem(new StringItem());
            self::registerItem(new Feather());
            self::registerItem(new Gunpowder());
            self::registerItem(new WoodenHoe());
            self::registerItem(new StoneHoe());
            self::registerItem(new IronHoe());
            self::registerItem(new DiamondHoe());
            self::registerItem(new GoldHoe());
            self::registerItem(new WheatSeeds());
            self::registerItem(new Wheat());
            self::registerItem(new Bread());
            self::registerItem(new LeatherCap());
            self::registerItem(new LeatherTunic());
            self::registerItem(new LeatherPants());
            self::registerItem(new LeatherBoots());
            self::registerItem(new ChainHelmet());
            self::registerItem(new ChainChestplate());
            self::registerItem(new ChainLeggings());
            self::registerItem(new ChainBoots());
            self::registerItem(new IronHelmet());
            self::registerItem(new IronChestplate());
            self::registerItem(new IronLeggings());
            self::registerItem(new IronBoots());
            self::registerItem(new DiamondHelmet());
            self::registerItem(new DiamondChestplate());
            self::registerItem(new DiamondLeggings());
            self::registerItem(new DiamondBoots());
            self::registerItem(new GoldHelmet());
            self::registerItem(new GoldChestplate());
            self::registerItem(new GoldLeggings());
            self::registerItem(new GoldBoots());
            self::registerItem(new Flint());
            self::registerItem(new RawPorkchop());
            self::registerItem(new CookedPorkchop());
            self::registerItem(new Painting());
            self::registerItem(new GoldenApple());
            self::registerItem(new Sign());
            self::registerItem(new WoodenDoor());
            self::registerItem(new Bucket());

            self::registerItem(new Minecart());
            //TODO: SADLE
            self::registerItem(new IronDoor());
            self::registerItem(new Redstone());
            self::registerItem(new Snowball());
            self::registerItem(new Boat());
            self::registerItem(new Leather());
            //TODO: KELP
            self::registerItem(new Brick());
            self::registerItem(new Clay());
            self::registerItem(new Sugarcane());
            self::registerItem(new Paper());
            self::registerItem(new Book());
            self::registerItem(new Slimeball());
            //TODO: CHEST_MINECART

            self::registerItem(new Egg());
            self::registerItem(new Compass());
            self::registerItem(new FishingRod());
            self::registerItem(new Clock());
            self::registerItem(new GlowstoneDust());
            self::registerItem(new RawFish());
            self::registerItem(new CookedFish());
            self::registerItem(new Dye());
            self::registerItem(new Bone());
            self::registerItem(new Sugar());
            self::registerItem(new Cake());
            self::registerItem(new Bed());
            //TODO: REPEATER
            self::registerItem(new Cookie());
            self::registerItem(new FilledMap());


            self::registerItem(new Shears());
            self::registerItem(new Melon());
            self::registerItem(new PumpkinSeeds());
            self::registerItem(new MelonSeeds());
            self::registerItem(new RawBeef());
            self::registerItem(new Steak());
            self::registerItem(new RawChicken());
            self::registerItem(new CookedChicken());
            self::registerItem(new RottenFlesh());


            self::registerItem(new EnderPearl());
            self::registerItem(new BlazeRod());
            self::registerItem(new GhastTear());
            self::registerItem(new GoldNugget());
            self::registerItem(new NetherWart());


            self::registerItem(new Potion());
            self::registerItem(new GlassBottle());
            self::registerItem(new SpiderEye());
            self::registerItem(new FermentedSpiderEye());
            self::registerItem(new BlazePowder());
            self::registerItem(new MagmaCream());
            self::registerItem(new BrewingStand());


            //TODO: CAULDRON_BLOCK
            self::registerItem(new ShulkerBox(), true);
            self::registerItem(new UndyedShulkerBox(), true);
            //TODO: ENDER_EYE
            self::registerItem(new GlisteringMelon());
            self::registerItem(new SpawnEgg());
            self::registerItem(new ExperienceBottle());
            //TODO: FIREBALL
            self::registerItem(new Emerald());
            self::registerItem(new ItemFrame());
            self::registerItem(new FlowerPot());
            self::registerItem(new Carrot());
            self::registerItem(new Potato());
            self::registerItem(new BakedPotato());
            self::registerItem(new PoisonousPotato());
            self::registerItem(new EmptyMap());
            self::registerItem(new GoldenCarrot());
            self::registerItem(new Skull());
            //TODO: CARROTONASTICK
            self::registerItem(new NetherStar());
            self::registerItem(new PumpkinPie());
            self::registerItem(new EnchantedBook());
            //TODO: COMPARATOR
            self::registerItem(new NetherBrick());
            self::registerItem(new NetherQuartz());
            //TODO: MINECART_WITH_TNT
            //TODO: HOPPER_MINECART
            self::registerItem(new PrismarineShard());
            self::registerItem(new Hopper());
            self::registerItem(new RawRabbit());
            self::registerItem(new CookedRabbit());
            self::registerItem(new RabbitStew());
            self::registerItem(new RabbitFoot());
            self::registerItem(new RabbitHide());
            //TODO: HORSEARMORLEATHER
            //TODO: HORSEARMORIRON
            //TODO: GOLD_HORSE_ARMOR
            //TODO: DIAMOND_HORSE_ARMOR
            //TODO: LEAD
            //TODO: NAMETAG
            self::registerItem(new PrismarineCrystals());
            self::registerItem(new RawMutton());
            self::registerItem(new CookedMutton());
            self::registerItem(new EnderCrystal());
            self::registerItem(new SpruceDoor());
            self::registerItem(new BirchDoor());
            self::registerItem(new JungleDoor());
            self::registerItem(new AcaciaDoor());
            self::registerItem(new DarkOakDoor());
            self::registerItem(new ChorusFruit());
            self::registerItem(new ChorusFruitPopper());

            self::registerItem(new DragonBreath());
            self::registerItem(new SplashPotion());

            //TODO: LINGERING_POTION
            //TODO: SPARKLER
            //TODO: COMMAND_BLOCK_MINECART
            self::registerItem(new Elytra());
            self::registerItem(new ShulkerShell());
            //TODO: MEDICINE
            //TODO: BALLOON
            //TODO: RAPID_FERTILIZER
            self::registerItem(new Totem());
            self::registerItem(new IronNugget());
            //TODO: ICE_BOMB

            //TODO: TRIDENT

            self::registerItem(new Beetroot());
            self::registerItem(new BeetrootSeeds());
            self::registerItem(new BeetrootSoup());
            self::registerItem(new RawSalmon());
            self::registerItem(new Clownfish());
            self::registerItem(new Pufferfish());
            self::registerItem(new CookedSalmon());
            self::registerItem(new GoldenAppleEnchanted());
        }
    }

    /**
     * Registers an item type into the index. Plugins may use this method to register new item types or override existing
     * ones.
     *
     * NOTE: If you are registering a new item type, you will need to add it to the creative inventory yourself - it
     * will not automatically appear there.
     *
     * @return void
     * @throws RuntimeException if something attempted to override an already-registered item without specifying the
     * $override parameter.
     */
    public static function registerItem(Item $item, bool $override = false): void{
        $id = $item->getId();
        if(!$override and self::isRegistered($id)){
            throw new RuntimeException("Trying to overwrite an already registered item");
        }

        self::$list[self::getListOffset($id)] = clone $item;
    }

    /**
     * Returns whether the specified item ID is already registered in the item factory.
     */
    public static function isRegistered(int $id) : bool{
        if($id < 256){
            return BlockFactory::isRegistered($id);
        }
        return self::$list[self::getListOffset($id)] !== null;
    }

    private static function getListOffset(int $id) : int{
        if($id < -0x8000 or $id > 0x7fff){
            throw new InvalidArgumentException("ID must be in range " . -0x8000 . " - " . 0x7fff);
        }
        return $id & 0xffff;
    }

    /** @var Item[][] */
	private static array $creative = [];

	public static function initCreativeItems(): void{
        self::clearCreativeItems();

        /* Bedrock Client */
        foreach(ProtocolAdapterFactory::getAll() as $protocolVersion => $adapter){
            $items = $adapter->getCreativeItems();
            foreach($items as $item){
                self::addCreativeItem($protocolVersion, $item);
            }
        }

        $bedrockCreativeItems = new Config(\pocketmine\RESOURCE_PATH . "bedrock/creativeitems.json", Config::JSON, []);
        foreach($bedrockCreativeItems->getAll() as $data){
            $item = Item::jsonDeserialize($data);
            if($item->getName() === "Unknown"){
                continue;
            }
            self::addCreativeItem(BedrockProtocolInfo::CURRENT_PROTOCOL, $item);
        }

        /* Pw10 Client */
		$creativeItems = new Config(\pocketmine\RESOURCE_PATH . "creativeitems.json", Config::JSON, []);

		foreach($creativeItems->getAll() as $data){
			$item = Item::jsonDeserialize($data);
			if($item->getName() === "Unknown"){
				continue;
			}
			self::addCreativeItem(ProtocolInfo::CURRENT_PROTOCOL, $item);
		}
	}

	public static function clearCreativeItems(): void{
		Item::$creative = [];
	}

	public static function getCreativeItems(int $protocolVersion, bool $isBedrock = false) : array{
		return Item::$creative[$protocolVersion] ?? Item::$creative[$isBedrock ? BedrockProtocolInfo::CURRENT_PROTOCOL : ProtocolInfo::CURRENT_PROTOCOL];
	}

	public static function addCreativeItem(int $protocolVersion, Item $item): void{
		Item::$creative[$protocolVersion][] = clone $item;
	}

	public static function removeCreativeItem(int $protocolVersion, Item $item):void{
		$index = self::getCreativeItemIndex($protocolVersion, $item);
		if($index !== -1){
			unset(Item::$creative[$protocolVersion][$index]);
		}
	}

    public static function removeCreativeItemAllProtocols(Item $item): void
    {
        foreach (Item::$creative as $protocolVersion => $items) {
            $index = self::getCreativeItemIndex($protocolVersion, $item);
            if($index !== -1){
                unset(Item::$creative[$protocolVersion][$index]);
            }
        }
    }

	public static function isCreativeItem(int $protocolVersion, Item $item) : bool{
		foreach(Item::$creative[$protocolVersion] as $i => $d){
			if($item->equals($d, !$item->isTool())){
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $index
	 *
	 * @return Item|null
	 */
	public static function getCreativeItem(int $protocolVersion, int $index){
		return Item::$creative[$protocolVersion][$index] ?? null;
	}

	public static function getCreativeItemIndex(int $protocolVersion, Item $item) : int{
		foreach(Item::$creative[$protocolVersion] as $i => $d){
			if($item->equals($d, !($item instanceof Durable))){
				return $i;
			}
		}

		return -1;
	}

	/**
	 * Returns an instance of the Item with the specified id, meta, count and NBT.
	 *
	 * @param int                $id
	 * @param int                $meta
	 * @param int                $count
	 * @param CompoundTag|string $tags
	 *
	 * @return Item
	 */
	public static function get(int $id, int $meta = 0, int $count = 1, $tags = "") : Item{
		try{
			if($id >= 0 and $id < 256){
                if(($itemId = Block::get($id, $meta)->getItemId()) === $id){
                    $class = new ItemBlock($id, $meta);
                    $class->setCount($count);
                    return $class->setCompoundTag($tags);
                }else{
                    return self::get($itemId, $meta, $count, $tags);
                }
			}else{
				if(isset(self::$list[$id])) {
					$class = self::$list[$id];
				} else {
					$class = null;
				}
				if($class === null){
					return (new Item($id, $meta, $count))->setCompoundTag($tags);
				}else{
					return (new $class($meta, $count))->setCompoundTag($tags);
				}
			}
		}catch(RuntimeException $e){
			return (new Item($id, $meta, $count))->setCompoundTag($tags);
		}
	}

	/**
	 * @return Item
	 */
	public static function air() : Item{
		return Item::get(Item::AIR, 0, 0);
	}

    public static function fromString(string $str, bool $multiple = false){
        if($multiple){
            $blocks = [];
            foreach(explode(",", $str) as $b){
                $blocks[] = self::fromStringSingle($b);
            }

            return $blocks;
        }else{
            return self::fromStringSingle($str);
        }
    }

    public static function fromStringSingle(string $str) : Item{
        $b = explode(":", str_replace([" ", "minecraft:"], ["_", ""], trim($str)));
        if(!isset($b[1])){
            $meta = 0;
        }elseif(is_numeric($b[1])){
            $meta = (int) $b[1];
        }else{
            throw new \InvalidArgumentException("Unable to parse \"" . $b[1] . "\" from \"" . $str . "\" as a valid meta value");
        }

        if(is_numeric($b[0])){
            $item = self::get((int) $b[0], $meta);
        }elseif(defined(ItemIds::class . "::" . mb_strtoupper($b[0]))){
            $item = self::get(constant(ItemIds::class . "::" . mb_strtoupper($b[0])), $meta);
        }else{
            throw new \InvalidArgumentException("Unable to resolve \"" . $str . "\" to a valid item");
        }

        return $item;
    }

	/**
	 * @param int $id
	 * @param int $meta
	 * @param int $count
	 * @param string $name
	 */
	public function __construct(int $id, int $meta = 0, int $count = 1, string $name = "Unknown"){
		$this->id = $id & 0xffff;
		$this->meta = $meta !== -1 ? $meta & 0xffff : -1;
		$this->count = $count;
		$this->name = $name;
		if(!isset($this->block) and $this->id <= 0xff and isset(BlockFactory::$solid[$this->id])){
			$this->block = Block::get($this->id, $this->meta);
			$this->name = $this->block->getName();
		}
	}

	/**
	 * Sets the Item's NBT
	 * @deprecated This method accepts NBT serialized in a network-dependent format.
	 * @see Item::setNamedTag()
	 *
	 * @param CompoundTag|string $tags
	 *
	 * @return $this
	 */
	public function setCompoundTag($tags){
		if($tags instanceof CompoundTag){
			$this->setNamedTag($tags);
		}elseif(is_string($tags) and strlen($tags) > 0){
			$this->setNamedTag(self::parseCompoundTag($tags));
		}else{
			$this->clearNamedTag();
		}

		return $this;
	}

	/**
	 * Returns the serialized NBT of the Item
	 * @return string
	 */
	public function getCompoundTag() : string{
		return $this->nbt !== null ? self::writeCompoundTag($this->nbt) : "";
	}

	/**
	 * Returns whether this Item has a non-empty NBT.
	 * @return bool
	 */
	public function hasCompoundTag() : bool{
		return $this->nbt !== null and $this->nbt->getCount() > 0;
	}

	/**
	 * @return bool
	 */
	public function hasCustomBlockData() : bool{
		return $this->getNamedTag()->hasTag("BlockEntityTag", CompoundTag::class);
	}

	public function clearCustomBlockData(){
		if($this->getNamedTag()->hasTag("BlockEntityTag", CompoundTag::class)){
			$this->getNamedTag()->removeTag("BlockEntityTag");
		}

		return $this;
	}

	/**
	 * @param CompoundTag $compound
	 *
	 * @return $this
	 */
	public function setCustomBlockData(CompoundTag $compound){
		$this->getNamedTag()->setTag("BlockEntityTag", clone $compound);
		return $this;
	}

	/**
	 * @return CompoundTag|null
	 */
	public function getCustomBlockData(){
		$tag = $this->getNamedTag();
		if($tag->hasTag("BlockEntityTag", CompoundTag::class)){
			return $tag->getCompoundTag("BlockEntityTag");
		}

		return null;
	}

	public function hasEnchantments() : bool{
		return $this->getNamedTag()->hasTag("ench", ListTag::class);
	}

	public function hasEnchantment(int $id, int $level = -1) : bool{
		if(!$this->hasEnchantments()){
			return false;
		}

		foreach($this->getNamedTag()->getListTag("ench") as $tag){
			/** @var CompoundTag $tag */
			if($tag->getShort("id") === $id){
				if($level === -1 or $tag->getShort("lvl") === $level){
					return true;
				}
			}
		}

		return false;
	}

	public function getEnchantment(int $id): ?EnchantmentInstance
    {
		if(!$this->hasEnchantments()){
			return null;
		}

		foreach($this->getNamedTag()->getListTag("ench") as $entry) {
            /** @var CompoundTag $entry */
            if ($entry->getShort("id") === $id) {
                $e = Enchantment::getEnchantment($entry->getShort("id"));
                return new EnchantmentInstance($e, $entry->getShort("lvl"));
            }
        }

		return null;
	}

	public function removeEnchantment(int $id, int $level = -1) : void{
		if(!$this->hasEnchantments()){
			return;
		}

		$tag = $this->getNamedTag();
		$enchTag = $tag->getListTag("ench");
		foreach($enchTag as $k => $tag){
			/** @var CompoundTag $tag */
			if($tag->getShort("id") === $id){
				if($level === -1 or $tag->getShort("lvl") === $level){
					$enchTag->remove($k);
					break;
				}
			}
		}
	}

	public function removeEnchantments() : void{
		$this->getNamedTag()->removeTag("ench");
	}

	public function addEnchantment(EnchantmentInstance $ench) : Item{
		$tag = $this->getNamedTag();

		$found = false;

		$enchList = $tag->getListTag("ench");
		if($enchList === null or $enchList->getTagType() !== NBT::TAG_Compound){
			$tag->setTag("ench", $enchList = new ListTag([], NBT::TAG_Compound));
		}else{
			foreach($enchList as $enchTag){
				/** @var CompoundTag $enchTag */
				if($enchTag->getShort("id") === $ench->getId()){
					$enchTag->setShort("lvl", $ench->getLevel());
					$found = true;
					break;
				}
			}
		}

		if(!$found){
			$enchList->push(CompoundTag::create()
				->setShort("id", $ench->getId())
				->setShort("lvl", $ench->getLevel()));
		}

		return $this;
	}

	/**
	 * @return EnchantmentInstance[]
	 */
	public function getEnchantments() : array{
		$enchantments = [];

		if($this->hasEnchantments()) {
            foreach ($this->getNamedTag()->getListTag("ench") as $entry) {
                /** @var CompoundTag $entry */
                $e = Enchantment::getEnchantment($entry->getShort("id"));
                $enchantments[] = new EnchantmentInstance($e, $entry->getShort("lvl"));
            }
        }

		return $enchantments;
	}

    /**
     * Returns the level of the enchantment on this item with the specified ID, or 0 if the item does not have the
     * enchantment.
     *
     * @param int $enchantmentId
     *
     * @return int
     */
    public function getEnchantmentLevel(int $enchantmentId) : int{
        $ench = $this->getNamedTag()->getListTag("ench");
        if($ench !== null){
            /** @var CompoundTag $entry */
            foreach($ench as $entry){
                if($entry->getShort("id") === $enchantmentId){
                    return $entry->getShort("lvl");
                }
            }
        }

        return 0;
    }


    public function hasRepairCost() : bool{
		return $this->getNamedTag()->hasTag("RepairCost", IntTag::class);
	}

	public function getRepairCost() : int{
		$tag = $this->getNamedTag();
		return $tag->hasTag("RepairCost", IntTag::class) ? $tag->getInt("RepairCost") : 1;
	}


	public function setRepairCost(int $cost){
		if($cost === 1){
			$this->clearRepairCost();
			return $this;
		}

		$this->getNamedTag()->setInt("RepairCost", $cost);
		return $this;
	}

	public function clearRepairCost(){
		$tag = $this->getNamedTag();
		if($tag->hasTag("RepairCost", IntTag::class)){
			$tag->removeTag("RepairCost");
			$this->setNamedTag($tag);
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasCustomName() : bool{
		$tag = $this->getNamedTag();
		return $tag->hasTag("display", CompoundTag::class) and $tag->getCompoundTag("display")->hasTag("Name", StringTag::class);
	}

	/**
	 * @return string
	 */
	public function getCustomName() : string{
		$tag = $this->getNamedTag();
		if($tag->hasTag("display", CompoundTag::class)){
			$display = $tag->getCompoundTag("display");
			if($display->hasTag("Name", StringTag::class)){
				return $display->getString("Name");
			}
		}

		return "";
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setCustomName(string $name){
		if($name === ""){
			$this->clearCustomName();
			return $this;
		}

		$tag = $this->getNamedTag();
		if(!$tag->hasTag("display", CompoundTag::class)){
			$tag->setTag("display", new CompoundTag());
		}
		$tag->getCompoundTag("display")->setString("Name", $name);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function clearCustomName(){
		$tag = $this->getNamedTag();
		if($tag->hasTag("display", CompoundTag::class)){
			$display = $tag->getCompoundTag("display");
			if($display->hasTag("Name", StringTag::class)){
				$display->removeTag("Name");
			}
		}

		return $this;
	}

	public function getLore() : array{
		/** @var CompoundTag $tag */
		$tag = $this->getNamedTag()->getTag("display", CompoundTag::class);
		if($tag->hasTag("Lore", ListTag::class)){
			$lines = [];
			foreach($tag->getListTag("Lore") as $line){
				$lines[] = $line->getValue();
			}

			return $lines;
		}

		return [];
	}

	/**
	 * @param string[] $lines
	 *
	 * @return $this
	 */
	public function setLore(array $lines){
		$tag = $this->getNamedTag();
		if(!$tag->hasTag("display", CompoundTag::class)){
			$tag->setTag("display", new CompoundTag());
		}

		$displayTag = $tag->getCompoundTag("display");
		$displayTag->setTag("Lore", $loreTag = new ListTag([], NBT::TAG_String));

		foreach($lines as $line){
			$loreTag->push(new StringTag($line));
		}

		return $this;
	}

	/**
	 * Returns a tree of Tag objects representing the Item's NBT
	 * @return null|CompoundTag
	 */
	public function getNamedTag(){
		return $this->nbt ?? ($this->nbt = new CompoundTag());
	}

	/**
	 * Sets the Item's NBT from the supplied CompoundTag object.
	 * @param CompoundTag $tag
	 *
	 * @return $this
	 */
	public function setNamedTag(CompoundTag $tag){
		if($tag->getCount() === 0){
			return $this->clearNamedTag();
		}

		$this->nbt = $tag;
		return $this;
	}

	/**
	 * Removes the Item's NBT.
	 * @return Item
	 */
	public function clearNamedTag(){
		$this->nbt = null;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCount() : int{
		return $this->count;
	}

	/**
	 * @param int $count
	 */
	public function setCount(int $count){
		$this->count = $count;
	}

	/**
	 * Returns the name of the item, or the custom name if it is set.
	 * @return string
	 */
	final public function getName() : string{
		return $this->hasCustomName() ? $this->getCustomName() : $this->name;
	}

	/**
	 * Returns the vanilla name of the item, disregarding custom names.
	 * @return string
	 */
	public function getVanillaName() : string{
		return $this->name;
	}

	/**
	 * Pops an item from the stack and returns it, decreasing the stack count of this item stack by one.
	 * @return Item
	 *
	 * @throws InvalidStateException if the count is less than or equal to zero, or if the stack is air.
	 */
    public function pop(int $count = 1) : Item{
        if($count > $this->count){
            throw new \InvalidArgumentException("Cannot pop $count items from a stack of $this->count");
        }

        $item = clone $this;
        $item->count = $count;

        $this->count -= $count;

        return $item;
    }

	/**
	 * @return bool
	 */
	final public function canBePlaced() : bool{
		return $this->block !== null and $this->block->canBePlaced();
	}

	/**
	 * Returns whether an entity can eat or drink this item.
	 * @return bool
	 */
	public function canBeConsumed() : bool{
		return false;
	}

	/**
	 * Returns whether this item can be consumed by the supplied Entity.
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canBeConsumedBy(Entity $entity) : bool{
		return $this->canBeConsumed();
	}

	/**
	 * Called when the item is consumed by an Entity.
	 * @param Entity $entity
	 */
	public function onConsume(Entity $entity): void{

	}

	/**
	 * Returns the block corresponding to this Item.
	 * @return Block
	 */
	public function getBlock() : Block{
		if($this->block instanceof Block){
			return clone $this->block;
		}else{
			return Block::get(self::AIR);
		}
	}

	/**
	 * @return int
	 */
	final public function getId() : int{
		return $this->id;
	}

	/**
	 * @return int
	 */
	final public function getDamage() : int{
		return $this->meta;
	}

	/**
	 * @param int $meta
	 */
	public function setDamage(int $meta){
		$this->meta = $meta !== -1 ? $meta & 0xFFFF : -1;
	}

	/**
	 * Returns whether this item can match any item with an equivalent ID with any meta value.
	 * Used in crafting recipes which accept multiple variants of the same item, for example crafting tables recipes.
	 *
	 * @return bool
	 */
	public function hasAnyDamageValue() : bool{
		return $this->meta === -1;
	}

	/**
	 * Returns the highest amount of this item which will fit into one inventory slot.
	 * @return int
	 */
	public function getMaxStackSize() : int{
		return 64;
	}

    /**
     * Returns the time in ticks which the item will fuel a furnace for.
     */
    public function getFuelTime() : int{
        return 0;
    }

    /**
     * Returns an item after burning fuel
     */
    public function getFuelResidue() : Item{
        $item = clone $this;
        $item->pop();

        return $item;
    }

    /**
     * Returns how many points of damage this item will deal to an entity when used as a weapon.
     */
    public function getAttackPoints() : int{
        return 1;
    }

    /**
     * Returns how many armor points can be gained by wearing this item.
     */
    public function getArmorPoints() : int{
        return 0;
    }

    /**
     * Returns what type of block-breaking tool this is. Blocks requiring the same tool type as the item will break
     * faster (except for blocks requiring no tool, which break at the same speed regardless of the tool used)
     */
    public function getBlockToolType() : int{
        return Tool::TYPE_NONE;
    }

    /**
     * Returns the harvesting power that this tool has. This affects what blocks it can mine when the tool type matches
     * the mined block.
     * This should return 1 for non-tiered tools, and the tool tier for tiered tools.
     *
     * @see Block::getToolHarvestLevel()
     */
    public function getBlockToolHarvestLevel() : int{
        return 0;
    }

    public function getMiningEfficiency(Block $block) : float{
        return 1;
    }

	/**
	 * Called when a player is using this item and releases it. Used to handle bow shoot actions.
	 *
	 * @param Player $player
	 */
	public function onReleaseUsing(Player $player) : void{

	}

    /**
     * Called when this item is used to destroy a block. Usually used to update durability.
     */
    public function onDestroyBlock(Block $block) : bool{
        return false;
    }

    /**
     * Called when this item is used to attack an entity. Usually used to update durability.
     */
    public function onAttackEntity(Entity $victim) : bool{
        return false;
    }

	/**
	 * Returns the number of ticks a player must wait before activating this item again.
	 *
	 * @return int
	 */
	public function getCooldownTicks() : int{
		return 0;
	}

	/**
	 * @return bool
	 */
	public function isTool(){
		return false;
	}

	/**
	 * @return int
	 */
	public function getMaxDurability(): int{
		return 16;
	}

	/** @deprecated */
	public function isPickaxe(){
		return false;
	}

	/** @deprecated  */
	public function isAxe(){
		return false;
	}

	/** @deprecated  */
	public function isSword(){
		return false;
	}

	/** @deprecated */
	public function isShovel(){
		return false;
	}

	public function isHoe(){
		return $this instanceof Hoe;
	}

	public function isShears(){
		return false;
	}

    /**
     * Called when a player uses this item on a block.
     */
    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
        return false;
    }
	/**
	 * Called when a player uses the item on air, for example throwing a projectile.
	 * Returns whether the item was changed, for example count decrease or durability change.
	 *
	 * @param Player  $player
	 * @param Vector3 $directionVector
	 *
	 * @return bool
	 */
	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isNull() : bool{
		return $this->id === self::AIR or $this->count < 1;
	}

	/**
	 * Compares an Item to this Item and check if they match.
	 *
	 * @param Item $item
	 * @param bool $checkDamage Whether to verify that the damage values match.
	 * @param bool $checkCompound Whether to verify that the items' NBT match.
	 * @param bool $checkCount
	 *
	 * @return bool
	 */

    final public function equals(Item $item, bool $checkDamage = true, bool $checkCompound = true, bool $checkCount = false) : bool{
        return $this->id === $item->getId() and
            (!$checkDamage or $this->getDamage() === $item->getDamage()) and
            (!$checkCompound or $this->getNamedTag()->equals($item->getNamedTag())) and
            (!$checkCount or $this->getCount() === $item->getCount());
    }

	/**
	 * @deprecated Use {@link \pocketmine\entity\object\Item#equals} instead, this method will be removed in the future.
	 *
	 * @param Item $item
	 * @param bool $checkDamage
	 * @param bool $checkCompound
	 *
	 * @return bool
	 */
	final public function deepEquals(Item $item, bool $checkDamage = true, bool $checkCompound = true) : bool{
		return $this->equals($item, $checkDamage, $checkCompound);
	}

	/**
	 * @return string
	 */
	final public function __toString() : string{
		return "Item " . $this->name . " (" . $this->id . ":" . ($this->hasAnyDamageValue() ? "?" : $this->meta) . ")x" . $this->count . ($this->hasCompoundTag() ? " tags:0x" . bin2hex($this->getCompoundTag()) : "");
	}

	/**
	 * Returns an array of item stack properties that can be serialized to json.
	 *
	 * @return array
	 */
	final public function jsonSerialize() : array{
		return [
			"id" => $this->getId(),
			"damage" => $this->getDamage(),
			"count" => $this->getCount(),
			"nbt_hex" => bin2hex($this->getCompoundTag())
		];
	}

	/**
	 * Returns an Item from properties created in an array by {@link \pocketmine\entity\object\Item#jsonSerialize}
	 *
	 * @param array $data
	 * @return Item
	 */
	final public static function jsonDeserialize(array $data) : Item{
        $nbt = "";

        //Backwards compatibility
        if(isset($data["nbt"])){
            $nbt = $data["nbt"];
        }elseif(isset($data["nbt_hex"])){
            $nbt = hex2bin($data["nbt_hex"]);
        }elseif(isset($data["nbt_b64"])){
            $nbt = base64_decode($data["nbt_b64"], true);
        }

		return Item::get(
			(int) $data["id"],
			(int) ($data["damage"] ?? 0),
			(int) ($data["count"] ?? 1),
			(string) $nbt
		);
	}

	/**
	 * Serializes the item to an NBT CompoundTag
	 *
	 * @param int $slot optional, the inventory slot of the item
	 *
	 * @return CompoundTag
	 */
	public function nbtSerialize(int $slot = -1, bool $isIdString = false) : CompoundTag{
		$tag = CompoundTag::create();
        if($isIdString and $this->id !== 0){
            [$runtimeId, $_] = ItemPalette::getRuntimeFromLegacyId($this->id, $this->meta);
            $name = ItemPalette::getStringFromRuntimeId($runtimeId);
            $tag->setString("Name", $name);
        }else{
            $tag->setShort("id", $this->id);
        }
		$tag->setByte("Count", Binary::signByte($this->count));
		$tag->setShort("Damage", $this->meta);

		if($this->hasCompoundTag()){
			$tag->setTag("tag", clone $this->getNamedTag());
		}

		if($slot !== -1){
			$tag->setByte("Slot", $slot);
		}

		return $tag;
	}

	/**
	 * Deserializes an Item from an NBT CompoundTag
	 */
	public static function nbtDeserialize(CompoundTag $tag) : Item{
		if(!$tag->hasTag("id") or !$tag->hasTag("Count")){
			return Item::air();
		}

		$count = Binary::unsignByte($tag->getByte("Count"));
		$meta = $tag->getShort("Damage", 0);

		$idTag = $tag->getTag("id");
		if($idTag instanceof ShortTag){
			$item = Item::get($idTag->getValue(), $meta, $count);
		}elseif($idTag instanceof StringTag){ //PC item save format
            try{
                $item = Item::fromStringSingle($idTag->getValue());
            }catch(\InvalidArgumentException $e){
                //TODO: improve error handling
                return Item::air();
            }
			$item->setDamage($meta);
			$item->setCount($count);
		}else{
			throw new InvalidArgumentException("Item CompoundTag ID must be an instance of StringTag or ShortTag, " . get_class($tag->id) . " given");
		}

		if($tag->hasTag("tag", CompoundTag::class)){
			$item->setNamedTag(clone $tag->getCompoundTag("tag"));
		}

		return $item;
	}

	public function __clone(){
		if($this->nbt !== null){
			$this->nbt = clone $this->nbt;
		}
	}
}


