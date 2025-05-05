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

namespace pocketmine\network\mcpe;

use pocketmine\entity\object\MinecartAbstract;
use pocketmine\entity\Rideable;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Timings;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\EntityFallPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\RemoveBlockPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter as MCPECommandParameter;
use pocketmine\network\mcpe\protocol\types\InputModeIds;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\Player;
use pocketmine\Server;
use function bin2hex;
use function strlen;
use function substr;

class PlayerNetworkSessionAdapter extends MCPENetworkSession{

	/** @var Server */
	private $server;
	/** @var Player */
	private $player;

	/** @var float */
	private $lastRightClickBlock = 0;
    private $lastTextPacket = 0;
    private $textPacketCnt = 0;
    private $textPacketExceed = 0;

	public function __construct(Server $server, Player $player){
		$this->server = $server;
		$this->player = $player;
	}

    public function handleDataPacket(DataPacket $packet){
        //TODO: Remove this hack once InteractPacket spam issue is fixed
        if($packet->buffer === "\x21\x04\x00"){
            return;
        }
        if(!$this->player->loggedIn and !($packet instanceof LoginPacket or $packet instanceof BatchPacket or $packet instanceof ClientToServerHandshakePacket)){ //Ignore any packets before login
            return;
        }

        $timings = Timings::getReceiveDataPacketTimings($packet);
        $timings->startTiming();

        if(!$packet->wasDecoded and $packet->mustBeDecoded()){ //Allow plugins to decode it
            $packet->decode();
            if(!$packet->feof() and !$packet->mayHaveUnreadBytes()){
                $remains = substr($packet->buffer, $packet->offset);
                $this->server->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains));
            }
        }

        $ev = new DataPacketReceiveEvent($this->player, $packet);
        $ev->call();

        if(!$ev->isCancelled() and $packet->mustBeDecoded() and !$packet->handle($this)){
            $this->server->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->player->getName() . ": 0x" . bin2hex($packet->buffer));
        }

        $timings->stopTiming();
    }

	public function handleLogin(LoginPacket $packet) : bool{
		return $this->player->handleLogin($packet);
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return $this->player->onEncryptionHandshake();
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return $this->player->handleResourcePackClientResponse($packet);
	}

	public function handleText(TextPacket $packet) : bool{
        $time = time();

        if($this->lastTextPacket !== $time){
            $this->textPacketCnt = 0;
        }
        $this->lastTextPacket = $time;

        if(++$this->textPacketCnt >= 5){
            if(++$this->textPacketExceed >= 10){
                $this->server->getNetwork()->blockAddress($this->player->getAddress(), 300);
            }
            return false;
        }

        if(strlen($packet->message) > 200){
            $this->server->getLogger()->warning('big text packet from '.$this->player->getName().' as '.strlen($packet->message).' len with textPacketCnt='.$this->textPacketCnt);
            $this->server->getNetwork()->blockAddress($this->player->getAddress(), 300);

            return false;
        }

        if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

		$this->player->resetCrafting();
		if($packet->type === TextPacket::TYPE_CHAT){
			$this->player->chat($packet->message);
		}

		return true;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		$yaw = fmod($packet->yaw, 360);
		$pitch = fmod($packet->pitch, 360);
		if($yaw < 0){
			$yaw += 360;
		}

		$this->player->setRotation($yaw, $pitch);
		$this->player->updateNextPosition((new Vector3($packet->x, $packet->y, $packet->z))->round(4)->subtract(0, 1.62, 0));

		return true;
	}

	public function handleRemoveBlock(RemoveBlockPacket $packet) : bool{
		if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

		return $this->player->removeBlock(new Vector3($packet->x, $packet->y, $packet->z));
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return $this->player->handleLevelSoundEvent($packet);
	}

	public function handleEntityEvent(EntityEventPacket $packet) : bool{
		return $this->player->handleEntityEvent($packet);
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return $this->player->handleMobEquipment($packet);
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool{
		return true; //Not used
	}

	public function handleInteract(InteractPacket $packet) : bool{
		if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

		$this->player->resetCrafting();

		$target = $this->player->level->getEntity($packet->target);
		if($target === null){
			return false;
		}

		switch($packet->action){
			case InteractPacket::ACTION_LEFT_CLICK: //Attack
                $target = $this->player->level->getEntity($packet->target);
                if ($target instanceof MinecartAbstract) { //TODO: Boat
                    if ($this->player->linkedEntity === $target) {
                        $target->setLinked(0, $this->player);
                    }
                    $target->flagForDespawn();
                }else{
                    $this->player->attackEntity($target);
                }
				break;
			case InteractPacket::ACTION_RIGHT_CLICK:
                if ($target instanceof Rideable) {
                        $this->player->linkEntity($target);
                }else{
                    $this->player->interactEntity($target, $target->asVector3());
                }
				break;
			case InteractPacket::ACTION_LEAVE_VEHICLE:
                if ($target instanceof MinecartAbstract) { //TODO: Boat
                    $target->setLinked(0, $this->player);
                    return true;
                }
                break;
			case InteractPacket::ACTION_MOUSEOVER:
                $text = $target->getInteractButtonText($this->player);
                if ($text !== null) {
                    $this->player->setInteractiveTag($text);
                } else {
                    $this->player->removeInteractiveTag();
                }
				break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown interaction type " . $packet->action . "received from " . $this->player->getName());

				return false;
		}

		return true;
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return $this->player->handleBlockPickRequest($packet);
	}

	public function handleUseItem(UseItemPacket $packet) : bool{
		if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

		$blockVector = new Vector3($packet->x, $packet->y, $packet->z);
		$fVector = new Vector3($packet->fx, $packet->fy, $packet->fz);

		$this->player->resetCrafting();

		if($this->player->getInventory()->getHeldItemSlot() !== $packet->slot){
			$this->player->equipItem($packet->slot, $packet->slot);
		}

		if($packet->face === -1){
			if(microtime(true) - $this->lastRightClickBlock > 0.005){
				$this->player->useItem($blockVector, $fVector, $packet->face, $packet->item);
			}
		}else{
			$this->lastRightClickBlock = microtime(true);
			$this->player->useItem($blockVector, $fVector, $packet->face, $packet->item);
			
			if($this->player->getCurrentInputMode() !== InputModeIds::TOUCHSCREEN){ //this is a very nasty hack
				$this->player->useItem($blockVector, $fVector, -1, $packet->item);
			}
		}

		return true;
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->player->handlePlayerAction($packet);
	}

	public function handleEntityFall(EntityFallPacket $packet) : bool{
		return true; //Not used
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return $this->player->handleAnimate($packet);
	}

	public function handleDropItem(DropItemPacket $packet) : bool{
		return $this->player->handleDropItem($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		if(!$this->player->spawned or $packet->windowId === 0){
			return true;
		}

		$this->player->closeWindow($packet->windowId);

		return true;
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool{
		return $this->player->handleContainerSetSlot($packet);
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
		return $this->player->handleCraftingEvent($packet);
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		$this->player->toggleFlight($packet->isFlying);
		$this->player->toggleNoClip($packet->noClip);

		return true;
	}

	public function handleBlockEntityData(BlockEntityDataPacket $packet) : bool{
		return $this->player->handleBlockEntityData($packet);
	}

	public function handlePlayerInput(PlayerInputPacket $packet) : bool{
		return $this->player->handlePlayerInput($packet);
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return $this->player->handleSetPlayerGameType($packet);
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return $this->player->handleSpawnExperienceOrb($packet);
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
		return $this->player->handleMapInfoRequest($packet);
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		if(!$this->player->loginProcessed){
			return false;
		}
		$this->player->setViewDistance($packet->radius);

		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return $this->player->handleItemFrameDropItem($packet);
	}

	public function handleCommandStep(CommandStepPacket $packet) : bool{
		if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

        $message = $packet->command;
        $command = $this->server->getCommandMap()->getCommand($message);
        if($packet->inputJson !== null and $command !== null and isset($command->getOverloads()[$packet->overload])){
            $overload = $command->getOverloads()[$packet->overload];
            $params = ((isset($overload["input"])) ? $overload["input"]["parameters"] : $overload);
            foreach($params as $arg_data){
                if ($arg_data instanceof CommandParameter) {
                    $arg_data = $arg_data->toPw10Data();
                } else if ($arg_data instanceof MCPECommandParameter) {
                    $arg_data = $arg_data->toData();
                }
                if(isset($packet->inputJson[$arg_data["name"]])){
                    $arg = $packet->inputJson[$arg_data["name"]];
                    $message .= match ($arg_data["type"]) {
                        "target" => " \"" . $arg["rules"][0]["value"] . "\"",
                        "blockpos" => " " . $arg["x"] . " " . $arg["y"] . " " . $arg["z"],
                        "rawtext" => " " . $arg,
                        default => " \"" . $arg . "\"",
                    };
                }
            }
        }

		$message = "/" . $message;
		$this->player->chat($message);

		return true;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool
    {
        return $this->player->handleResourcePackChunkRequest($packet);
    }
}


