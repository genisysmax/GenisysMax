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

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\types\ItemInteractionData;
use pocketmine\network\bedrock\protocol\types\itemStack\ItemStackRequest;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\bedrock\protocol\types\PlayMode;
use pocketmine\network\NetworkSession;

class PlayerAuthInputPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	/** Pressing the "fly up" key when using touch. */
	public const INPUT_ASCEND = 0;
	/** Pressing the "fly down" key when using touch. */
	public const INPUT_DESCEND = 1;
	/** Pressing (and optionally holding) the jump key (while not flying). */
	public const INPUT_NORTH_JUMP = 2;
	/** Pressing (and optionally holding) the jump key (including while flying). */
	public const INPUT_JUMP_DOWN = 3;
	/** Pressing (and optionally holding) the sprint key (typically the CTRL key). Does not include double-pressing the forward key. */
	public const INPUT_SPRINT_DOWN = 4;
	/** Pressing (and optionally holding) the fly button ONCE when in flight mode when using touch. This has no obvious use. */
	public const INPUT_CHANGE_HEIGHT = 5;
	/** Pressing (and optionally holding) the jump key (including while flying), and also auto-jumping. */
	public const INPUT_JUMPING = 6;
	/** Auto-swimming upwards while pressing forwards with auto-jump enabled. */
	public const INPUT_AUTO_JUMPING_IN_WATER = 7;
	/** Sneaking, and pressing the "fly down" key or "sneak" key (including while flying). */
	public const INPUT_SNEAKING = 8;
	/** Pressing (and optionally holding) the sneak key (including while flying). This includes when the sneak button is toggled ON with touch controls. */
	public const INPUT_SNEAK_DOWN = 9;
	/** Pressing the forward key (typically W on keyboard). */
	public const INPUT_UP = 10;
	/** Pressing the backward key (typically S on keyboard). */
	public const INPUT_DOWN = 11;
	/** Pressing the left key (typically A on keyboard). */
	public const INPUT_LEFT = 12;
	/** Pressing the right key (typically D on keyboard). */
	public const INPUT_RIGHT = 13;
	/** Pressing the ↖ key on touch. */
	public const INPUT_UP_LEFT = 14;
	/** Pressing the ↗ key on touch. */
	public const INPUT_UP_RIGHT = 15;
	/** Client wants to go upwards. Sent when Ascend or Jump is pressed, irrespective of whether flight is enabled. */
	public const INPUT_WANT_UP = 16;
	/** Client wants to go downwards. Sent when Descend or Sneak is pressed, irrespective of whether flight is enabled. */
	public const INPUT_WANT_DOWN = 17;
	/** Same as "want up" but slow. Only usable with controllers at the time of writing. Triggered by pressing the right joystick by default. */
	public const INPUT_WANT_DOWN_SLOW = 18;
	/** Same as "want down" but slow. Only usable with controllers at the time of writing. Not bound to any control by default. */
	public const INPUT_WANT_UP_SLOW = 19;
	/** Unclear usage, during testing it was only seen in conjunction with SPRINT_DOWN. NOT sent while actually sprinting. */
	public const INPUT_SPRINTING = 20;
	/** Ascending scaffolding. Note that this is NOT sent when climbing ladders. */
	public const INPUT_ASCEND_BLOCK = 21;
	/** Descending scaffolding. */
	public const INPUT_DESCEND_BLOCK = 22;
	/** Toggling the sneak button on touch when the button enters the "enabled" state. */
	public const INPUT_SNEAK_TOGGLE_DOWN = 23;
	/** Unclear use. Sent continually on touch controls, irrespective of whether the player is actually sneaking or not. */
	public const INPUT_PERSIST_SNEAK = 24;
	public const INPUT_START_SPRINTING = 25;
	public const INPUT_STOP_SPRINTING = 26;
	public const INPUT_START_SNEAKING = 27;
	public const INPUT_STOP_SNEAKING = 28;
	public const INPUT_START_SWIMMING = 29;
	public const INPUT_STOP_SWIMMING = 30;
	/** Initiating a new jump. Sent every time the client leaves the ground due to jumping, including auto jumps. */
	public const INPUT_START_JUMPING = 31;
	public const INPUT_START_GLIDING = 32;
	public const INPUT_STOP_GLIDING = 33;
	public const INPUT_PERFORM_ITEM_INTERACTION = 34;
	public const INPUT_PERFORM_BLOCK_ACTIONS = 35;
	public const INPUT_PERFORM_ITEM_STACK_REQUEST = 36;
	public const INPUT_HANDLED_TELEPORT = 37;
	public const INPUT_EMOTING = 38;
	/** Left-clicking the air. In vanilla, this generates an ATTACK_NODAMAGE sound and does nothing else. */
	public const INPUT_MISSED_SWING = 39;
	public const INPUT_START_CRAWLING = 40;
	public const INPUT_STOP_CRAWLING = 41;
    public const START_FLYING = 42;
    public const STOP_FLYING = 43;
    public const ACK_ACTOR_DATA = 44;
    public const IN_CLIENT_PREDICTED_VEHICLE = 45;

	public const INTERACTION_TOUCH = 0;
	public const INTERACTION_CROSSHAIR = 1;
	public const INTERACTION_CLASSIC = 2; //???

	/** @var float */
	public $yaw;
	/** @var float */
	public $pitch;
	/** @var Vector3 */
	public $playerMovePosition;
	/** @var Vector2 */
	public $motion;
	/** @var float */
	public $headRotation;
	/** @var int */
	public $inputFlags;
	/** @var int */
	public $inputMode;
	/** @var int */
	public $playMode;
	/** @var int */
	public $interactionMode;
	/** @var Vector3|null */
	public $vrGazeDirection;
	/** @var int */
	public $tick;
	/** @var Vector3 */
	public $delta;
	/** @var float */
	public $analogMoveVecX;
	/** @var float */
	public $analogMoveVecZ;
	/** @var ItemInteractionData|null */
	public $itemInteractionData = null;
    /** @var ItemStackRequest|null */
    public $itemStackRequest = null;
	public $blockActions = null;
    public ?int $clientPredictedVehicleActorUniqueId = null;
    private ?PlayerAuthInputVehicleInfo $vehicleInfo = null;

	public function decodePayload(){
		$this->yaw = $this->getLFloat();
		$this->pitch = $this->getLFloat();
		$this->playerMovePosition = $this->getVector3();
		$this->motion = $this->getVector2();
		$this->headRotation = $this->getLFloat();
		$this->inputFlags = $this->getUnsignedVarLong();
		$this->inputMode = $this->getUnsignedVarInt();
		$this->playMode = $this->getUnsignedVarInt();
		$this->interactionMode = $this->getUnsignedVarInt();
		if($this->playMode === PlayMode::VR){
			$this->vrGazeDirection = $this->getVector3();
		}
		$this->tick = $this->getUnsignedVarLong();
		$this->delta = $this->getVector3();

		if ($this->getInputFlag(PlayerAuthInputPacket::INPUT_PERFORM_ITEM_INTERACTION)) {
			$this->itemInteractionData = ItemInteractionData::read($this);
		}

		if($this->getInputFlag(PlayerAuthInputPacket::INPUT_PERFORM_ITEM_STACK_REQUEST)){
            $itemStack = new ItemStackRequest;
            $itemStack->read($this);
            $this->itemStackRequest = $itemStack;
		}

        if($this->getInputFlag(PlayerAuthInputPacket::IN_CLIENT_PREDICTED_VEHICLE)){
            $this->vehicleInfo = PlayerAuthInputVehicleInfo::read($this);
            $this->clientPredictedVehicleActorUniqueId = $this->vehicleInfo->getPredictedVehicleActorUniqueId(); //TODO: ?
        }

		if ($this->getInputFlag(PlayerAuthInputPacket::INPUT_PERFORM_BLOCK_ACTIONS)) {
			$this->blockActions = [];
			$max = $this->getVarInt();
			for ($i = 0; $i < $max; ++$i) {
				$actionType = $this->getVarInt();
				$this->blockActions[] = match (true) {
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($this, $actionType),
					$actionType === PlayerActionPacket::ACTION_STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new \InvalidArgumentException("Unexcepted block action type $actionType")
				};
			}
		}

		$this->analogMoveVecX = $this->getLFloat();
		$this->analogMoveVecZ = $this->getLFloat();
	}

	public function encodePayload(){
        $this->putLFloat($this->pitch);
        $this->putLFloat($this->yaw);
        $this->putVector3($this->playerMovePosition);
        $this->putVector2($this->motion);
        $this->putLFloat($this->headRotation);
        $this->putUnsignedVarLong($this->inputFlags);
        $this->putUnsignedVarInt($this->inputMode);
        $this->putUnsignedVarInt($this->playMode);
        $this->putUnsignedVarInt($this->interactionMode);
        if($this->playMode === PlayMode::VR){
            assert($this->vrGazeDirection !== null);
            $this->putVector3($this->vrGazeDirection);
        }
        $this->putUnsignedVarLong($this->tick);
        $this->putVector3($this->delta);
        if($this->itemInteractionData !== null){
            $this->itemInteractionData->write($this);
        }
        if($this->itemStackRequest !== null){
            $this->itemStackRequest->write($this);
        }

        (new ItemStackRequest())->read($this);

        if($this->blockActions !== null){
            $this->putVarInt(count($this->blockActions));
            foreach($this->blockActions as $blockAction){
                $this->putVarInt($blockAction->getActionType());
                $blockAction->write($this);
            }
        }
        if($this->vehicleInfo !== null){
            $this->vehicleInfo->write($this);
        }
        $this->putLFloat($this->analogMoveVecX);
        $this->putLFloat($this->analogMoveVecZ);
	}

	public function getInputFlag(int $flag) : bool{
		return ($this->inputFlags & (1 << $flag)) !== 0;
	}

	public function setInputFlag(int $flag, bool $value) : void{
		if($value){
			$this->inputFlags |= (1 << $flag);
		}else{
			$this->inputFlags &= ~(1 << $flag);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerAuthInput($this);
	}
}


