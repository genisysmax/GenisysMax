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

namespace pocketmine\network\bedrock;

use pocketmine\BedrockPlayer;
use pocketmine\entity\object\MinecartAbstract;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Timings;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\adapter\v527\Protocol527Adapter;
use pocketmine\network\bedrock\protocol\ActorEventPacket;
use pocketmine\network\bedrock\protocol\AdventureSettingsPacket;
use pocketmine\network\bedrock\protocol\AnimatePacket;
use pocketmine\network\bedrock\protocol\BlockActorDataPacket;
use pocketmine\network\bedrock\protocol\BlockPickRequestPacket;
use pocketmine\network\bedrock\protocol\ClientToServerHandshakePacket;
use pocketmine\network\bedrock\protocol\CommandRequestPacket;
use pocketmine\network\bedrock\protocol\ContainerClosePacket;
use pocketmine\network\bedrock\protocol\ContainerOpenPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\EmotePacket;
use pocketmine\network\bedrock\protocol\InteractPacket;
use pocketmine\network\bedrock\protocol\InventoryTransactionPacket;
use pocketmine\network\bedrock\protocol\ItemFrameDropItemPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\LoginPacket;
use pocketmine\network\bedrock\protocol\MapInfoRequestPacket;
use pocketmine\network\bedrock\protocol\MobEquipmentPacket;
use pocketmine\network\bedrock\protocol\ModalFormResponsePacket;
use pocketmine\network\bedrock\protocol\MovePlayerPacket;
use pocketmine\network\bedrock\protocol\PacketViolationWarningPacket;
use pocketmine\network\bedrock\protocol\PlayerActionPacket;
use pocketmine\network\bedrock\protocol\PlayerAuthInputPacket;
use pocketmine\network\bedrock\protocol\PlayerInputPacket;
use pocketmine\network\bedrock\protocol\PlayerSkinPacket;
use pocketmine\network\bedrock\protocol\RequestAbilityPacket;
use pocketmine\network\bedrock\protocol\RequestChunkRadiusPacket;
use pocketmine\network\bedrock\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\bedrock\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\bedrock\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\bedrock\protocol\RespawnPacket;
use pocketmine\network\bedrock\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\bedrock\protocol\SetPlayerGameTypePacket;
use pocketmine\network\bedrock\protocol\TextPacket;
use pocketmine\network\bedrock\protocol\types\inventory\ContainerIds;
use pocketmine\network\bedrock\protocol\types\inventory\WindowTypes;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\bedrock\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\Server;
use UnexpectedValueException;
use function bin2hex;
use function is_bool;
use function strlen;
use function substr;

class PlayerNetworkSessionAdapter extends BedrockNetworkSession{

	private const MAX_FORM_RESPONSE_DEPTH = 2;

    private $lastTextPacket = 0;
    private $textPacketCnt = 0;
    private $textPacketExceed = 0;
	/** @var ?int */
	private $lastPlayerAuthInputFlags = null;
	/** @var ?float */
	private $lastPlayerAuthInputPitch = null;
	/** @var ?float */
	private $lastPlayerAuthInputYaw = null;
	/** @var ?Position */
	private $lastPlayerAuthInputPosition = null;

	public function __construct(
		private Server $server,
		private BedrockPlayer $player
	){}

    public function handleDataPacket(DataPacket $packet){
        //TODO: Remove this hack once InteractPacket spam issue is fixed
        if(strlen($packet->buffer) > 1 and substr($packet->buffer, 0, 2) === "\x21\x04"){
            return;
        }

        if(
            !$this->player->loggedIn and
            !$this->player->awaitingEncryptionHandshake and
            !($packet instanceof LoginPacket or $packet instanceof RequestNetworkSettingsPacket)
        ){ //Ignore any packets before login and network settings
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

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		if ($packet->cancelReason !== NULL) {
			return $this->player->onFormSubmit($packet->formId, null);
		} elseif ($packet->formData !== null){
			try{
				$responseData = json_decode($packet->formData, true, self::MAX_FORM_RESPONSE_DEPTH, JSON_THROW_ON_ERROR);
			}catch(\JsonException $e){
				$this->server->getLogger()->logException($e);
				return false;
			}
			return $this->player->onFormSubmit($packet->formId, $responseData);
		}else{
			$this->server->getLogger()->logException(throw new \RuntimeException("Expected either formData or cancelReason to be set in ModalFormResponsePacket"));
		}
	}

	private function resolveOnOffInputFlags(int $inputFlags, int $startFlag, int $stopFlag) : ?bool{
		$enabled = ($inputFlags & (1 << $startFlag)) !== 0;
		$disabled = ($inputFlags & (1 << $stopFlag)) !== 0;
		if($enabled !== $disabled){
			return $enabled;
		}
		//neither flag was set, or both were set
		return null;
	}

	public function handlePlayerAuthInput(PlayerAuthInputPacket $packet) : bool
    {
        $rawPos = $packet->playerMovePosition;
        $rawYaw = $packet->yaw;
        $rawPitch = $packet->pitch;

        foreach ([$rawPos->x, $rawPos->y, $rawPos->z, $rawYaw, $packet->headRotation, $rawPitch] as $float) {
            if (is_infinite($float) || is_nan($float)) {
                return false;
            }
        }

        if ($rawYaw !== $this->lastPlayerAuthInputYaw || $rawPitch !== $this->lastPlayerAuthInputPitch) {
            $this->lastPlayerAuthInputYaw = $rawYaw;
            $this->lastPlayerAuthInputPitch = $rawPitch;

            $yaw = fmod($rawYaw, 360);
            $pitch = fmod($rawPitch, 360);
            if ($yaw < 0) {
                $yaw += 360;
            }

            $this->player->setRotation($yaw, $pitch);
        }

        $hasMoved = $this->lastPlayerAuthInputPosition === null || !$this->lastPlayerAuthInputPosition->equals($rawPos);
        $newPos = $rawPos->subtract(0, 1.62, 0)->round(4);

        if ($hasMoved) {
            $this->player->updateNextPosition($newPos);
        }

        $inputFlags = $packet->inputFlags;
        if ($inputFlags !== $this->lastPlayerAuthInputFlags) {
            $this->lastPlayerAuthInputFlags = $inputFlags;

            $sneaking = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputPacket::INPUT_START_SNEAKING, PlayerAuthInputPacket::INPUT_STOP_SNEAKING);
            //$swimming = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputPacket::START_SWIMMING, PlayerAuthInputPacket::STOP_SWIMMING);
            $sprinting = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputPacket::INPUT_START_SPRINTING, PlayerAuthInputPacket::INPUT_STOP_SPRINTING);
            $gliding = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputPacket::INPUT_START_GLIDING, PlayerAuthInputPacket::INPUT_STOP_GLIDING);

            $mismatch =
                ($gliding !== null && !$this->player->toggleGlide($gliding)) |
                ($sneaking !== null && !$this->player->toggleSneak($sneaking)) |
                //($swimming !== null && !$this->player->toggleSwin($swimming)) |
                ($sprinting !== null && !$this->player->toggleSprint($sprinting));

            if ((bool)$mismatch) {
                $this->player->sendData($this->player);
            }

            if ($packet->getInputFlag(PlayerAuthInputPacket::INPUT_START_JUMPING)) {
                $this->player->toggleGlide(false);
                $this->player->jump();
            }
        }

        //TODO: block actions
        $blockActions = $packet->blockActions;

        $packetHandled = true;
        if ($blockActions !== null) {
            if (count($blockActions) > 100) {
                return false;
            }

            foreach ($blockActions as $k => $blockAction) {
                $actionHandled = false;
                if ($blockAction instanceof PlayerBlockActionStopBreak) {
                    $actionHandled = $this->player->handleBedrockPlayerActionFromData($blockAction->getActionType(), new Vector3(0, 0, 0), Vector3::SIDE_DOWN);
                } elseif ($blockAction instanceof PlayerBlockActionWithBlockInfo) {
                    $actionHandled = $this->player->handleBedrockPlayerActionFromData($blockAction->getActionType(), $blockAction->getBlockPosition(), $blockAction->getFace());
                }

                if (!$actionHandled) {
                    $packetHandled = false;
                }
            }
        }

        return $packetHandled;
    }

	public function handleLogin(LoginPacket $packet) : bool{
		return $this->player->handleBedrockLogin($packet);
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return $this->player->onEncryptionHandshake();
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return $this->player->handleBedrockResourcePackClientResponse($packet);
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		return $this->player->handleBedrockResourcePackChunkRequest($packet);
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		if(!$this->player->loginProcessed){
			return false;
		}
		$this->player->setViewDistance($packet->radius);

		return true;
	}

	public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet) : bool{
		$this->player->doFirstSpawn();

		return true;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		$yaw = fmod($packet->yaw, 360);
		$pitch = fmod($packet->pitch, 360);
		if($yaw < 0){
			$yaw += 360;
		}

		$this->player->setRotation($yaw, $pitch);
		$this->player->updateNextPosition($packet->position->round(4)->subtract(0, 1.62, 0));

		return true;
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		return $this->player->handleInventoryTransaction($packet);
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return $this->player->handleBedrockLevelSoundEvent($packet);
	}

	public function handleActorEvent(ActorEventPacket $packet) : bool{
		return $this->player->handleActorEvent($packet);
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return $this->player->handleBedrockMobEquipment($packet);
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return $this->player->handleBedrockBlockPickRequest($packet);
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return $this->player->handleBedrockAnimate($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		if(!$this->player->spawned){
			return true;
		}

		$pk = new ContainerClosePacket();
		$pk->windowId = $packet->windowId;
        $pk->windowType = $this->player->getCurrentWindowType();
		$pk->server = false;
		$this->player->sendDataPacket($pk);

		$this->player->newInventoryClose($packet->windowId);

		if($packet->windowId !== ContainerIds::INVENTORY){
			$this->player->setClientClosingWindowId($packet->windowId);
			$this->player->closeWindow($packet->windowId);
			$this->player->setClientClosingWindowId(-1);
		}

		return true;
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		if($this->player->getProtocolVersion() < Protocol527Adapter::PROTOCOL_VERSION){
			$this->player->toggleFlight($packet->getFlag(AdventureSettingsPacket::FLYING));
			$this->player->toggleNoClip($packet->getFlag(AdventureSettingsPacket::NO_CLIP));

			return true;
		}

		return false;
	}

	public function handleRequestAbility(RequestAbilityPacket $packet) : bool{
		if($packet->abilityId === RequestAbilityPacket::ABILITY_FLYING){
			if(!is_bool($packet->abilityValue)){
				throw new UnexpectedValueException("Flying ability value should always be bool");
			}
			$this->player->toggleFlight($packet->abilityValue);
			return true;
		}

		if($packet->abilityId === RequestAbilityPacket::ABILITY_NOCLIP){
			if(!is_bool($packet->abilityValue)){
				throw new UnexpectedValueException("No-clip ability value should always be bool");
			}
			$this->player->toggleNoClip($packet->abilityValue);
			return true;
		}

		return false;
	}

	public function handleBlockActorData(BlockActorDataPacket $packet) : bool{
		return $this->player->handleBedrockBlockActorData($packet);
	}

    public function handlePlayerInput(PlayerInputPacket $packet) : bool{
        return $this->player->handleBedrockPlayerInput($packet);
    }

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return $this->player->handleBedrockSetPlayerGameType($packet);
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return $this->player->handleBedrockItemFrameDropItem($packet);
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		if(!$this->player->spawned or !$this->player->isAlive()){
			return true;
		}

		$this->player->chat($packet->command);
		return true;
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

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->player->handleBedrockPlayerActionFromData($packet->action, new Vector3($packet->x, $packet->y, $packet->z), $packet->face);
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		return $this->player->handlePlayerSkin($packet);
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		return $this->player->handleBedrockRespawn($packet);
	}

	public function handleInteract(InteractPacket $packet) : bool
    {
        $target = $this->player->level->getEntity($packet->actorRuntimeId);
        if ($packet->action === InteractPacket::ACTION_OPEN_INVENTORY and $packet->actorRuntimeId === $this->player->getId()) {
            if ($this->player->newInventoryOpen(ContainerIds::INVENTORY)) {
                $pk = new ContainerOpenPacket();
                $pk->windowId = ContainerIds::INVENTORY;
                $pk->type = WindowTypes::INVENTORY;
                $pk->x = $pk->y = $pk->z = 0;
                $this->player->sendDataPacket($pk);
                $this->player->setCurrentWindowType(WindowTypes::INVENTORY);
            }
        } elseif ($packet->action === InteractPacket::ACTION_MOUSEOVER) {
            $text = $target->getInteractButtonText($this->player);
            if ($text !== null) {
                $this->player->setInteractiveTag($text);
            } else {
                $this->player->removeInteractiveTag();
            }
        } elseif ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
            if ($target instanceof MinecartAbstract) { //TODO: Boat
                $target->setLinked(0, $this->player);
                return true;
            }
        }
        return true;
    }

	public function handlePacketViolationWarning(PacketViolationWarningPacket $packet) : bool{
		$this->player->getServer()->getLogger()->notice("PacketViolationWarning from {$this->player->getName()}: (type={$packet->type},severity={$packet->severity},packetId={$packet->packetId},violationContext={$packet->violationContext})");
		return true;
	}

	public function handleEmote(EmotePacket $packet) : bool{
		return $this->player->handleEmote($packet);
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
		return $this->player->handleRequestNetworkSettings($packet);
	}

    public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
        return $this->player->handleBedrockMapInfoRequest($packet);
    }

}


