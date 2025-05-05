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

namespace pocketmine\network\bedrock\palette\block;

use pocketmine\block\BlockIds;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\UnexpectedTagTypeException;
use pocketmine\network\mcpe\NetworkNbtSerializer;

trait Pre407BlockPaletteSerializer{
    public function __construct(){
        //NOOP
    }

    /** @var int[] */
    private static $runtimeToLegacyIdMap = [];
    /** @var int[] */
    private static $legacyToRuntimeIdMap = [];

    /** @var string */
    private static $encodedPalette = null;

    public static function init() : void{
        $io = new NetworkNbtSerializer();

        $root = $io->read(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL."/required_block_states.nbt"));

        $stateList = $root->getTag();
        if(!$stateList instanceof ListTag or $stateList->getTagType() !== NBT::TAG_Compound){
            throw new UnexpectedTagTypeException("Expected TAG_List<TAG_Compound>, got {$stateList->getType()}");
        }

        $legacyIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL."/block_id_map.json"), true);

        $io12 = new NetworkNbtSerializer();

        $root = $io12->read(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL."/r12_to_current_block_map.nbt"));

        $legacyStateMapReader = $root->getTag();
        if(!$legacyStateMapReader instanceof ListTag or $legacyStateMapReader->getTagType() !== NBT::TAG_Compound){
            throw new UnexpectedTagTypeException("Expected TAG_List<TAG_Compound>, got {$legacyStateMapReader->getType()}");
        }

        /**
         * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
         */
        $idToStatesMap = [];
        $blockStates = [];
        $runtimeIdCounter = 0;
        foreach($stateList as $k => $state){
            /** @var CompoundTag $state */
            $runtimeId = $runtimeIdCounter++;
            $blockStates[$runtimeId] = $state;
            $idToStatesMap[$state->getCompoundTag("block")->getString("name")][] = $k;
        }
        /** @var CompoundTag $pair */
        foreach($legacyStateMapReader as $pair){
            $oldState = $pair->getCompoundTag("old");
            $id = $legacyIdMap[$oldState->getString("name")];
            $data = $oldState->getShort("val");
            if($data > 15){
                //we can't handle metadata with more than 4 bits
                continue;
            }
            $mappedStateNew = $pair->getCompoundTag("new");
            //SET `block`
            $pair->removeTag("new");
            $pair->setTag("block", $mappedStateNew);
            $mappedState = $pair->getCompoundTag("block");

            $mappedName = $mappedState->getString("name");
            if(!isset($idToStatesMap[$mappedName])){
                throw new \RuntimeException("Mapped new state does not appear in network table");
            }
            foreach($idToStatesMap[$mappedName] as $k){
                $networkState = $blockStates[$k];
                if($mappedState->equals($networkState->getCompoundTag("block"))){
                    self::registerMapping($k, $id, $data);
                    continue 2;
                }
            }
            throw new \RuntimeException("Mapped new state does not appear in network table");
        }

        self::$encodedPalette = $io->write(new TreeRoot(new ListTag($blockStates)));
    }

    private static function registerMapping(int $runtimeId, int $legacyId, int $legacyMeta) : void{
        self::$legacyToRuntimeIdMap[($legacyId << 5) | $legacyMeta] = $runtimeId;
        self::$runtimeToLegacyIdMap[$runtimeId] = ($legacyId << 5) | $legacyMeta;
    }

    public static function lazyInit() : void{
        if(self::$encodedPalette === null){
            self::init();
        }
    }

    /**
     * @param int $id
     * @param int $meta
     *
     * @return int
     */
    public static function getRuntimeFromLegacyId(int $id, int $meta = 0) : int{
        /*
        * try id+meta first
        * if not found, try id+0 (strip meta)
        * if still not found, return update! block
        */
        return self::$legacyToRuntimeIdMap[($id << 5) | $meta] ?? self::$legacyToRuntimeIdMap[$id << 5] ?? self::$legacyToRuntimeIdMap[BlockIds::INFO_UPDATE << 5];
    }

    /**
     * @param int $runtimeId
     * @param &$id = 0
     * @param &$meta = 0
     */
    public static function getLegacyFromRuntimeId(int $runtimeId, &$id = 0, &$meta = 0) : void{
        if(isset(self::$runtimeToLegacyIdMap[$runtimeId])){
            $v = self::$runtimeToLegacyIdMap[$runtimeId];
            $id = $v >> 5;
            $meta = $v & 0xf;
        }
    }

    /**
     * @return string|null
     */
    public static function getEncodedPalette() : ?string{
        return self::$encodedPalette;
    }

    public static function setEncodedPalette(?string $buffer) : void{
        self::$encodedPalette = $buffer;
    }

    public static function getRuntimeToLegacy() : array{
        return self::$runtimeToLegacyIdMap;
    }

    public static function setRuntimeToLegacy(array $list) : void{
        self::$runtimeToLegacyIdMap = $list;
    }

    public static function getLegacyToRuntime() : array{
        return self::$legacyToRuntimeIdMap;
    }

    public static function setLegacyToRuntime(array $list) : void{
        self::$legacyToRuntimeIdMap = $list;
    }

}

