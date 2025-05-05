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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\palette\R12ToCurrentBlockMapEntry;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkNbtSerializer;

trait BlockPaletteSerializer{
    public function __construct(){
        //NOOP
    }

    /** @var int[] */
    private static $runtimeToLegacyIdMap = [];
    /** @var int[] */
    private static $legacyToRuntimeIdMap = [];

    /** @var string */
    private static $encodedPalette = null;

    public static function init(): void
    {
        $nbt = new NetworkNbtSerializer();

        $rawData = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL_CBS."/canonical_block_states.nbt");

        $blockStates = array_map(function (TreeRoot $root): CompoundTag {
            return $root->mustGetCompoundTag();
        }, $nbt->readMultiple($rawData));

        $legacyIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/block_id_map.json"), true);

        /** @var R12ToCurrentBlockMapEntry[] $legacyStateMap */
        $legacyStateMap = [];
        $legacyStateMapReader = new NetworkBinaryStream(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL_R12."/r12_to_current_block_map.bin"));

        while (!$legacyStateMapReader->feof()) {
            $id = $legacyStateMapReader->getString();
            $meta = $legacyStateMapReader->getLShort();

            $state = $legacyStateMapReader->getNbtCompoundRoot();
            $legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
        }

        /**
         * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
         */
        $idToStatesMap = [];
        foreach ($blockStates as $runtimeId => $state) {
            $idToStatesMap[$state->getString("name")][] = $runtimeId;
        }

        foreach ($legacyStateMap as $pair) {
            $id = $legacyIdMap[$pair->getId()] ?? null;
            if ($id === null) {
                throw new \RuntimeException("No legacy ID matches " . $pair->getId());
            }
            $data = $pair->getMeta();

            $mappedState = $pair->getBlockState();
            $mappedName = $mappedState->getString("name");

            if (!isset($idToStatesMap[$mappedName])) {
                throw new \RuntimeException("Mapped new state does not appear in network table");
            }

            foreach ($idToStatesMap[$mappedName] as $k) {
                $networkState = $blockStates[$k];
                if ($mappedState->equals($networkState)) {
                    self::registerMapping($k, $id, $data);
                    continue 2;
                }
            }
            throw new \RuntimeException("Mapped new state does not appear in network table");
        }

        $stream = new NetworkBinaryStream();
        $stream->putUnsignedVarInt(count($blockStates));

        foreach ($blockStates as $state) {
            $stream->putString($state->getString("name"));

            $stream->put($nbt->write(new TreeRoot($state->getCompoundTag("states"))));
        }
        self::$encodedPalette = $stream->buffer;
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

