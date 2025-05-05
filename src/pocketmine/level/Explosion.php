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

namespace pocketmine\level;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\TNT;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\explosion\ExplosionExplodeAEvent;
use pocketmine\event\explosion\ExplosionExplodeBEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ExplodePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\tile\Chest;
use pocketmine\tile\Container;
use pocketmine\tile\Tile;

class Explosion{

    private $rays = 16; //Rays
    public $level;
    public $source;
    public $size;
    /**
     * @var Block[]
     */
    public $affectedBlocks = [];
    public $stepLen = 0.3;
    /** @var Entity|Block */
    private $what;

    /** @var ExplosionFilter|null */
    private $explosionFilter;
    /** @var SubChunkIteratorManager */
    private $subChunkHandler;

    public function __construct(Position $center, $size, $what = null){
        $this->level = $center->getLevel();
        $this->source = $center;
        $this->size = max($size, 0);
        $this->what = $what;

        $this->subChunkHandler = new SubChunkIteratorManager($this->level, false);
    }

    /**
     * @return bool
     */
    public function explodeA() : bool{
        if($this->size < 0.1){
            return false;
        }

        $ev = new ExplosionExplodeAEvent($this);
        $ev->call();
        if($ev->isCancelled()){
            return false;
        }

        $vector = new Vector3(0, 0, 0);
        $vBlock = new Position(0, 0, 0, $this->level);

        $currentChunk = null;
        $currentSubChunk = null;

        $mRays = (int) ($this->rays - 1);
        for($i = 0; $i < $this->rays; ++$i){
            for($j = 0; $j < $this->rays; ++$j){
                for($k = 0; $k < $this->rays; ++$k){
                    if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
                        $vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
                        $vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
                        $pointerX = $this->source->x;
                        $pointerY = $this->source->y;
                        $pointerZ = $this->source->z;

                        for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
                            $x = (int) $pointerX;
                            $y = (int) $pointerY;
                            $z = (int) $pointerZ;
                            $vBlock->x = $pointerX >= $x ? $x : $x - 1;
                            $vBlock->y = $pointerY >= $y ? $y : $y - 1;
                            $vBlock->z = $pointerZ >= $z ? $z : $z - 1;

                            if(!$this->subChunkHandler->moveTo($vBlock->x, $vBlock->y, $vBlock->z)){
                                continue;
                            }

                            $blockId = $this->subChunkHandler->currentSubChunk->getBlockId($vBlock->x & 0x0f, $vBlock->y & 0x0f, $vBlock->z & 0x0f);

                            if($blockId !== 0){
                                $blastForce -= (BlockFactory::$blastResistance[$blockId] / 5 + 0.3) * $this->stepLen;
                                if($blastForce > 0){
                                    if(!isset($this->affectedBlocks[$index = Level::blockHash($vBlock->x, $vBlock->y, $vBlock->z)])){
                                        $block = Block::get($blockId, $this->subChunkHandler->currentSubChunk->getBlockData($vBlock->x & 0x0f, $vBlock->y & 0x0f, $vBlock->z & 0x0f), $vBlock);
                                        if($this->explosionFilter === null or $this->explosionFilter->canAffectBlock($block)){
                                            $this->affectedBlocks[$index] = $block;
                                        }
                                    }
                                }
                            }

                            $pointerX += $vector->x;
                            $pointerY += $vector->y;
                            $pointerZ += $vector->z;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function explodeB() : bool{
        $ev = new ExplosionExplodeBEvent($this);
        $ev->call();
        if($ev->isCancelled()){
            return false;
        }

        $send = [];
        $updateBlocks = [];

        $source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $yield = (1 / $this->size) * 100;

        if($this->what instanceof Entity){
            $ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield);
            $ev->call();
            if($ev->isCancelled()){
                return false;
            }else{
                $yield = $ev->getYield();
                $this->affectedBlocks = $ev->getBlockList();
            }
        }

        $this->affectEntities();

        $air = Item::get(Item::AIR);

        foreach($this->affectedBlocks as $block){
            $yieldDrops = false;

            if($block instanceof TNT){
                $block->ignite(mt_rand(10, 30));
            }elseif($yieldDrops = (mt_rand(0, 100) < $yield)){
                foreach($block->getDrops($air) as $drop){
                    $this->level->dropItem($block->add(0.5, 0.5, 0.5), $drop);
                }
            }

            $this->level->setBlockIdAt($block->x, $block->y, $block->z, 0);
            $this->level->setBlockDataAt($block->x, $block->y, $block->z, 0);

            $t = $this->level->getTileAt($block->x, $block->y, $block->z);
            if($t instanceof Tile){
                if($yieldDrops and $t instanceof Container){
                    if($t instanceof Chest){
                        $t->unpair();
                    }

                    foreach($t->getInventory()->getContents() as $drop){
                        $this->level->dropItem($t->add(0.5, 0.5, 0.5), $drop);
                    }
                }

                $t->close();
            }

            $pos = new Vector3($block->x, $block->y, $block->z);

            for($side = 0; $side <= 5; $side++){
                $sideBlock = $pos->getSide($side);
                if(!$this->level->isInWorld($sideBlock->x, $sideBlock->y, $sideBlock->z)){
                    continue;
                }
                if(!isset($this->affectedBlocks[$index = Level::blockHash($sideBlock->x, $sideBlock->y, $sideBlock->z)]) and !isset($updateBlocks[$index])){
                    $ev = new BlockUpdateEvent($this->level->getBlockAt($sideBlock->x, $sideBlock->y, $sideBlock->z));
                    $ev->call();
                    if(!$ev->isCancelled()){
                        $ev->getBlock()->onNearbyBlockChange();
                    }
                    $updateBlocks[$index] = true;
                }
            }
            $send[] = new Vector3($block->x - $source->x, $block->y - $source->y, $block->z - $source->z);
        }

        $pk = new ExplodePacket();
        $pk->x = $this->source->x;
        $pk->y = $this->source->y;
        $pk->z = $this->source->z;
        $pk->radius = $this->size;
        $pk->records = $send;
        $this->level->addChunkPacket($source->x >> 4, $source->z >> 4, $pk);

        $this->level->addParticle(new HugeExplodeSeedParticle($source));
        $this->level->broadcastLevelSoundEvent($source, LevelSoundEventPacket::SOUND_EXPLODE);

        return true;
    }

    /**
     * @return Entity[]
     */
    public function affectEntities() : array{
        $explosionSize = $this->size * 2;
        $minX = Math::floorFloat($this->source->x - $explosionSize - 1);
        $maxX = Math::ceilFloat($this->source->x + $explosionSize + 1);
        $minY = Math::floorFloat($this->source->y - $explosionSize - 1);
        $maxY = Math::ceilFloat($this->source->y + $explosionSize + 1);
        $minZ = Math::floorFloat($this->source->z - $explosionSize - 1);
        $maxZ = Math::ceilFloat($this->source->z + $explosionSize + 1);

        $explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
        $entities = [];
        foreach($list as $entity){
            $distance = $entity->distance($this->source) / $explosionSize;

            if($distance <= 1 and ($this->explosionFilter === null or $this->explosionFilter->canAffectEntity($entity))){
                $motion = $entity->subtract($this->source)->normalize();

                $impact = (1 - $distance);

                $damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

                if($this->what instanceof Entity){
                    if($this->what->y > $entity->y) {
                        $ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage * 8);
                    } else {
                        $ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
                    }
                    $entity->attack($ev);
                }
                if($this->what instanceof Block){
                    $ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }else{
                    $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }

                $entity->attack($ev);
                $entity->setMotion($motion->multiply($impact));
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    /**
     * @return ExplosionFilter|null
     */
    public function getExplosionFilter() : ?ExplosionFilter{
        return $this->explosionFilter;
    }

    /**
     * @param ExplosionFilter|null $explosionFilter
     */
    public function setExplosionFilter(?ExplosionFilter $explosionFilter) : void{
        $this->explosionFilter = $explosionFilter;
    }

    /**
     * @return Block|Entity
     */
    public function getWhat(){
        return $this->what;
    }
}

