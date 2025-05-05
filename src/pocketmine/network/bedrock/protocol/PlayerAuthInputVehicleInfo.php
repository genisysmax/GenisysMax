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

final class PlayerAuthInputVehicleInfo{

    public function __construct(
        private float $vehicleRotationX,
        private float $vehicleRotationZ,
        private int $predictedVehicleActorUniqueId
    ){}

    public function getVehicleRotationX() : float{ return $this->vehicleRotationX; }

    public function getVehicleRotationZ() : float{ return $this->vehicleRotationZ; }

    public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

    public static function read(DataPacket $in) : self{
        $vehicleRotationX = $in->getLFloat();
        $vehicleRotationZ = $in->getLFloat();
        $predictedVehicleActorUniqueId = $in->getActorUniqueId();

        return new self($vehicleRotationX, $vehicleRotationZ, $predictedVehicleActorUniqueId);
    }

    public function write(DataPacket $out) : void{
        $out->putLFloat($this->vehicleRotationX);
        $out->putLFloat($this->vehicleRotationZ);
        $out->putActorUniqueId($this->predictedVehicleActorUniqueId);
    }
}

