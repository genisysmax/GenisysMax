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

use pocketmine\block\BaseRail;
use pocketmine\block\Block;
use pocketmine\block\PoweredRail;
use pocketmine\block\Rail;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;

abstract class MinecartAbstract extends Entity
{

    public const TYPE_NORMAL = 1;
    public const TYPE_CHEST = 2;
    public const TYPE_HOPPER = 3;
    public const TYPE_TNT = 4;

    private array $matrix = [
        [[0, 0, -1], [0, 0, 1]],
        [[-1, 0, 0], [1, 0, 0]],
        [[-1, -1, 0], [1, 0, 0]],
        [[-1, 0, 0], [1, -1, 0]],
        [[0, 0, -1], [0, -1, 1]],
        [[0, -1, -1], [0, 0, 1]],
        [[0, 0, 1], [1, 0, 0]],
        [[0, 0, 1], [-1, 0, 0]],
        [[0, 0, -1], [-1, 0, 0]],
        [[0, 0, -1], [1, 0, 0]]
    ];

    private float $currentSpeed = 0.0;
    private float $maxSpeed = 0.4;

    private float $derailedX = 0.5;
    private float $derailedY = 0.5;
    private float $derailedZ = 0.5;

    private float $flyingX = 0.95;
    private float $flyingY = 0.95;
    private float $flyingZ = 0.95;

    private bool $slowWhenEmpty = true;

    private ?Block $blockInside = null;

    private int $displayOffset = 0;
    private int $hasDisplay = 0;

    public float $width = 0.98;
    public float $height = 0.7;

    protected float $baseOffset = 0.35;

    public float $gravity = 0.5;
    public float $drag = 0.1;

    abstract protected function isRideable(): bool;

    public function initEntity(): void
    {
        parent::initEntity();
        $this->prepareDataProperty();
    }

    public function entityBaseTick($tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        // The damage token
        if ($this->getHealth() < 20) {
            $this->setHealth($this->getHealth() + 1);
        }

        // Entity variables
        $this->lastX = $this->x;
        $this->lastY = $this->y;
        $this->lastZ = $this->z;
        $this->motionY -= 0.03999999910593033;
        $dx = (int)floor($this->x);
        $dy = (int)floor($this->y);
        $dz = (int)floor($this->z);

        // Some hack to check rails
        if (($this->getLevel()->getBlockIdAt($dx, $dy - 1, $dz)) instanceof BaseRail) {
            --$dy;
        }

        $block = $this->getLevel()->getBlock(new Vector3($dx, $dy, $dz));

        // Ensure that the block is a rail
        if ($block instanceof BaseRail) {
            $this->processMovement($dx, $dy, $dz, $block);
            // Activate the minecart/TNT

            //if ($block instanceof ActivatorRail && $block->isActive()) {

            // if ($block instanceof ActivatorRail && false) {
            //$this->activate($dx, $dy, $dz, ($block->getDamage() & 0x8) != 0);
            //}
        } else {
            $this->setFalling();
        }
        $this->checkBlockCollision();

        // Minecart head
        $this->pitch = 0;
        $diffX = $this->lastX - $this->x;
        $diffZ = $this->lastZ - $this->z;
        $yawToChange = $this->yaw;
        if ($diffX * $diffX + $diffZ * $diffZ > 0.001) {
            $yawToChange = (atan2($diffZ, $diffX) * 180 / M_PI);
        }

        // Reverse yaw if yaw is below 0
        if ($yawToChange < 0) {
            // -90-(-90)-(-90) = 90
            $yawToChange -= $yawToChange - $yawToChange;
        }

        $this->setRotation($yawToChange, $this->pitch);
        // No need to onGround or Motion diff! This always have an update

        foreach($this->getLevel()->getNearbyEntities($this->boundingBox->grow(0.2, 0, 0.2), $this) as $entity){
            if ($entity instanceof MinecartAbstract) {
                $entity->applyEntityCollision($this);
            }
        }

        return true;
    }

    public function applyEntityCollision(Entity $entity): void
    {
        $motiveX = $entity->x - $this->x;
        $motiveZ = $entity->z - $this->z;
        $square = $motiveX * $motiveX + $motiveZ * $motiveZ;

        if ($square >= 9.999999747378752E-5) {
            $square = sqrt($square);
            $motiveX /= $square;
            $motiveZ /= $square;
            $next = 1 / $square;

            if ($next > 1) {
                $next = 1;
            }

            $motiveX *= $next;
            $motiveZ *= $next;
            $motiveX *= 0.10000000149011612;
            $motiveZ *= 0.10000000149011612;
            $motiveX *= 1;
            $motiveZ *= 1;
            $motiveX *= 0.5;
            $motiveZ *= 0.5;
            if ($entity instanceof MinecartAbstract) {
                $mine = $entity;
                $desinityX = $mine->x - $this->x;
                $desinityZ = $mine->z - $this->z;
                $vector = (new Vector3($desinityX, 0, $desinityZ))->normalize();
                $vec = (new Vector3(cos((float)$this->yaw * 0.017453292), 0, sin((float)$this->yaw * 0.017453292)))->normalize();
                $desinityXZ = abs($vector->dot($vec));

                if ($desinityXZ < 0.800000011920929) {
                    return;
                }

                $motX = $mine->motionX + $this->motionX;
                $motZ = $mine->motionZ + $this->motionZ;

                $motX /= 2;
                $motZ /= 2;
                $this->motionX *= 0.20000000298023224;
                $this->motionZ *= 0.20000000298023224;
                $this->motionX += $motX - $motiveX;
                $this->motionZ += $motZ - $motiveZ;
                $mine->motionX *= 0.20000000298023224;
                $mine->motionZ *= 0.20000000298023224;
                $mine->motionX += $motX + $motiveX;
                $mine->motionZ += $motZ + $motiveZ;
            } else {
                $this->motionX -= $motiveX;
                $this->motionZ -= $motiveZ;
            }
        }
    }

    public function getMaxSpeed(): float
    {
        return $this->maxSpeed;
    }

    private bool $hasUpdated = false;

    private function setFalling(): void
    {
        $this->motionX = self::clamp($this->motionX, -$this->getMaxSpeed(), $this->getMaxSpeed());
        $this->motionZ = self::clamp($this->motionZ, -$this->getMaxSpeed(), $this->getMaxSpeed());

        if ($this->onGround) {
            $this->motionX *= $this->derailedX;
            $this->motionY *= $this->derailedY;
            $this->motionZ *= $this->derailedZ;
        }

        $this->move($this->motionX, $this->motionY, $this->motionZ);
        if (!$this->onGround) {
            $this->motionX *= $this->flyingX;
            $this->motionY *= $this->flyingY;
            $this->motionZ *= $this->flyingZ;
        }
    }

    public static function clamp(float $val, float $min, float $max): float
    {
        return max($min, min($max, $val));
    }

    private function processMovement(int $dx, int $dy, int $dz, BaseRail $block): void
    {
        $this->fallDistance = 0.0;
        $vector = $this->getNextRail($this->x, $this->y, $this->z);

        $this->y = $dy;
        $isPowered = false;
        $isSlowed = false;

        if ($block instanceof PoweredRail) {
            //$isPowered = $block->isActive();
            //$isSlowed = !$block->isActive();
            $isPowered = false;
            $isSlowed = true;
        }

        switch ($block->getDamage()) {
            case BaseRail::ASCENDING_EAST:
                $this->motionX -= 0.0078125;
                $this->y += 1;
                break;
            case BaseRail::ASCENDING_WEST:
                $this->motionX += 0.0078125;
                $this->y += 1;
                break;
            case BaseRail::ASCENDING_NORTH:
                $this->motionZ += 0.0078125;
                $this->y += 1;
                break;
            case BaseRail::ASCENDING_SOUTH:
                $this->motionZ -= 0.0078125;
                $this->y += 1;
                break;
        }

        $facing = $this->matrix[$block->getDamage()];
        $facing1 = $facing[1][0] - $facing[0][0];
        $facing2 = $facing[1][2] - $facing[0][2];
        $speedOnTurns = sqrt($facing1 * $facing1 + $facing2 * $facing2);
        $realFacing = $this->motionX * $facing1 + $this->motionZ * $facing2;

        if ($realFacing < 0) {
            $facing1 = -$facing1;
            $facing2 = -$facing2;
        }

        $squareOfFame = sqrt($this->motionX * $this->motionX + $this->motionZ * $this->motionZ);

        if ($squareOfFame > 2) {
            $squareOfFame = 2;
        }

        $this->motionX = $squareOfFame * $facing1 / $speedOnTurns;
        $this->motionZ = $squareOfFame * $facing2 / $speedOnTurns;

        $linked = $this->linkedEntity;

        if ($linked instanceof Living) {
            $expectedSpeed = $this->currentSpeed;
            if ($expectedSpeed > 0) {
                // This is a trajectory (Angle of elevation)
                $playerYawNeg = -sin($linked->yaw * M_PI / 180.0);
                $playerYawPos = cos($linked->yaw * M_PI / 180.0);
                $motion = $this->motionX * $this->motionX + $this->motionZ * $this->motionZ;
                if ($motion < 0.01) {
                    $this->motionX += $playerYawNeg * 0.1;
                    $this->motionZ += $playerYawPos * 0.1;

                    $isSlowed = false;
                }
            }
        }

        //http://minecraft.gamepedia.com/Powered_Rail#Rail
        if ($isSlowed) {
            $expectedSpeed = sqrt($this->motionX * $this->motionX + $this->motionZ * $this->motionZ);
            if ($expectedSpeed < 0.03) {
                $this->motionX *= 0;
                $this->motionY *= 0;
                $this->motionZ *= 0;
            } else {
                $this->motionX *= 0.5;
                $this->motionY *= 0;
                $this->motionZ *= 0.5;
            }
        }

        $playerYawNeg = (float)$dx + 0.5 + (float)$facing[0][0] * 0.5;
        $playerYawPos = (float)$dz + 0.5 + (float)$facing[0][2] * 0.5;
        $motion = (float)$dx + 0.5 + (float)$facing[1][0] * 0.5;
        $wallOfFame = (float)$dz + 0.5 + (float)$facing[1][2] * 0.5;

        $facing1 = $motion - $playerYawNeg;
        $facing2 = $wallOfFame - $playerYawPos;
        if ($facing1 == 0) {
            $expectedSpeed = $this->z - (float)$dz;
        } else if ($facing2 == 0) {
            $expectedSpeed = $this->x - (float)$dx;
        } else {
            $motX = $this->x - $playerYawNeg;
            $motZ = $this->z - $playerYawPos;
            $expectedSpeed = ($motX * $facing1 + $motZ * $facing2) * 2;
        }

        $this->x = $playerYawNeg + $facing1 * $expectedSpeed;
        $this->z = $playerYawPos + $facing2 * $expectedSpeed;
        $this->setPosition(new Vector3($this->x, $this->y, $this->z)); // Hehe, my minstake :3

        $motX = $this->motionX;
        $motZ = $this->motionZ;
        if ($this->linkedEntity instanceof Living) {
            $motX *= 0.75;
            $motZ *= 0.75;
        }
        $motX = self::clamp($motX, -$this->getMaxSpeed(), $this->getMaxSpeed());
        $motZ = self::clamp($motZ, -$this->getMaxSpeed(), $this->getMaxSpeed());

        $this->move($motX, 0, $motZ);
        if ($facing[0][1] != 0 && floor($this->x) - $dx == $facing[0][0] && floor($this->z) - $dz == $facing[0][2]) {
            $this->setPosition(new Vector3($this->x, $this->y + (float)$facing[0][1], $this->z));
        } else if ($facing[1][1] != 0 && floor($this->x) - $dx == $facing[1][0] && floor($this->z) - $dz == $facing[1][2]) {
            $this->setPosition(new Vector3($this->x, $this->y + (float)$facing[1][1], $this->z));
        }

        $this->applyDrag();
        $vector1 = $this->getNextRail($this->x, $this->y, $this->z);

        if ($vector1 != null && $vector != null) {
            $d14 = ($vector->y - $vector1->y) * 0.05;

            $squareOfFame = sqrt($this->motionX * $this->motionX + $this->motionZ * $this->motionZ);
            if ($squareOfFame > 0) {
                $this->motionX = $this->motionX / $squareOfFame * ($squareOfFame + $d14);
                $this->motionZ = $this->motionZ / $squareOfFame * ($squareOfFame + $d14);
            }

            $this->setPosition(new Vector3($this->x, $vector1->y, $this->z));
        }

        $floorX = floor($this->x);
        $floorZ = floor($this->z);

        if ($floorX != $dx || $floorZ != $dz) {
            $squareOfFame = sqrt($this->motionX * $this->motionX + $this->motionZ * $this->motionZ);
            $this->motionX = $squareOfFame * (float)($floorX - $dx);
            $this->motionZ = $squareOfFame * (float)($floorZ - $dz);
        }

        if ($isPowered) {
            $newMovie = sqrt($this->motionX * $this->motionX + $this->motionZ * $this->motionZ);

            if ($newMovie > 0.01) {
                $nextMovie = 0.06;

                $this->motionX += $this->motionX / $newMovie * $nextMovie;
                $this->motionZ += $this->motionZ / $newMovie * $nextMovie;
            } else if ($block->getDamage() == Rail::STRAIGHT_NORTH_SOUTH) {
                if ($this->getLevel()->getBlock(new Vector3($dx - 1, $dy, $dz))->isSolid()) {
                    $this->motionX = 0.02;
                } else if ($this->getLevel()->getBlock(new Vector3($dx + 1, $dy, $dz))->isSolid()) {
                    $this->motionX = -0.02;
                }
            } else if ($block->getDamage() == Rail::STRAIGHT_EAST_WEST) {
                if ($this->getLevel()->getBlock(new Vector3($dx, $dy, $dz - 1))->isSolid()) {
                    $this->motionZ = 0.02;
                } else if ($this->getLevel()->getBlock(new Vector3($dx, $dy, $dz + 1))->isSolid()) {
                    $this->motionZ = -0.02;
                }
            }
        }

    }

    private function applyDrag(): void
    {
        if ($this->linkedEntity instanceof Living || !$this->slowWhenEmpty) {
            $this->motionX *= 0.996999979019165;
            $this->motionY *= 0.0;
            $this->motionZ *= 0.996999979019165;
        } else {
            $this->motionX *= 0.9599999785423279;
            $this->motionY *= 0.0;
            $this->motionZ *= 0.9599999785423279;
        }
    }

    private function getNextRail(float $dx, float $dy, float $dz): ?Vector3
    {
        $checkX = floor($dx);
        $checkY = floor($dy);
        $checkZ = floor($dz);

        if ($this->getLevel()->getBlockIdAt((int)$checkX, (int)($checkY - 1), (int)$checkZ) instanceof BaseRail) {
            --$checkY;
        }

        $block = $this->getLevel()->getBlock(new Vector3($checkX, $checkY, $checkZ));

        if ($block instanceof BaseRail) {
            $facing = $this->matrix[$block->getDamage()];
            // Genisys mistake (Doesn't check surrounding more exactly)
            $nextOne = $checkX + 0.5 + $facing[0][0] * 0.5;
            $nextTwo = $checkY + 0.5 + $facing[0][1] * 0.5;
            $nextThree = $checkZ + 0.5 + $facing[0][2] * 0.5;
            $nextFour = $checkX + 0.5 + $facing[1][0] * 0.5;
            $nextFive = $checkY + 0.5 + $facing[1][1] * 0.5;
            $nextSix = $checkZ + 0.5 + $facing[1][2] * 0.5;
            $nextSeven = $nextFour - $nextOne;
            $nextEight = ($nextFive - $nextTwo) * 2;
            $nextMax = $nextSix - $nextThree;

            if ($nextSeven == 0) {
                $rail = $dz - $checkZ;
            } else if ($nextMax == 0) {
                $rail = $dx - $checkX;
            } else {
                $whatOne = $dx - $nextOne;
                $whatTwo = $dz - $nextThree;

                $rail = ($whatOne * $nextSeven + $whatTwo * $nextMax) * 2;
            }

            $dx = $nextOne + $nextSeven * $rail;
            $dy = $nextTwo + $nextEight * $rail;
            $dz = $nextThree + $nextMax * $rail;
            if ($nextEight < 0) {
                ++$dy;
            }

            if ($nextEight > 0) {
                $dy += 0.5;
            }

            return new Vector3($dx, $dy, $dz);
        } else {
            return null;
        }
    }

    public function setCurrentSpeed(float $speed): void
    {
        $this->currentSpeed = $speed;
    }

    private function prepareDataProperty(): void
    {
        $this->setRollingAmplitude(0);
        $this->setRollingDirection(1);
        if ($this->namedtag->hasTag("CustomDisplayTile")) {
            if ($this->namedtag->getByte("CustomDisplayTile") === 1) {
                $display = $this->namedtag->getInt("DisplayTile");
                $offSet = $this->namedtag->getInt("DisplayOffset");
                $this->setHasDisplay(1);
                $this->setDisplayItem($display);
                $this->setDisplayBlockOffset($offSet);
            }
        } else {
            $display = $this->blockInside == null ? 0
                : $this->blockInside->getId()
                | $this->blockInside->getDamage() << 16;
            if ($display == 0) {
                $this->setHasDisplay(0);
                return;
            }
            $this->setHasDisplay(1);
            $this->setDisplayItem($display);
            $this->setDisplayBlockOffset(0);
        }
    }

    public function saveNBT(): void
    {
        parent::saveNBT();
        $hasDisplay = $this->hasDisplay == 1
            || $this->blockInside != null;
        $this->namedtag->setByte("CustomDisplayTile", $hasDisplay ? 1 : 0);
        if ($hasDisplay) {
            $display = $this->blockInside->getId()
                | $this->blockInside->getDamage() << 16;
            $offSet = $this->displayOffset;
            $this->namedtag->setInt("DisplayTile", $display);
            $this->namedtag->setInt("DisplayOffset", $offSet);
        }
    }

    public function setDisplayBlock(?Block $block = null, bool $update = true): bool
    {
        if (!$update) {
            if ($block->isSolid()) {
                $this->blockInside = $block;
            } else {
                $this->blockInside = null;
            }
            return true;
        }
        if ($block != null) {
            if ($block->isSolid()) {
                $this->blockInside = $block;
                $display = $this->blockInside->getId()
                    | $this->blockInside->getDamage() << 16;
                $this->setHasDisplay(1);
                $this->setDisplayItem($display);
                $this->setDisplayBlockOffset(6);
            }
        } else {
            // Set block to air (default).
            $this->blockInside = null;
            $this->setHasDisplay(0);
            $this->setDisplayItem(0);
            $this->setDisplayBlockOffset(0);
        }
        return true;
    }

    public function getDisplayBlock(): ?Block
    {
        return $this->blockInside;
    }

    public function setDisplayBlockOffset(int $offset): void
    {
        $this->displayOffset = $offset;
        $this->propertyManager->setInt(self::DATA_MINECART_DISPLAY_OFFSET, $offset);
    }

    public function getDisplayBlockOffset(): int
    {
        return $this->displayOffset;
    }

    public function isSlowWhenEmpty(): bool
    {
        return $this->slowWhenEmpty;
    }

    public function setSlowWhenEmpty(bool $slow): void
    {
        $this->slowWhenEmpty = $slow;
    }

    public function getDerailedVelocityMod(): Vector3
    {
        return new Vector3($this->derailedX, $this->derailedY, $this->derailedZ);
    }

    public function getFlyingVelocityMod(): Vector3
    {
        return new Vector3($this->flyingX, $this->flyingY, $this->flyingZ);
    }

    public function setFlyingVelocityMod(Vector3 $flying): void
    {
        $this->flyingX = $flying->getX();
        $this->flyingY = $flying->getY();
        $this->flyingZ = $flying->getZ();
    }

    public function setDerailedVelocityMod(Vector3 $derailed): void
    {
        $this->derailedX = $derailed->getX();
        $this->derailedY = $derailed->getY();
        $this->derailedZ = $derailed->getZ();
    }

    public function setMaximumSpeed(int $speed): void
    {
        $this->maxSpeed = $speed;
    }

    public function setRollingAmplitude(int $time): void
    {
        $this->propertyManager->setInt(self::DATA_HURT_TIME, $time);
    }

    public function setRollingDirection(int $direction): void
    {
        $this->propertyManager->setInt(self::DATA_HURT_DIRECTION, $direction);
    }

    public function setHasDisplay(int $has): void
    {
        $this->hasDisplay = $has;
        $this->propertyManager->setByte(self::DATA_MINECART_HAS_DISPLAY, $has);
    }

    public function setDisplayItem(int $block): void
    {
        $this->propertyManager->setInt(self::DATA_MINECART_DISPLAY_BLOCK, $block);
    }
}

