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

namespace pocketmine\block;

use InvalidArgumentException;
use pocketmine\math\Vector3;

class Rail extends BaseRail {

    /* extended meta values for regular rails, to allow curving */
    public const CURVE_SOUTHEAST = 6;
    public const CURVE_SOUTHWEST = 7;
    public const CURVE_NORTHWEST = 8;
    public const CURVE_NORTHEAST = 9;

    private const CURVE_CONNECTIONS = [
        self::CURVE_SOUTHEAST => [
            Vector3::SIDE_SOUTH,
            Vector3::SIDE_EAST
        ],
        self::CURVE_SOUTHWEST => [
            Vector3::SIDE_SOUTH,
            Vector3::SIDE_WEST
        ],
        self::CURVE_NORTHWEST => [
            Vector3::SIDE_NORTH,
            Vector3::SIDE_WEST
        ],
        self::CURVE_NORTHEAST => [
            Vector3::SIDE_NORTH,
            Vector3::SIDE_EAST
        ]
    ];

    protected $id = self::RAIL;

    public function getName() : string{
        return "Rail";
    }

    protected function getMetaForState(array $connections) : int{
        try{
            return self::searchState($connections, self::CURVE_CONNECTIONS);
        }catch(InvalidArgumentException $e){
            return parent::getMetaForState($connections);
        }
    }

    protected function getConnectionsForState() : array{
        return self::CURVE_CONNECTIONS[$this->meta] ?? self::CONNECTIONS[$this->meta];
    }

    protected function getPossibleConnectionDirectionsOneConstraint(int $constraint) : array{
        /** @var int[] $horizontal */
        static $horizontal = [
            Vector3::SIDE_NORTH,
            Vector3::SIDE_SOUTH,
            Vector3::SIDE_WEST,
            Vector3::SIDE_EAST
        ];

        $possible = parent::getPossibleConnectionDirectionsOneConstraint($constraint);

        if(($constraint & self::FLAG_ASCEND) === 0){
            foreach($horizontal as $d){
                if($constraint !== $d){
                    $possible[$d] = true;
                }
            }
        }

        return $possible;
    }
}


