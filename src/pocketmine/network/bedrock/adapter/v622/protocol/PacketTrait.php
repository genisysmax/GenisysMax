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

namespace pocketmine\network\bedrock\adapter\v622\protocol;

use LogicException;
use pocketmine\block\BlockIds;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\adapter\v622\palette\BlockPalette as BlockPalette622;
use pocketmine\network\bedrock\adapter\v622\palette\ItemPalette as ItemPalette622;
use pocketmine\network\bedrock\protocol\types\ItemDescriptorType;
use pocketmine\network\bedrock\protocol\types\PotionTypeRecipe;
use pocketmine\network\mcpe\NetworkBinaryStream;
use UnexpectedValueException;

trait PacketTrait{

	public function getItemStack(\Closure $readExtraCrapInTheMiddle) : Item
    {
        $netId = $this->getVarInt();
        if ($netId === 0) {
            return Item::air();
        }

        $cnt = $this->getLShort();
        $netData = $this->getUnsignedVarInt();

        [$id, $meta] = ItemPalette622::getLegacyFromRuntimeId($netId, $netData);

        $readExtraCrapInTheMiddle($this);

        $this->getVarInt();

        $extraData = new NetworkBinaryStream($this->getString());
        return (static function () use ($extraData, $netId, $id, $meta, $cnt): Item {
            $nbtLen = $extraData->getLShort();

            /** @var CompoundTag|null $nbt */
            $nbt = null;
            if ($nbtLen === 0xffff) {
                $c = $extraData->getByte();
                if ($c !== 1) {
                    throw new UnexpectedValueException("Unexpected NBT data version $c");
                }

                $nbt = (new LittleEndianNbtSerializer())->read($extraData->buffer, $extraData->offset, 512)->mustGetCompoundTag();

                if ($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)) { //a hack: 1.12+ meta format
                    $meta = $nbt->getInt(self::DAMAGE_TAG);
                    $nbt->removeTag(self::DAMAGE_TAG);
                } elseif (($metaTag = $nbt->getTag(self::PM_META_TAG)) instanceof IntTag) {
                    //TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
                    //client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
                    //client-side. Aside from being very annoying, this also breaks various server-side behaviours.
                    $meta = $metaTag->getValue();
                    $nbt->removeTag(self::PM_META_TAG);
                }

                if ($nbt->hasTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)) {
                    $nbt->setTag(self::DAMAGE_TAG, $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION));
                    $nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
                }

                if ($nbt->hasTag("map_uuid", LongTag::class)) { // 1.1 compatibility hack
                    $nbt->setString("map_uuid", (string)$nbt->getLong("map_uuid"));
                }

                if ($nbt->count() === 0) {
                    $nbt = null;
                }
            } elseif ($nbtLen !== 0) {
                throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
            }


            //TODO
            $canPlaceOn = $extraData->getLInt();
            if ($canPlaceOn > 128) {
                throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
            } elseif ($canPlaceOn > 0) {
                for ($i = 0; $i < $canPlaceOn; ++$i) {
                    $extraData->get($extraData->getLShort());
                }
            }

            //TODO
            $canDestroy = $extraData->getLInt();
            if ($canDestroy > 128) {
                throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
            } elseif ($canDestroy > 0) {
                for ($i = 0; $i < $canDestroy; ++$i) {
                    $extraData->get($extraData->getLShort());
                }
            }

            if ($netId === ItemPalette622::getRuntimeFromStringId("minecraft:shield")) { //SHIELD
                $extraData->getLLong(); //"blocking tick" (ffs mojang)
            }

            if (!$extraData->feof()) {
                throw new \UnexpectedValueException("Unexpected trailing extradata for network item $netId");
            }

            $item = Item::get($id, $meta, $cnt, $nbt);
            if ($meta >= $item->getMaxDurability()) {
                $item->setDamage(0);
            }
            return $item;
        })();
    }

	public function putItemStack(Item $item, \Closure $writeExtraCrapInTheMiddle) : void
    {
        if ($item->isNull()) {
            $this->putVarInt(0);
            return;
        }

        $coreData = $item->getDamage();
        [$netId, $netData] = ItemPalette622::getRuntimeFromLegacyId($item->getId(), $item instanceof Durable ? 0 : $item->getDamage());

        if ($coreData >= $item->getMaxDurability()) {
            $netData = 0;
        }

        $this->putVarInt($netId);
        $this->putLShort($item->getCount());
        $this->putUnsignedVarInt($netData);

        $writeExtraCrapInTheMiddle($this);

        $blockRuntimeId = 0;
        $isBlockItem = $item->getId() < 256;
        if ($isBlockItem) {
            $block = $item->getBlock();
            if ($block->getId() !== BlockIds::AIR) {
                $blockRuntimeId = BlockPalette622::getRuntimeFromLegacyId($block->getId(), $block->getDamage());
            }
        }
        $this->putVarInt($blockRuntimeId);

        $isDurable = $item instanceof Durable;
        $nbt = null;
        if ($item->hasCompoundTag() or $isDurable) {
            $nbt = clone $item->getNamedTag();
        }

        if ($isDurable and $coreData !== 0) {
            if ($nbt->hasTag(self::DAMAGE_TAG)) {
                $nbt->setTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION, $nbt->getTag(self::DAMAGE_TAG));
            }

            if ($nbt->hasTag("map_uuid", StringTag::class)) { // 1.1 compatibility
                $nbt->setLong("map_uuid", (int)$nbt->getString("map_uuid"));
            }

            $nbt->setInt(self::DAMAGE_TAG, $coreData);
        } elseif ($isBlockItem && $coreData !== 0) {
            //TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
            //client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
            //client-side. Aside from being very annoying, this also breaks various server-side behaviours.
            if ($nbt === null) {
                $nbt = new CompoundTag();
            }
            $nbt->setInt(self::PM_META_TAG, $coreData);
        }

        $this->putString((static function () use ($nbt, $netId): string {
            $extraData = new NetworkBinaryStream();
            if ($nbt !== null) {
                $extraData->putLShort(0xffff);
                $extraData->putByte(1); //TODO: NBT data version (?)
                $extraData->put((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)));
            } else {
                $extraData->putLShort(0);
            }

            $extraData->putLInt(0); //CanPlaceOn entry count (TODO)
            $extraData->putLInt(0); //CanDestroy entry count (TODO)

            if ($netId === ItemPalette622::getRuntimeFromStringId("minecraft:shield")) { //SHIELD
                $this->putLLong(0); //"blocking tick" (ffs mojang)
            }
            return $extraData->buffer;
        })());
    }

    public function putRecipeIngredient(Item $item) : void{
        if($item->isNull()){
            $this->putByte(0);
        }else{
            if($item->hasAnyDamageValue()){
                [$netId, ] = ItemPalette622::getRuntimeFromLegacyId($item->getId(), 0);
                $netData = 0x7fff;
            }else{
                [$netId, $netData] = ItemPalette622::getRuntimeFromLegacyId($item->getId(), $item->getDamage());
            }

            $this->putByte(ItemDescriptorType::INT_ID_META);
            $this->putLShort($netId);
            if($netId !== 0){
                $this->putLShort($netData);
            }
        }
        $this->putVarInt($item->getCount());
    }

    public function getRecipeIngredient() : Item{
        $descriptorType = $this->getByte();
        if($descriptorType === ItemDescriptorType::INT_ID_META){
            $netId = $this->getLShort();
            if($netId !== 0){
                $netData = $this->getLShort();
            }else{
                $netData = 0;
            }
        }elseif($descriptorType === ItemDescriptorType::STRING_ID_META){
            $netId = ItemPalette622::getRuntimeFromStringId($this->getString());
            $netData = $this->getLShort();
        }else{
            throw new LogicException("Unsupported conversion of recipe ingredient");
        }

        [$id, $meta] = ItemPalette622::getLegacyFromRuntimeId($netId, $netData);
        $cnt = $this->getVarInt();
        return Item::get($id, $meta, $cnt);
    }

    public function writePotionTypes(PotionTypeRecipe $entry):void{
        [$netIdInput, $netDataInput] = ItemPalette622::getRuntimeFromLegacyId($entry->getInputPotionId(), $entry->getInputPotionMeta());
        [$netIdIngredient, $netDataIngredient] = ItemPalette622::getRuntimeFromLegacyId($entry->getIngredientItemId(), $entry->getIngredientItemMeta());
        [$netIdOutput, $netDataOutput] = ItemPalette622::getRuntimeFromLegacyId($entry->getOutputPotionId(), $entry->getOutputPotionMeta());

        $this->putVarInt($netIdInput);
        $this->putVarInt($netDataInput);
        $this->putVarInt($netIdIngredient);
        $this->putVarInt($netDataIngredient);
        $this->putVarInt($netIdOutput);
        $this->putVarInt($netDataOutput);
    }
}

