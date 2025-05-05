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

namespace pocketmine\scheduler;

use Closure;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;

class AsyncClosureTask extends AsyncTask{

    protected NonThreadSafeValue $parameters;

    public function __construct(
        protected Closure $fn,
        array $parameters = [],
        protected ?Closure $onComplete = null,
    ) {
        $this->parameters = new NonThreadSafeValue($parameters);
    }

    public function onRun() : void{
        ($this->fn)(...$this->parameters->deserialize());
    }

    public function onCompletion(Server $server) : void{
        if ($this->onComplete !== NULL) {
            ($this->onComplete)();
        }
    }
}

