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

namespace pocketmine\entity\object;

use pocketmine\BedrockPlayer;
use pocketmine\block\Block;
use pocketmine\block\Fallable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\Position;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\PacketTranslator;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\protocol\SetActorDataPacket as BedrockSetActorDataPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\Player;
use function is_array;

class FallingBlock extends Entity{
	public const NETWORK_ID = self::FALLING_BLOCK;

	public float $width = 0.98;
	public float $height = 0.98;

	protected float $baseOffset = 0.49;

	public float $gravity = 0.04;
	public float $drag = 0.02;

    /** @var Block */
    protected Block $block;

	public bool $canCollide = false;

	protected function initEntity() : void{
		parent::initEntity();

        $blockId = 0;
		if($this->namedtag->hasTag("TileID", IntTag::class)){
			$blockId = $this->namedtag->getInt("TileID");
		}elseif($this->namedtag->hasTag("Tile", IntTag::class)){
			$blockId = $this->namedtag->getInt("Tile");
			$this->namedtag->setInt("TileID", $blockId);
			$this->namedtag->removeTag("Tile");
		}

        $damage = 0;
		if($this->namedtag->hasTag("Data", ByteTag::class)){
			$damage = $this->namedtag->getByte("Data");
		}

		if($blockId === 0){
			$this->close();
			return;
		}


        $this->block = Block::get($blockId, $damage);
        $this->propertyManager->setInt(self::DATA_VARIANT, ($this->getBlock() | ($this->getDamage() << 8)));
	}

	public function canCollideWith(Entity $entity): bool
    {
		return false;
	}

    public function canBeMovedByCurrents() : bool{
        return false;
    }

	public function attack(EntityDamageEvent $source): void
    {
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($source);
		}
	}

    public function fall(float $fallDistance) : void{
        if($this->block instanceof Fallable){
            $this->block = $this->block->onEndFalling($this);
        }
    }

	public function entityBaseTick($tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->isFlaggedForDespawn()) {
            $pos = Position::fromObject($this->add(-$this->width / 2, $this->height, -$this->width / 2)->floor(), $this->getLevel());

            $this->block->position($pos);

            $blockTarget = null;
            if($this->block instanceof Fallable){
                $blockTarget = $this->block->tickFalling();
            }

            if ($this->onGround or $blockTarget !== null) {
                $this->flagForDespawn();

                $pos = $this->add(-$this->width / 2, $this->height, -$this->width / 2)->floor();

                $block = $this->level->getBlockAt($pos->x, $pos->y, $pos->z);

                if ($block->getId() > 0 and $block->isTransparent() and !$block->canBeReplaced()) {
                    //FIXME: anvils are supposed to destroy torches
                    $this->getLevel()->dropItem($this, ItemItem::get($this->getBlock(), $this->getDamage(), 1));
                } else {
                    $ev = new EntityBlockChangeEvent($this, $block, Block::get($this->getBlock(), $this->getDamage()));
                    $ev->call();
                    if (!$ev->isCancelled()) {
                        $this->getLevel()->setBlock($pos, $ev->getTo(), true);
                    }
                }
                $hasUpdate = true;
            }
        }
        return $hasUpdate;
    }

    public function getBlock() : int{
        return $this->block->getId();
    }

    public function getDamage() : int{
        return $this->block->getDamage();
    }

	public function saveNBT(): void
    {
		parent::saveNBT();
		$this->namedtag->setInt("TileID", $this->getBlock());
		$this->namedtag->setByte("Data", $this->getDamage());
	}

	protected function sendSpawnPacket(Player $player) : void{
		$pk = new AddEntityPacket();
		$pk->type = static::NETWORK_ID;
		$pk->entityRuntimeId = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->propertyManager->getAll();
		if($player instanceof BedrockPlayer and isset($pk->metadata[self::DATA_VARIANT])){
			$adapter = ProtocolAdapterFactory::get($player->getProtocolVersion());
			if($adapter !== null){
				$pk->metadata[self::DATA_VARIANT][1] = $adapter->translateBlockId(BlockPalette::getRuntimeFromLegacyId($this->getBlock(), $this->getDamage()));
			}else{
				$pk->metadata[self::DATA_VARIANT][1] = BlockPalette::getRuntimeFromLegacyId($this->getBlock(), $this->getDamage());
			}
		}

		$player->sendDataPacket($pk);
	}

	/**
	 * @param Player[]|Player $player
	 * @param array           $data Properly formatted entity data, defaults to everything
	 */
	public function sendData($player, array $data = null){
		if(!is_array($player)){
			$player = [$player];
		}

		$bedrockPackets = [];
		foreach($player as $p){
			if($p !== $this and $p instanceof BedrockPlayer){
				$bedrockPackets[$p->getProtocolVersion()] = null;
			}
		}
		if($this instanceof BedrockPlayer){
			$bedrockPackets[$this->getProtocolVersion()] = null;
		}

		$pk = new SetEntityDataPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $data ?? $this->propertyManager->getAll();

		$bk = new BedrockSetActorDataPacket();
		$bk->actorRuntimeId = $this->getId();
		$bk->metadata = PacketTranslator::translateMetadata($pk->metadata);

		foreach($bedrockPackets as $protocol => &$bbk){
			$bbk = clone $bk;
			if(isset($bk->metadata[self::DATA_VARIANT])){
				$adapter = ProtocolAdapterFactory::get($protocol);
				if($adapter !== null){
					$bbk->metadata[self::DATA_VARIANT][1] = $adapter->translateBlockId(BlockPalette::getRuntimeFromLegacyId($this->getBlock(), $this->getDamage()));
				}else{
					$bbk->metadata[self::DATA_VARIANT][1] = BlockPalette::getRuntimeFromLegacyId($this->getBlock(), $this->getDamage());
				}
			}
		}

		foreach($player as $p){
			if($p === $this){
				continue;
			}
			$p->sendDataPacket($p instanceof BedrockPlayer ? clone $bedrockPackets[$p->getProtocolVersion()] : clone $pk);
		}

		if($this instanceof Player){
			$this->sendDataPacket($this instanceof BedrockPlayer ? $bedrockPackets[$p->getProtocolVersion()] : $pk);
		}
	}
}


