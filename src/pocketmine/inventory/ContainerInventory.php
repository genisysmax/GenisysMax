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

namespace pocketmine\inventory;

use pocketmine\BedrockPlayer;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\ContainerClosePacket as BedrockContainerClose;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\Player;

abstract class ContainerInventory extends BaseInventory{

	public function onOpen(Player $who){
		parent::onOpen($who);

		$pk = new ContainerOpenPacket();
		$pk->windowId = $who->getWindowId($this);
		$pk->type = $this->getType()->getNetworkType();
		$holder = $this->getHolder();
		if($holder instanceof Vector3){
			$pk->x = $holder->getX();
			$pk->y = $holder->getY();
			$pk->z = $holder->getZ();
		}else{
			$pk->x = $pk->y = $pk->z = 0;
		}

		$who->sendDataPacket($pk);
        $who->setCurrentWindowType($this->getType()->getNetworkType());
		$this->sendContents($who);
	}

	public function onClose(Player $who){
		if($who instanceof BedrockPlayer){
			$pk = new BedrockContainerClose();
			$pk->windowId = $who->getWindowId($this);
            $pk->windowType = $this->getType()->getNetworkType();
			$pk->server = $who->getClientClosingWindowId() !== $pk->windowId;
			$who->sendDataPacket($pk);
		}else{
			$pk = new ContainerClosePacket();
			$pk->windowId = $who->getWindowId($this);
			$who->sendDataPacket($pk);
		}

		parent::onClose($who);
	}
}

