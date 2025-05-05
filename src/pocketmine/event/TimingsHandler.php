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

namespace pocketmine\event;

use pocketmine\entity\Living;
use pocketmine\Server;
use function count;
use function fwrite;
use function round;
use function spl_object_id;
use const PHP_EOL;

class TimingsHandler{

	/** @var TimingsHandler[] */
	private static $HANDLERS = [];

    /** @var bool */
    private static $enabled = false;
    /** @var int */
    private static $timingStart = 0;

    /** @var string */
	private $name;
	/** @var TimingsHandler|null */
	private $parent;

    /** @var int */
	private $count = 0;
    /** @var int */
	private $curCount = 0;
    /** @var int */
	private $start = 0;
    /** @var int */
	private $timingDepth = 0;
    /** @var int */
	private $totalTime = 0;
    /** @var int */
	private $curTickTotal = 0;
    /** @var int */
	private $violations = 0;

    /**
     * @param string $name
     * @param TimingsHandler|null $parent
     */
	public function __construct(string $name, TimingsHandler $parent = null){
		$this->name = $name;
        $this->parent = $parent;

		self::$HANDLERS[spl_object_id($this)] = $this;
	}

	/**
	 * @param resource $fp
	 */
	public static function printTimings($fp): void{
		fwrite($fp, "Minecraft" . PHP_EOL);

		foreach(self::$HANDLERS as $timings){
			$time = $timings->totalTime;
			$count = $timings->count;
			if($count === 0){
				continue;
			}

			$avg = $time / $count;

          $formatTime = number_format($time, 0, '.', '');
          $formatAvg = number_format($avg * 1000000000, 0, '.', '');
          
          fwrite($fp, "    " . $timings->name . " Time: " . $formatTime . " Count: " . $count . " Avg: " . $formatAvg . " Violations: " . $timings->violations . PHP_EOL);
		}

		fwrite($fp, "# Version " . Server::getInstance()->getVersion() . PHP_EOL);
		fwrite($fp, "# " . Server::getInstance()->getName() . " " . Server::getInstance()->getPocketMineVersion() . PHP_EOL);

		$entities = 0;
		$livingEntities = 0;
		foreach(Server::getInstance()->getLevels() as $level){
			$entities += count($level->getEntities());
			foreach($level->getEntities() as $e){
				if($e instanceof Living){
					++$livingEntities;
				}
			}
		}

		fwrite($fp, "# Entities " . $entities . PHP_EOL);
		fwrite($fp, "# LivingEntities " . $livingEntities . PHP_EOL);

        $sampleTime = hrtime(true) - self::$timingStart;
        fwrite($fp, "Sample time $sampleTime (" . ($sampleTime / 1000000000) . "s)" . PHP_EOL);
	}

    public static function isEnabled() : bool{
        return self::$enabled;
    }

    public static function setEnabled(bool $enable = true) : void{
        self::$enabled = $enable;
        self::reload();
    }

    public static function reload() : void{
        foreach(self::$HANDLERS as $timings){
            $timings->reset();
        }

        if(self::$enabled){
            self::$timingStart = hrtime(true);
        }
    }

    public static function tick(bool $measure = true) : void{
        if(self::$enabled){
			if($measure){
				foreach(self::$HANDLERS as $timings){
                    if($timings->curTickTotal > 50000000){
                        $timings->violations += (int) round($timings->curTickTotal / 50000000);
					}
					$timings->curTickTotal = 0;
					$timings->curCount = 0;
				}
			}else{
				foreach(self::$HANDLERS as $timings){
					$timings->totalTime -= $timings->curTickTotal;
					$timings->count -= $timings->curCount;

					$timings->curTickTotal = 0;
					$timings->curCount = 0;
				}
			}
		}
	}

    public function startTiming() : void{
        if(self::$enabled){
            $this->internalStartTiming(hrtime(true));
        }
    }

    private function internalStartTiming(int $now) : void{
        if (++$this->timingDepth === 1) {
            $this->start = $now;
            if ($this->parent !== null) {
                $this->parent->internalStartTiming($now);
            }
        }
    }

    public function stopTiming() : void{
        if(self::$enabled){
            $this->internalStopTiming(hrtime(true));
        }
    }

    private function internalStopTiming(int $now) : void{
        if ($this->timingDepth === 0) {
            //TODO: it would be nice to bail here, but since we'd have to track timing depth across resets
            //and enable/disable, it would have a performance impact. Therefore, considering the limited
            //usefulness of bailing here anyway, we don't currently bother.
            return;
        }
        if (--$this->timingDepth !== 0) {
            return;
        }

        if($this->start == 0){
            return;
        }

        $diff = $now - $this->start;
        $this->totalTime += $diff;
        $this->curTickTotal += $diff;
        ++$this->curCount;
        ++$this->count;
        $this->start = 0;

        if ($this->parent !== null) {
            $this->parent->internalStopTiming($now);
        }
    }

	public function reset(): void{
		$this->count = 0;
		$this->curCount = 0;
		$this->violations = 0;
		$this->curTickTotal = 0;
		$this->totalTime = 0;
		$this->start = 0;
	}

	public function remove(): void{
		unset(self::$HANDLERS[spl_object_id($this)]);
	}
}

