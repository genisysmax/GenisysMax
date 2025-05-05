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

namespace pocketmine\command;

use pocketmine\command\defaults\DeopCommand;
use pocketmine\command\defaults\DumpMemoryCommand;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\command\defaults\GarbageCollectorCommand;
use pocketmine\command\defaults\GiveCommand;
use pocketmine\command\defaults\KillCommand;
use pocketmine\command\defaults\ListCommand;
use pocketmine\command\defaults\OpCommand;
use pocketmine\command\defaults\SaveCommand;
use pocketmine\command\defaults\SaveOffCommand;
use pocketmine\command\defaults\SaveOnCommand;
use pocketmine\command\defaults\SayCommand;
use pocketmine\command\defaults\SetWorldSpawnCommand;
use pocketmine\command\defaults\StatusCommand;
use pocketmine\command\defaults\StopCommand;
use pocketmine\command\defaults\TeleportCommand;
use pocketmine\command\defaults\TimeCommand;
use pocketmine\command\defaults\TimingsCommand;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\defaults\VersionCommand;
use pocketmine\command\defaults\WeatherCommand;
use pocketmine\command\defaults\WhitelistCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\TranslationContainer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_map;
use function array_shift;
use function count;
use function explode;
use function min;
use function str_getcsv;
use function strlen;
use function strpos;
use function strtolower;
use function trim;

class SimpleCommandMap implements CommandMap{

    /** @var array */
    public static $commandData = [];

	/**
	 * @param string $commandLine
	 *
	 * @return string[]
	 */
	public static function splitCommandLine(string $commandLine) : array{
		try {
			return array_map("stripslashes", str_getcsv($commandLine, " "));
		} catch (\Throwable) {
			return [];
		}
	}

	/**
	 * @var Command[]
	 */
	protected $knownCommands = [];
	/** @var Server */
	private $server;

	public function __construct(Server $server){
		$this->server = $server;
        $this->setDefaultCommands();
        if(file_exists(Server::getInstance()->getDataPath() . "commands.json")) {
            self::$commandData = json_decode(file_get_contents(Server::getInstance()->getDataPath() . "commands.json"), true);
        }
	}

	private function setDefaultCommands(){
		
		/* deleting the command to fix it on all versions */
		#$this->register("pocketmine", new HelpCommand("help"));
		$this->register("pocketmine", new StopCommand("stop"));
		$this->register("pocketmine", new WhitelistCommand("whitelist"));
		$this->register("pocketmine", new SaveOnCommand("save-on"));
		$this->register("pocketmine", new SaveOffCommand("save-off"));
		$this->register("pocketmine", new SaveCommand("save-all"));
		$this->register("pocketmine", new GiveCommand("give"));
		$this->register("pocketmine", new SetWorldSpawnCommand("setworldspawn"));
		$this->register("pocketmine", new StatusCommand("status"));
		$this->register("pocketmine", new VersionCommand("ver"));
        $this->register("pocketmine", new DeopCommand("deop"));
        $this->register("pocketmine", new GamemodeCommand("gamemode"));
        $this->register("pocketmine", new KillCommand("kill"));
        $this->register("pocketmine", new ListCommand("list"));
        $this->register("pocketmine", new OpCommand("op"));
        $this->register("pocketmine", new SayCommand("say"));
        $this->register("pocketmine", new TimeCommand("time"));
        $this->register("pocketmine", new TeleportCommand("tp"));
        $this->register("pocketmine", new TimingsCommand("timings"));
        $this->register("pocketmine", new WeatherCommand("weather"));

		if($this->server->getProperty("debug.commands", false)){
			$this->register("pocketmine", new GarbageCollectorCommand("gc"));
			$this->register("pocketmine", new DumpMemoryCommand("dumpmemory"));
		}
	}


	public function registerAll(string $fallbackPrefix, array $commands){
		foreach($commands as $command){
			$this->register($fallbackPrefix, $command);
		}
	}

	/**
	 * @param string      $fallbackPrefix
	 * @param Command     $command
	 * @param string|null $label
	 *
	 * @return bool
	 */
	public function register(string $fallbackPrefix, Command $command, string $label = null) : bool{
		if($label === null){
			$label = $command->getName();
		}
		$label = trim($label);
		$fallbackPrefix = strtolower(trim($fallbackPrefix));

		$registered = $this->registerAlias($command, false, $fallbackPrefix, $label);

		$aliases = $command->getAliases();
		foreach($aliases as $index => $alias){
			if(!$this->registerAlias($command, true, $fallbackPrefix, $alias)){
				unset($aliases[$index]);
			}
		}
		$command->setAliases($aliases);

		if(!$registered){
			$command->setLabel($fallbackPrefix . ":" . $label);
		}

		$command->register($this);

		return $registered;
	}

	/**
	 * @param Command $command
	 * @param bool $isAlias
	 * @param string $fallbackPrefix
	 * @param string $label
	 *
	 * @return bool
	 */
	private function registerAlias(Command $command, bool $isAlias, string $fallbackPrefix, string $label) : bool{
		$this->knownCommands[$fallbackPrefix . ":" . $label] = $command;
		if(($command instanceof VanillaCommand or $isAlias) and isset($this->knownCommands[$label])){
			return false;
		}

		if(isset($this->knownCommands[$label]) and $this->knownCommands[$label]->getLabel() !== null and $this->knownCommands[$label]->getLabel() === $label){
			return false;
		}

		if(!$isAlias){
			$command->setLabel($label);
		}

		$this->knownCommands[$label] = $command;

		return true;
	}

	/**
	 * Returns a command to match the specified command line, or null if no matching command was found.
	 * This method is intended to provide capability for handling commands with spaces in their name.
	 * The referenced parameters will be modified accordingly depending on the resulting matched command.
	 *
	 * @param string   &$commandName
	 * @param string[] &$args
	 *
	 * @return Command|null
	 */
	public function matchCommand(string &$commandName, array &$args){
		$count = min(count($args), 255);

		for($i = 0; $i < $count; ++$i){
			$commandName .= array_shift($args);
			if(($command = $this->getCommand($commandName)) instanceof Command){
				return $command;
			}

			$commandName .= " ";
		}

		return null;
	}

	public function dispatch(CommandSender $sender, string $commandLine) : bool{
		$args = self::splitCommandLine($commandLine);
		$sentCommandLabel = "";
		$target = $this->matchCommand($sentCommandLabel, $args);

		if($target === null){
			return false;
		}

		$target->timings->startTiming();

		try{
			$target->execute($sender, $sentCommandLabel, $args);
		}catch(InvalidCommandSyntaxException $e){
			$sender->sendCommandMessage($this->server->getLanguage()->translateString("commands.generic.usage", [$target->getUsage()]));
		}catch(\Throwable $e){
			$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.exception"));
			$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.command.exception", [$commandLine, (string) $target, $e->getMessage()]));
			$sender->getServer()->getLogger()->logException($e);
		}

		$target->timings->stopTiming();

		return true;
	}

	public function unregister(Command $command) : bool{
		$unreg = false;
		if(isset($this->knownCommands[$command->getName()])){
			$unreg = true;
			unset($this->knownCommands[$command->getName()]);
		}
		foreach($command->getAliases() as $alias){
			if(isset($this->knownCommands[$alias])){
				$unreg = true;
				unset($this->knownCommands[$alias]);
			}
		}
		if($unreg){
			$command->unregister($this);
		}

		return $unreg;
	}

	public function clearCommands(){
		foreach($this->knownCommands as $command){
			$command->unregister($this);
		}
		$this->knownCommands = [];
		$this->setDefaultCommands();
	}

	public function getCommand(string $name){
		return $this->knownCommands[$name] ?? null;
	}

	/**
	 * @return Command[]
	 */
	public function getCommands() : array{
		return $this->knownCommands;
	}


	/**
	 * @return void
	 */
	public function registerServerAliases(){
		$values = $this->server->getCommandAliases();

		foreach($values as $alias => $commandStrings){
			if(strpos($alias, ":") !== false){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.illegal", [$alias]));
				continue;
			}

			$targets = [];

			$bad = "";
			$recursive = "";
			foreach($commandStrings as $commandString){
				$args = explode(" ", $commandString);
				$commandName = "";
				$command = $this->matchCommand($commandName, $args);


				if($command === null){
					if(strlen($bad) > 0){
						$bad .= ", ";
					}
					$bad .= $commandString;
				}elseif($commandName === $alias){
					if($recursive !== ""){
						$recursive .= ", ";
					}
					$recursive .= $commandString;
				}else{
					$targets[] = $commandString;
				}
			}

			if($recursive !== ""){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.recursive", [$alias, $recursive]));
				continue;
			}

			if(strlen($bad) > 0){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.notFound", [$alias, $bad]));
				continue;
			}

			//These registered commands have absolute priority
			if(count($targets) > 0){
				$this->knownCommands[strtolower($alias)] = new FormattedCommandAlias(strtolower($alias), $targets);
			}else{
				unset($this->knownCommands[strtolower($alias)]);
			}

		}
	}


}


