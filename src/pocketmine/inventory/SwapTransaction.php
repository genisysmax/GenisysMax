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

use pocketmine\item\Item;
use pocketmine\Player;
use function microtime;
use function spl_object_id;

class SwapTransaction implements Transaction{
	/** @var Inventory */
	protected $firstInventory;
	/** @var Inventory */
	protected $secondInventory;
	/** @var int */
	protected $firstSlot;
	/** @var int */
	protected $secondSlot;
	/** @var float */
	protected $creationTime;
	/** @var int */
 	protected $transactionType = Transaction::TYPE_SWAP;
 	/** @var int */
 	protected $failures = 0;
 	/** @var bool */
 	protected $wasSuccessful = false;

 	/**
  	 * @param Inventory $firstInventory
  	 * @param Inventory $secondInventory
  	 * @param int       $firstSlot
  	 * @param int       $secondSlot
 	 * @param int       $transactionType
  	 */
	public function __construct(Inventory $firstInventory, Inventory $secondInventory, int $firstSlot, int $secondSlot, int $transactionType = Transaction::TYPE_SWAP){
		$this->firstInventory = $firstInventory;
		$this->secondInventory = $secondInventory;
		$this->firstSlot = $firstSlot;
		$this->secondSlot = $secondSlot;
		$this->creationTime = microtime(true);
		$this->transactionType = $transactionType;
	}

	public function getCreationTime() : float{
		return $this->creationTime;
	}

	public function getFirstInventory(){
		return $this->firstInventory;
	}

	public function getInventory(){
		return $this->firstInventory;
	}

	public function getSecondInventory(){
		return $this->secondInventory;
	}

	public function getFirstSlot() : int{
		return $this->firstSlot;
	}

	public function getSlot() : int{
		return $this->firstSlot;
	}

	public function getSecondSlot() : int{
		return $this->secondSlot;
	}

	public function getTargetItem() : Item{
		return $this->secondInventory->getItem($this->secondSlot);
	}

 	public function getFailures(){
 		return $this->failures;
 	}

	public function addFailure(){
		$this->failures++;
	}

	public function succeeded(){
		return $this->wasSuccessful;
	}

	public function setSuccess($value = true){
		$this->wasSuccessful = $value;
	}

	public function getTransactionType(){
		return $this->transactionType;
	}

	/**
	 * @param Player $source
	 *
	 * Sends a slot update to inventory viewers
	 * For successful transactions, update non-source viewers (source does not need updating)
	 * For failed transactions, update the source (non-source viewers will see nothing anyway)
	 */
	public function sendSlotUpdate(Player $source){
		if(!($this->firstInventory instanceof TemporaryInventory)){
			$targets = [];
			if($this->wasSuccessful){
				$targets = $this->firstInventory->getViewers();
				unset($targets[spl_object_id($source)]);
			}else{
				$targets = [$source];
			}
			$this->firstInventory->sendSlot($this->firstSlot, $targets);
		}
		if(!($this->secondInventory instanceof TemporaryInventory)){
			$targets = [];
			if($this->wasSuccessful){
				$targets = $this->secondInventory->getViewers();
				unset($targets[spl_object_id($source)]);
			}else{
				$targets = [$source];
			}
			$this->secondInventory->sendSlot($this->secondSlot, $targets);
		}
		if($this->transactionType === Transaction::TYPE_HOTBAR and !$this->wasSuccessful){
			$this->firstInventory->sendContents($source); //force resend hotbar mapping
		}
	}

	/**
	 * @param Player $source
	 *
	 * @return bool
	 *
	 * Handles transaction execution. Returns whether transaction was successful or not.
	 */
	public function execute(Player $source) : bool{
		if($source->isSpectator()){
			return false;
		}
		$firstItem = $this->firstInventory->getItem($this->firstSlot);
		$secondItem = $this->secondInventory->getItem($this->secondSlot);
		$this->firstInventory->setItem($this->firstSlot, $secondItem, false);
		$this->secondInventory->setItem($this->secondSlot, $firstItem, false);

		if($this->transactionType === Transaction::TYPE_HOTBAR){
			$this->firstInventory->sendContents($source); //force resend hotbar
		}

		return true;
	}

}


