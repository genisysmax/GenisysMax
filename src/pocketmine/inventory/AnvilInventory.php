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
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\network\bedrock\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class AnvilInventory extends TemporaryInventory{
 
 	public const TARGET = 0;
 	public const SACRIFICE = 1;
 	public const RESULT = 2;
 
 
	public function __construct(Position $pos){
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::ANVIL));
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder(){
		return $this->holder;
	}

	public function getResultSlotIndex(){
 		return self::RESULT;
 	}
 
 	public function onRename(Player $player, Item $resultItem) : bool{
 		if(!$resultItem->equals($this->getItem(self::TARGET), true, false, true)){
 			//Item does not match target item. Everything must match except the tags.
 			return false;
 		}
 
 		if($player->getXpLevel() < $resultItem->getRepairCost()){ //Not enough exp
 			return false;
  		}
 		$player->setXpLevel($player->getXpLevel() - $resultItem->getRepairCost());
 		
 		$this->clearAll();
 		if(!$player->getServer()->allowInventoryCheats and !$player->isCreative()){
 			if(!$player->getFloatingInventory()->canAddItem($resultItem)){
 				return false;
 			}
 			$player->getFloatingInventory()->addItem($resultItem);
 		}

 		$player->getLevel()->addSound(new AnvilUseSound($player), [$player]);

 		return true;
 	}
 
 	public function processSlotChange(Transaction $transaction): bool{
 		if($transaction->getSlot() === $this->getResultSlotIndex()){
 			return false;
 		}
 		return true;
 	}
 
 	public function onSlotChange($index, $before, $send){
 		//Do not send anvil slot updates to anyone. This will cause a client crash.
  	}

	public function onClose(Player $who){
		if($who instanceof BedrockPlayer){
			$pk = new ContainerClosePacket();
			$pk->windowId = $who->getWindowId($this);
            $pk->windowType = WindowTypes::ANVIL;
			$pk->server = $who->getClientClosingWindowId() !== $pk->windowId;
			$who->sendDataPacket($pk);
		}

		parent::onClose($who);

		for($i = 0; $i < 2; ++$i){
			$this->getHolder()->getLevel()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem($i));
			$this->clear($i);
		}
	}
}

