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

namespace pocketmine\network\bedrock\adapter\v389\protocol;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\bedrock\protocol\types\PotionTypeRecipe;
use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\bedrock\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use UnexpectedValueException;

trait PacketTrait{

	public function getItemStackWithoutStackId() : Item{
		$id = $this->getVarInt();
		if($id <= 0){
			return Item::air();
		}

		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		if($data === 0x7fff){
			$data = -1;
		}
		$cnt = $auxValue & 0xff;

		$nbt = null;

		$nbtLen = $this->getLShort();
		if($nbtLen === 0xffff){
			$c = $this->getByte();
			if($c !== 1){
				throw new UnexpectedValueException("Unexpected NBT count $c");
			}

			$nbt = $this->getNbtCompoundRoot();
			if($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)){ //a hack: 1.12+ meta format
				$data = $nbt->getInt(self::DAMAGE_TAG);
				$nbt->removeTag(self::DAMAGE_TAG);
			}

			if($nbt->hasTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)){
				$nbt->setTag(self::DAMAGE_TAG, $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION));
				$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
			}

			if($nbt->hasTag("map_uuid", LongTag::class)){ // 1.1 compatibility hack
				$nbt->setString("map_uuid", (string) $nbt->getLong("map_uuid"));
			}
		}elseif($nbtLen !== 0){
			throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
		}

		//TODO
		$canPlaceOn = $this->getVarInt();
		if($canPlaceOn > 128){
			throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
		}elseif($canPlaceOn > 0){
			for($i = 0; $i < $canPlaceOn; ++$i){
				$this->getString();
			}
		}

		//TODO
		$canDestroy = $this->getVarInt();
		if($canDestroy > 128){
			throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
		}elseif($canDestroy > 0){
			for($i = 0; $i < $canDestroy; ++$i){
				$this->getString();
			}
		}

		if($id === 513){ //SHIELD
			$this->getVarLong(); //"blocking tick" (ffs mojang)
		}

        $item = Item::get($id, $data, $cnt, $nbt);
        if ($data >= $item->getMaxDurability()) {
            $item->setDamage(0);
        }
        return $item;
	}

	public function putItemStackWithoutStackId(Item $item) : void{
        if($item->isNull()){
            $this->putVarInt(0);
            return;
        }

        $meta = $item->getDamage();
        if ($meta >= $item->getMaxDurability()) {
            $meta = 0;
        }

		$this->putVarInt($item->getId());
		$auxValue = $item->getCount();
		if(!$item instanceof Durable){
			$auxValue |= (($meta & 0x7fff) << 8);
		}
		$this->putVarInt($auxValue);

		if($item->hasCompoundTag() or ($item instanceof Durable and $meta !== 0)){
			$this->putLShort(0xffff);
			$this->putByte(1); //TODO: some kind of count field? always 1 as of 1.9.0

			$nbt = clone $item->getNamedTag();
			if($item instanceof Durable and $meta !== 0){
				if($nbt->hasTag(self::DAMAGE_TAG)){
					$nbt->setTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION, $nbt->getTag(self::DAMAGE_TAG));
				}

				$nbt->setInt(self::DAMAGE_TAG, $meta & 0x7fff); //a hack: 1.12+ meta format
			}
			if($nbt->hasTag("map_uuid", StringTag::class)){ // 1.1 compatibility
				$nbt->setLong("map_uuid", (int) $nbt->getString("map_uuid"));
			}

			$this->put((new NetworkNbtSerializer())->write(new TreeRoot($nbt)));
		}else{
			$this->putLShort(0);
		}

		$this->putVarInt(0); //CanPlaceOn entry count (TODO)
		$this->putVarInt(0); //CanDestroy entry count (TODO)

		if($item->getId() === 513){ //SHIELD
			$this->putVarLong(0); //"blocking tick" (ffs mojang)
		}
	}

	public function putRecipeIngredient(Item $item) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);
			return;
		}

		$this->putVarInt($item->getId());
		$this->putVarInt($item->hasAnyDamageValue() ? 0x7fff : $item->getDamage());
		$this->putVarInt($item->getCount());
	}

	public function getRecipeIngredient() : Item{
		$id = $this->getVarInt();
		if($id === 0){
			return Item::get(0, 0, 0);
		}

		$data = $this->getVarInt();
		if($data === 0x7fff){
			$data = -1;
		}

		$cnt = $this->getVarInt();
		return Item::get($id, $data, $cnt);
	}

    public function writePotionTypes(PotionTypeRecipe $entry):void{
        $this->putVarInt($entry->getInputPotionId());
        $this->putVarInt($entry->getIngredientItemId());
        $this->putVarInt($entry->getOutputPotionId());
    }

    /**
     * @param Item|ItemInstance $item
     */
    public function putItemInstance($item) : void{
        if($item instanceof ItemInstance){
            $item = $item->stack;
        }

        $this->putItemStackWithoutStackId($item);
    }

    public function getItemInstance() : ItemInstance{
        return ItemInstance::legacy($this->getItemStackWithoutStackId());
    }

    public function getSkin() : Skin{
        $skinId = $this->getString();
        $skinResourcePatch = $this->getString();
        $skinImage = $this->getImage();

        $animations = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many skin animations: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $image = $this->getImage();
            $type = $this->getLInt();
            $frames = $this->getLFloat();
            $animations[] = new SkinAnimation($image, $type, $frames, SkinAnimation::EXPRESSION_LINEAR);
        }

        $capeImage = $this->getImage();
        $geometryData = $this->getString();
        $animationData = $this->getString();
        $isPremium = $this->getBool();
        $isPersona = $this->getBool();
        $isCapeOnClassic = $this->getBool();
        $capeId = $this->getString();
        $fullSkinId = $this->getString();

        return new Skin($skinId, "", $skinResourcePatch, $skinImage, $animations, $capeImage, $geometryData, $animationData, $isPremium, $isPersona, $isCapeOnClassic, $capeId, $fullSkinId);
    }

    /**
     * @param Skin $skin
     */
    public function putSkin(Skin $skin) : void{
        $this->putString($skin->getSkinId());
        $this->putString($skin->getSkinResourcePatch());
        $this->putImage($skin->getSkinImage());

        $animations = $skin->getAnimations();
        $this->putLInt(count($animations));
        foreach($animations as $animation){
            $this->putImage($animation->getImage());
            $this->putLInt($animation->getType());
            $this->putLFloat($animation->getFrames());
        }

        $this->putImage($skin->getCapeImage());
        $this->putString($skin->getGeometryData());
        $this->putString($skin->getAnimationData());
        $this->putBool($skin->isPremium());
        $this->putBool($skin->isPersona());
        $this->putBool($skin->isCapeOnClassic());
        $this->putString($skin->getCapeId());
        $this->putString($skin->getFullSkinId());
    }
}

