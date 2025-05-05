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

use pocketmine\network\bedrock\protocol\types\Experiments;
use pocketmine\network\bedrock\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\network\NetworkSession;
use function count;

class ResourcePackStackPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_STACK_PACKET;

    /** @var ResourcePackStackEntry[] */
    public array $resourcePackStack = [];
    /** @var ResourcePackStackEntry[] */
    public array $behaviorPackStack = [];
    public bool $mustAccept = false;
    public string $baseGameVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
    public Experiments $experiments;
    public bool $useVanillaEditorPacks = false;

    public function decodePayload(){
        $this->mustAccept = $this->getBool();
        $behaviorPackCount = $this->getUnsignedVarInt();
        while($behaviorPackCount-- > 0){
            $this->behaviorPackStack[] = ResourcePackStackEntry::read($this);
        }

        $resourcePackCount = $this->getUnsignedVarInt();
        while($resourcePackCount-- > 0){
            $this->resourcePackStack[] = ResourcePackStackEntry::read($this);
        }

        $this->baseGameVersion = $this->getString();
        $this->experiments = $this->getExperiments();
        $this->useVanillaEditorPacks = $this->getBool();
    }

    public function encodePayload(){
        $this->putBool($this->mustAccept);

        $this->putUnsignedVarInt(count($this->behaviorPackStack));
        foreach($this->behaviorPackStack as $entry){
            $entry->write($this);
        }

        $this->putUnsignedVarInt(count($this->resourcePackStack));
        foreach($this->resourcePackStack as $entry){
            $entry->write($this);
        }

        $this->putString($this->baseGameVersion);
        $this->putExperiments($this->experiments);
        $this->putBool($this->useVanillaEditorPacks);
    }

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePackStack($this);
	}
}


