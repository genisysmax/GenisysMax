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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\NetworkSession;

class LevelEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_EVENT_PACKET;

	public const EVENT_SOUND_CLICK = 1000;
	public const EVENT_SOUND_CLICK_FAIL = 1001;
	public const EVENT_SOUND_SHOOT = 1002;
	public const EVENT_SOUND_DOOR = 1003;
	public const EVENT_SOUND_FIZZ = 1004;
	public const EVENT_SOUND_IGNITE = 1005;

	public const EVENT_SOUND_GHAST = 1007;
	public const EVENT_SOUND_GHAST_SHOOT = 1008;
	public const EVENT_SOUND_BLAZE_SHOOT = 1009;
	public const EVENT_SOUND_DOOR_BUMP = 1010;

	public const EVENT_SOUND_DOOR_CRASH = 1012;

	public const EVENT_SOUND_ENDERMAN_TELEPORT = 1018;

	public const EVENT_SOUND_ANVIL_BREAK = 1020;
	public const EVENT_SOUND_ANVIL_USE = 1021;
	public const EVENT_SOUND_ANVIL_FALL = 1022;

	public const EVENT_SOUND_POP = 1030;

	public const EVENT_SOUND_PORTAL = 1032;

	public const EVENT_SOUND_ITEMFRAME_ADD_ITEM = 1040;
	public const EVENT_SOUND_ITEMFRAME_REMOVE = 1041;
	public const EVENT_SOUND_ITEMFRAME_PLACE = 1042;
	public const EVENT_SOUND_ITEMFRAME_REMOVE_ITEM = 1043;
	public const EVENT_SOUND_ITEMFRAME_ROTATE_ITEM = 1044;

	public const EVENT_SOUND_CAMERA = 1050;
	public const EVENT_SOUND_ORB = 1051;
	public const EVENT_SOUND_TOTEM = 1052;

	public const EVENT_PARTICLE_SHOOT = 2000;
	public const EVENT_PARTICLE_DESTROY = 2001;
	public const EVENT_PARTICLE_SPLASH = 2002;
	public const EVENT_PARTICLE_EYE_DESPAWN = 2003;
	public const EVENT_PARTICLE_SPAWN = 2004;

	public const EVENT_GUARDIAN_CURSE = 2006;

	public const EVENT_PARTICLE_BLOCK_FORCE_FIELD = 2008;
    public const EVENT_PARTICLE_PROJECTILE_HIT = 2009;
    public const EVENT_PARTICLE_DRAGON_EGG_TELEPORT = 2010;

	public const EVENT_PARTICLE_ENDERMAN_TELEPORT = 2013;
	public const EVENT_PARTICLE_PUNCH_BLOCK = 2014;

	public const EVENT_START_RAIN = 3001;
	public const EVENT_START_THUNDER = 3002;
	public const EVENT_STOP_RAIN = 3003;
	public const EVENT_STOP_THUNDER = 3004;
	public const EVENT_PAUSE_GAME = 3005; //data: 1 to pause, 0 to resume

	public const EVENT_REDSTONE_TRIGGER = 3500;
	public const EVENT_CAULDRON_EXPLODE = 3501;
	public const EVENT_CAULDRON_DYE_ARMOR = 3502;
	public const EVENT_CAULDRON_CLEAN_ARMOR = 3503;
	public const EVENT_CAULDRON_FILL_POTION = 3504;
	public const EVENT_CAULDRON_TAKE_POTION = 3505;
	public const EVENT_CAULDRON_FILL_WATER = 3506;
	public const EVENT_CAULDRON_TAKE_WATER = 3507;
	public const EVENT_CAULDRON_ADD_DYE = 3508;

	public const EVENT_BLOCK_START_BREAK = 3600;
	public const EVENT_BLOCK_STOP_BREAK = 3601;

	public const EVENT_SET_DATA = 4000;

	public const EVENT_PLAYERS_SLEEPING = 9800;

	public const EVENT_ADD_PARTICLE_MASK = 0x4000;

	public $evid;
	public $x = 0; //Weather effects don't have coordinates
	public $y = 0;
	public $z = 0;
	public $data;

	public function decodePayload(){
		$this->evid = $this->getVarInt();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->data = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putVarInt($this->evid);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVarInt($this->data);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelEvent($this);
	}

}


