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

namespace pocketmine\network\bedrock\palette;

use pocketmine\network\bedrock\palette\entry\PaletteEntry;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;

class PaletteTask extends AsyncTask
{

    public NonThreadSafeValue $value;

    public function __construct(
        PaletteEntry $paletteEntry,
    )
    {
        $this->value = new NonThreadSafeValue($paletteEntry);
    }

    public function onRun()
    {
        $entry = $this->value->deserialize();
        $entry->process();
        $this->setResult($entry);
    }

    public function onCompletion(Server $server)
    {
        if ($this->hasResult()) {
            $result = $this->getResult();
            if ($result instanceof PaletteEntry) {
                $result->result();
            }
        }
    }

}

