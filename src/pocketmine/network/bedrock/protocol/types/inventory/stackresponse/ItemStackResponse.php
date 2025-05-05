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

namespace pocketmine\network\bedrock\protocol\types\inventory\stackresponse;

use pocketmine\network\bedrock\protocol\DataPacket;
use function count;

final class ItemStackResponse{

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/**
	 * @param ItemStackResponseContainerInfo[] $containerInfos
	 */
	public function __construct(
		private int $result,
		private int $requestId,
		private array $containerInfos = []
	){
		if($this->result !== self::RESULT_OK && count($this->containerInfos) !== 0){
			throw new \InvalidArgumentException("Container infos must be empty if rejecting the request");
		}
	}

	public function getResult() : int{ return $this->result; }

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackResponseContainerInfo[] */
	public function getContainerInfos() : array{ return $this->containerInfos; }

	public static function read(DataPacket $in) : self{
		$result = $in->getByte();
		$requestId = $in->getVarInt();
		$containerInfos = [];
		if($result === self::RESULT_OK){
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$containerInfos[] = ItemStackResponseContainerInfo::read($in);
			}
		}
		return new self($result, $requestId, $containerInfos);
	}

	public function write(DataPacket $out) : void{
		$out->putByte($this->result);
		$out->putVarInt($this->requestId);
		if($this->result === self::RESULT_OK){
			$out->putUnsignedVarInt(count($this->containerInfos));
			foreach($this->containerInfos as $containerInfo){
				$containerInfo->write($out);
			}
		}
	}
}


