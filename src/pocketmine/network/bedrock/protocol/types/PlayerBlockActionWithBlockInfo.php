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

namespace pocketmine\network\bedrock\protocol\types;

use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\PlayerActionPacket;
use pocketmine\utils\BinaryStream;

/** This is used for PlayerAuthInput packet when the flags include PERFORM_BLOCK_ACTIONS */
final class PlayerBlockActionWithBlockInfo implements PlayerBlockAction{
	public function __construct(
		private int $actionType,
		private float|int $x,
        private float|int $y,
        private float|int $z,
		private int $face
	){
		if(!self::isValidActionType($actionType)){
			throw new \InvalidArgumentException("Invalid action type for " . self::class);
		}
	}

	public function getActionType() : int{ return $this->actionType; }

	public function getBlockPosition() : Vector3{ return new Vector3($this->x, $this->y, $this->z); }

	public function getFace() : int{ return $this->face; }

	public static function read(BinaryStream $in, int $actionType) : self{
        $x = 0;
        $y = 0;
        $z = 0;
		$in->getBlockPosition($x, $y, $z);
		$face = $in->getVarInt();
		return new self($actionType, $x, $y, $z, $face);
	}

	public function write(BinaryStream $out) : void{
        $out->putBlockPosition($this->x, $this->y, $this->z);
		$out->putVarInt($this->face);
	}

	public static function isValidActionType(int $actionType) : bool{
		return match($actionType){
			PlayerActionPacket::ACTION_ABORT_BREAK,
			PlayerActionPacket::ACTION_START_BREAK,
			PlayerActionPacket::ACTION_CRACK_BREAK,
			PlayerActionPacket::ACTION_PREDICT_DESTROY_BLOCK,
			PlayerActionPacket::ACTION_CONTINUE_DESTROY_BLOCK => true,
			default => false
		};
	}
}

