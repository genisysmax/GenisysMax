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

/**
 * Command handling related classes
 */
namespace pocketmine\command;

use pocketmine\event\TextContainer;
use pocketmine\event\TimingsHandler;
use pocketmine\event\TranslationContainer;
use pocketmine\network\bedrock\protocol\types\command\CommandData;
use pocketmine\network\bedrock\protocol\types\command\CommandOverload;
use pocketmine\network\bedrock\protocol\types\command\CommandParameter;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function explode;
use function file_get_contents;
use function json_decode;
use function str_replace;

abstract class Command{
	/** @var array */
	private static $defaultDataTemplate = null;

	/** @var string */
	private $name;

    /** @var ?CommandData */
	protected $commandData = null;

    /** @var ?array */
    protected $mcpeCommandData = null;

    /** @var bool */
    public $convertMcpeDataToBedrock = true;

	/** @var string */
	private $nextLabel;

	/** @var string */
	private $label;

	/**
	 * @var string[]
	 */
	private $activeAliases = [];

	/** @var CommandMap */
	private $commandMap = null;

	/** @var string */
	protected $description = "";

	/** @var string */
	protected $usageMessage;

	/** @var string */
	private $permissionMessage = null;

	/** @var TimingsHandler */
	public $timings;

	/**
	 * @param string   $name
	 * @param string   $description
	 * @param string   $usageMessage
	 * @param string[] $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []){
        $this->mcpeCommandData = SimpleCommandMap::$commandData[$name] ?? self::generateMcpeData();
		$this->name = $name;
		$this->setLabel($name);
		$this->setDescription($description);
		$this->usageMessage = $usageMessage ?? ("/" . $name);
		$this->setAliases($aliases);
        $this->buildCommandData();
    }

    private function buildCommandData() : void{
        $this->commandData = new CommandData();
        $this->commandData->commandName = $this->name;
        $this->commandData->commandDescription = Server::getInstance()->getLanguage()->translateString($this->description);
        $this->commandData->flags = 0;
        $this->commandData->permission = 0;
        $this->commandData->aliases = null;
        $this->commandData->overloads = [[new CommandParameter()]];
    }

    /**
     *
     * @return CommandData
     */
    public function getCommandData() : CommandData{
        return $this->commandData;
    }

	/**
	 * Returns an array containing command data
	 *
	 * @return array
	 */
    public function getMcpeCommandData() : array{
        return $this->mcpeCommandData;
    }

    public function setMcpeParameters(array $parameters, string $overloadIndex) : void{
        $this->mcpeCommandData["overloads"][$overloadIndex] = $parameters;
    }

    /**
     * Adds parameter to overload
     *
     * @param CommandParameter $parameter
     * @param int              $overloadIndex
     */
    public function addParameter(CommandParameter $parameter, int $overloadIndex) : void{
        $this->commandData->overloads[$overloadIndex][] = $parameter;
    }

    /**
     * Sets parameter to overload
     *
     * @param CommandParameter $parameter
     * @param int              $parameterIndex
     * @param int              $overloadIndex
     */
    public function setParameter(CommandParameter $parameter, int $parameterIndex, int $overloadIndex) : void{
        $this->commandData->overloads[$overloadIndex][$parameterIndex] = $parameter;
    }

    /**
     * Sets parameters to overload
     *
     * @param CommandParameter[] $parameters
     * @param int                $overloadIndex
     */
    public function setParameters(array $parameters, int $overloadIndex) : void{
        $this->commandData->overloads[$overloadIndex] = new CommandOverload(false, array_values($parameters));
    }

    /**
     * Removes parameter from overload
     *
     * @param int $parameterIndex
     * @param int $overloadIndex
     */
    public function removeParameter(int $parameterIndex, int $overloadIndex) : void{
        unset($this->commandData->overloads[$parameterIndex]);
    }

    public function removeAllMcpeParameters() : void{
        $this->mcpeCommandData["overloads"] = [];
    }

    /**
     * Remove all overloads
     */
    public function removeAllParameters() : void{
        $this->commandData->overloads = [];
    }

    /**
     * Removes overload and includes.
     *
     * @param int $overloadIndex
     */
    public function removeMcpeOverload(string $overloadIndex) : void{
        unset($this->mcpeCommandData["overloads"][$overloadIndex]);
    }

    public function removeOverload(int $overloadIndex) : void{
        unset($this->commandData->overloads[$overloadIndex]);
    }

    /**
     * Returns overload
     *
     * @param int $index
     *
     * @return CommandParameter[]|null
     */
    public function getOverload(string $index) : ?array{
        return $this->mcpeCommandData["overloads"][$index] ?? null;
    }

    /**
     * @return array
     */
    public function getOverloads() : array{
        return $this->mcpeCommandData["overloads"];
    }

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return mixed
	 */
	abstract public function execute(CommandSender $sender, string $commandLabel, array $args);

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string|null
	 */
    public function getPermission(){
        return $this->mcpeCommandData["pocketminePermission"] ?? null;
    }


	/**
	 * @param string|null $permission
	 */
    public function setPermission(string $permission = null){
        if($permission !== null){
            $this->mcpeCommandData["pocketminePermission"] = $permission;
        }else{
            unset($this->mcpeCommandData["pocketminePermission"]);
        }
    }

	/**
	 * @param CommandSender $target
	 *
	 * @return bool
	 */
	public function testPermission(CommandSender $target) : bool{
		if($this->testPermissionSilent($target)){
			return true;
		}

		if($this->permissionMessage === null){
			$target->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));
		}elseif($this->permissionMessage !== ""){
			$target->sendMessage(str_replace("<permission>", $this->getPermission(), $this->permissionMessage));
		}

		return false;
	}

	/**
	 * @param CommandSender $target
	 *
	 * @return bool
	 */
	public function testPermissionSilent(CommandSender $target) : bool{
		if(($perm = $this->getPermission()) === null or $perm === ""){
			return true;
		}

		foreach(explode(";", $perm) as $permission){
			if($target->hasPermission($permission)){
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getLabel() : string{
		return $this->label;
	}

	public function setLabel(string $name) : bool{
		$this->nextLabel = $name;
		if(!$this->isRegistered()){
			if($this->timings instanceof TimingsHandler){
				$this->timings->remove();
			}
			$this->timings = new TimingsHandler("** Command: " . $name);
			$this->label = $name;

			return true;
		}

		return false;
	}

	/**
	 * Registers the command into a Command map
	 *
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	public function register(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = $commandMap;

			return true;
		}

		return false;
	}

	/**
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	public function unregister(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = null;
            $this->activeAliases = $this->mcpeCommandData["aliases"];
			$this->label = $this->nextLabel;

			return true;
		}

		return false;
	}

	/**
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	private function allowChangesFrom(CommandMap $commandMap) : bool{
		return $this->commandMap === null or $this->commandMap === $commandMap;
	}

	/**
	 * @return bool
	 */
	public function isRegistered() : bool{
		return $this->commandMap !== null;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array{
		return $this->activeAliases;
	}

	/**
	 * @return string
	 */
	public function getPermissionMessage() : string{
		return $this->permissionMessage;
	}

	/**
	 * @return string
	 */
	public function getDescription() : string{
        return $this->commandData->commandDescription;
	}

	/**
	 * @return string
	 */
	public function getUsage() : string{
		return $this->usageMessage;
	}

	/**
	 * @param string[] $aliases
	 */
	public function setAliases(array $aliases){
        $this->mcpeCommandData["aliases"] = $aliases;
		if(!$this->isRegistered()){
			$this->activeAliases = (array) $aliases;
		}
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description){
        if (!$this->isRegistered()) {
            $this->description = $description;
            return;
        }
        $this->commandData->commandDescription = Server::getInstance()->getLanguage()->translateString($description);
	}

	/**
	 * @param string $permissionMessage
	 */
	public function setPermissionMessage(string $permissionMessage){
		$this->permissionMessage = $permissionMessage;
	}

	/**
	 * @param string $usage
	 */
	public function setUsage(string $usage){
		$this->usageMessage = $usage;
	}

	/**
	 * @return array
	 */
	final public static function generateMcpeData() : array{
		if(self::$defaultDataTemplate === null){
			self::$defaultDataTemplate = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "command_default.json"), true);
		}
		return self::$defaultDataTemplate;
	}

	/**
	 * @param CommandSender        $source
	 * @param TextContainer|string $message
	 * @param bool                 $sendToSource
	 */
	public static function broadcastCommandMessage(CommandSender $source, $message, bool $sendToSource = true){
		if($message instanceof TextContainer){
			$m = clone $message;
			$result = "[" . $source->getName() . ": " . ($source->getServer()->getLanguage()->get($m->getText()) !== $m->getText() ? "%" : "") . $m->getText() . "]";

			$users = $source->getServer()->getPluginManager()->getPermissionSubscriptions(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
			$colored = TextFormat::GRAY . TextFormat::ITALIC . $result;

			$m->setText($result);
			$result = clone $m;
			$m->setText($colored);
			$colored = clone $m;
		}else{
			$users = $source->getServer()->getPluginManager()->getPermissionSubscriptions(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
			$result = new TranslationContainer("chat.type.admin", [$source->getName(), $message]);
			$colored = new TranslationContainer(TextFormat::GRAY . TextFormat::ITALIC . "%chat.type.admin", [$source->getName(), $message]);
		}

		if($sendToSource === true and !($source instanceof ConsoleCommandSender)){
			$source->sendCommandMessage($message);
		}

		foreach($users as $user){
			if($user instanceof CommandSender){
				if($user instanceof ConsoleCommandSender){
					$user->sendCommandMessage($result);
				}elseif($user !== $source){
					$user->sendCommandMessage($colored);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function __toString() : string{
		return $this->name;
	}
}


