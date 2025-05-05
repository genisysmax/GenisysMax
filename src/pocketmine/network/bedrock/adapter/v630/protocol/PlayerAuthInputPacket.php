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

namespace pocketmine\network\bedrock\adapter\v630\protocol;

use pocketmine\network\bedrock\protocol\PlayerActionPacket;
use pocketmine\network\bedrock\protocol\types\ItemInteractionData;
use pocketmine\network\bedrock\protocol\types\itemStack\ItemStackRequest;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\bedrock\protocol\types\PlayMode;

class PlayerAuthInputPacket extends \pocketmine\network\bedrock\protocol\PlayerAuthInputPacket {

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
			(new ItemStackRequest())->read($this);
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

        $this->putLFloat($this->analogMoveVecX);
        $this->putLFloat($this->analogMoveVecZ);
	}
}


