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

namespace pocketmine\plugin;

use pocketmine\command\defaults\TimingsCommand;
use pocketmine\command\PluginCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\event\Listener;
use pocketmine\event\plugin\{PluginDisableEvent, PluginEnableEvent};
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\permission\Permissible;
use pocketmine\permission\Permission;
use pocketmine\Server;
use function array_map;
use function array_pad;
use function basename;
use function count;
use function explode;
use function get_class;
use function gettype;
use function is_array;
use function is_bool;
use function is_dir;
use function is_string;
use function is_subclass_of;
use function microtime;
use function preg_match;
use function spl_object_id;
use function stripos;
use function strpos;
use function strtolower;

/**
 * Manages all the plugins, Permissions and Permissibles
 */
class PluginManager{

	/** @var Server */
	private $server;

	/** @var SimpleCommandMap */
	private $commandMap;

	/**
	 * @var Plugin[]
	 */
	protected $plugins = [];

	/**
	 * @var Permission[]
	 */
	protected $permissions = [];

	/**
	 * @var Permission[]
	 */
	protected $defaultPerms = [];

	/**
	 * @var Permission[]
	 */
	protected $defaultPermsOp = [];

	/**
	 * @var Permissible[][]
	 */
	protected $permSubs = [];

	/**
	 * @var Permissible[]
	 */
	protected $defSubs = [];

	/**
	 * @var Permissible[]
	 */
	protected $defSubsOp = [];

	/**
	 * @var PluginLoader[]
	 */
	protected $fileAssociations = [];

	/** @var TimingsHandler */
	public static $pluginParentTimer;

	/**
	 * @param Server           $server
	 * @param SimpleCommandMap $commandMap
	 */
	public function __construct(Server $server, SimpleCommandMap $commandMap){
		$this->server = $server;
		$this->commandMap = $commandMap;
	}

	/**
	 * @param string $name
	 *
	 * @return null|Plugin
	 */
	public function getPlugin(string $name){
		if(isset($this->plugins[$name])){
			return $this->plugins[$name];
		}

		return null;
	}

	/**
	 * @param string $loaderName A PluginLoader class name
	 *
	 * @return bool
	 */
	public function registerInterface(string $loaderName) : bool{
		if(is_subclass_of($loaderName, PluginLoader::class)){
			$loader = new $loaderName($this->server);
		}else{
			return false;
		}

		$this->fileAssociations[$loaderName] = $loader;

		return true;
	}

	/**
	 * @return Plugin[]
	 */
	public function getPlugins() : array{
		return $this->plugins;
	}

	/**
	 * @param string         $path
	 * @param PluginLoader[] $loaders
	 *
	 * @return Plugin|null
	 */
	public function loadPlugin(string $path, array $loaders = null){
		foreach($loaders ?? $this->fileAssociations as $loader){
			if(preg_match($loader->getPluginFilters(), basename($path)) > 0){
				$description = $loader->getPluginDescription($path);
				if($description instanceof PluginDescription){
					try{
						$description->checkRequiredExtensions();
					}catch(PluginException $ex){
						$this->server->getLogger()->error($ex->getMessage());
						return null;
					}

					try{
						if(($plugin = $loader->loadPlugin($path)) instanceof Plugin){
							$this->plugins[$plugin->getDescription()->getName()] = $plugin;

							$pluginCommands = $this->parseYamlCommands($plugin);

							if(count($pluginCommands) > 0){
								$this->commandMap->registerAll($plugin->getDescription()->getName(), $pluginCommands);
							}

							return $plugin;
						}
					}catch(\Throwable $e){
						$this->server->getLogger()->logException($e);
						return null;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param PluginLoader      $loader
	 * @param PluginBase        $plugin
	 * @param PluginDescription $description
	 * @param string            $dataFolder
	 * @param string            $file
	 */
	public function initPlugin(PluginLoader $loader, PluginBase $plugin, PluginDescription $description, $dataFolder, $file) : void{
		$plugin->init($loader, $this->server, $description, $dataFolder, $file);
		$plugin->onLoad();
	}

	
	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		try {
			if($plugin instanceof PluginBase and !$plugin->isEnabled()){
				$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.enable", [$plugin->getDescription()->getFullName()]));
	
				$plugin->setEnabled(true);
	
				(new PluginEnableEvent($plugin))->call();
			}
			foreach($plugin->getDescription()->getPermissions() as $perm){
				$this->addPermission($perm);
			}
		}catch(\Throwable $e){
			$this->server->getLogger()->logException($e);
			$this->disablePlugin($plugin);
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and $plugin->isEnabled()){
			$plugin->setEnabled(false);
			try{
				$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.disable", [$plugin->getDescription()->getFullName()]));

				$this->server->getPluginManager()->callEvent(new PluginDisableEvent($plugin));
			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
			}

			$this->server->getScheduler()->cancelTasks($plugin);
			HandlerList::unregisterAll($plugin);
			foreach($plugin->getDescription()->getPermissions() as $perm){
				$this->removePermission($perm);
			}
		}
	}

	/**
	 * @param string $directory
	 * @param array  $newLoaders
	 *
	 * @return Plugin[]
	 */
	public function loadPlugins(string $directory, array $newLoaders = null){

		if(is_dir($directory)){
			$plugins = [];
			$loadedPlugins = [];
			$dependencies = [];
			$softDependencies = [];
			if(is_array($newLoaders)){
				$loaders = [];
				foreach($newLoaders as $key){
					if(isset($this->fileAssociations[$key])){
						$loaders[$key] = $this->fileAssociations[$key];
					}
				}
			}else{
				$loaders = $this->fileAssociations;
			}
			foreach($loaders as $loader){
				foreach(new \RegexIterator(new \DirectoryIterator($directory), $loader->getPluginFilters()) as $file){
					if($file === "." or $file === ".."){
						continue;
					}
					$file = $directory . $file;
					try{
						$description = $loader->getPluginDescription($file);
						if($description instanceof PluginDescription){
							$name = $description->getName();
							if(stripos($name, "pocketmine") !== false or stripos($name, "minecraft") !== false or stripos($name, "mojang") !== false){
								$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.restrictedName"]));
								continue;
							}elseif(strpos($name, " ") !== false){
								$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.plugin.spacesDiscouraged", [$name]));
							}

							if(isset($plugins[$name]) or $this->getPlugin($name) instanceof Plugin){
								$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.duplicateError", [$name]));
								continue;
							}

							$compatible = false;
							//Check multiple dependencies
							foreach($description->getCompatibleApis() as $version){
								//Format: majorVersion.minorVersion.patch (3.0.0)
								//    or: majorVersion.minorVersion.patch-devBuild (3.0.0-alpha1)
								if($version !== $this->server->getApiVersion()){
									$pluginApi = array_pad(explode("-", $version), 2, ""); //0 = version, 1 = suffix (optional)
									$serverApi = array_pad(explode("-", $this->server->getApiVersion()), 2, "");

									/*if(strtoupper($pluginApi[1]) !== strtoupper($serverApi[1])){ //Different release phase (alpha vs. beta) or phase build (alpha.1 vs alpha.2)
										continue;
									}*/

									$pluginNumbers = array_map("intval", explode(".", $pluginApi[0]));
									$serverNumbers = array_map("intval", explode(".", $serverApi[0]));

									if($pluginNumbers[0] !== $serverNumbers[0]){ //Completely different API version
										continue;
									}

									if($pluginNumbers[1] > $serverNumbers[1]){ //If the plugin requires new API features, being backwards compatible
										continue;
									}
								}

								$compatible = true;
								break;
							}

							if($compatible === false){
								$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.incompatibleAPI"]));
								continue;
							}

							$plugins[$name] = $file;

							$softDependencies[$name] = (array) $description->getSoftDepend();
							$dependencies[$name] = (array) $description->getDepend();

							foreach($description->getLoadBefore() as $before){
								if(isset($softDependencies[$before])){
									$softDependencies[$before][] = $name;
								}else{
									$softDependencies[$before] = [$name];
								}
							}
						}
					}catch(\Throwable $e){
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.fileError", [$file, $directory, $e->getMessage()]));
						$this->server->getLogger()->logException($e);
					}
				}
			}


			while(count($plugins) > 0){
				$missingDependency = true;
				foreach($plugins as $name => $file){
					if(isset($dependencies[$name])){
						foreach($dependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
								unset($dependencies[$name][$key]);
							}elseif(!isset($plugins[$dependency])){
								$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.unknownDependency"]));
								break;
							}
						}

						if(count($dependencies[$name]) === 0){
							unset($dependencies[$name]);
						}
					}

					if(isset($softDependencies[$name])){
						foreach($softDependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
								unset($softDependencies[$name][$key]);
							}
						}

						if(count($softDependencies[$name]) === 0){
							unset($softDependencies[$name]);
						}
					}

					if(!isset($dependencies[$name]) and !isset($softDependencies[$name])){
						unset($plugins[$name]);
						$missingDependency = false;
						if($plugin = $this->loadPlugin($file, $loaders) and $plugin instanceof Plugin){
							$loadedPlugins[$name] = $plugin;
						}else{
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.genericLoadError", [$name]));
						}
					}
				}

				if($missingDependency === true){
					foreach($plugins as $name => $file){
						if(!isset($dependencies[$name])){
							unset($softDependencies[$name]);
							unset($plugins[$name]);
							$missingDependency = false;
							if($plugin = $this->loadPlugin($file, $loaders) and $plugin instanceof Plugin){
								$loadedPlugins[$name] = $plugin;
							}else{
								$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.genericLoadError", [$name]));
							}
						}
					}

					//No plugins loaded :(
					if($missingDependency === true){
						foreach($plugins as $name => $file){
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.circularDependency"]));
						}
						$plugins = [];
					}
				}
			}

			TimingsCommand::$timingStart = microtime(true);

			return $loadedPlugins;
		}else{
			TimingsCommand::$timingStart = microtime(true);

			return [];
		}
	}

	/**
	 * @param string $name
	 *
	 * @return null|Permission
	 */
	public function getPermission(string $name){
		if(isset($this->permissions[$name])){
			return $this->permissions[$name];
		}

		return null;
	}

	/**
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function addPermission(Permission $permission) : bool{
		if(!isset($this->permissions[$permission->getName()])){
			$this->permissions[$permission->getName()] = $permission;
			$this->calculatePermissionDefault($permission);

			return true;
		}

		return false;
	}

	/**
	 * @param string|Permission $permission
	 */
	public function removePermission($permission){
		if($permission instanceof Permission){
			unset($this->permissions[$permission->getName()]);
		}else{
			unset($this->permissions[$permission]);
		}
	}

	/**
	 * @param bool $op
	 *
	 * @return Permission[]
	 */
	public function getDefaultPermissions(bool $op) : array{
		if($op === true){
			return $this->defaultPermsOp;
		}else{
			return $this->defaultPerms;
		}
	}

	/**
	 * @param Permission $permission
	 */
	public function recalculatePermissionDefaults(Permission $permission){
		if(isset($this->permissions[$permission->getName()])){
			unset($this->defaultPermsOp[$permission->getName()]);
			unset($this->defaultPerms[$permission->getName()]);
			$this->calculatePermissionDefault($permission);
		}
	}

	/**
	 * @param Permission $permission
	 */
	private function calculatePermissionDefault(Permission $permission){
		Timings::$permissionDefaultTimer->startTiming();
		if($permission->getDefault() === Permission::DEFAULT_OP or $permission->getDefault() === Permission::DEFAULT_TRUE){
			$this->defaultPermsOp[$permission->getName()] = $permission;
			$this->dirtyPermissibles(true);
		}

		if($permission->getDefault() === Permission::DEFAULT_NOT_OP or $permission->getDefault() === Permission::DEFAULT_TRUE){
			$this->defaultPerms[$permission->getName()] = $permission;
			$this->dirtyPermissibles(false);
		}
		Timings::$permissionDefaultTimer->startTiming();
	}

	/**
	 * @param bool $op
	 */
	private function dirtyPermissibles(bool $op){
		foreach($this->getDefaultPermSubscriptions($op) as $p){
			$p->recalculatePermissions();
		}
	}

	/**
	 * @param string      $permission
	 * @param Permissible $permissible
	 */
	public function subscribeToPermission(string $permission, Permissible $permissible){
		if(!isset($this->permSubs[$permission])){
			$this->permSubs[$permission] = [];
		}
		$this->permSubs[$permission][spl_object_id($permissible)] = $permissible;
	}

	/**
	 * @param string      $permission
	 * @param Permissible $permissible
	 */
	public function unsubscribeFromPermission(string $permission, Permissible $permissible){
		if(isset($this->permSubs[$permission])){
			unset($this->permSubs[$permission][spl_object_id($permissible)]);
			if(count($this->permSubs[$permission]) === 0){
				unset($this->permSubs[$permission]);
			}
		}
	}

	/**
	 * @param string $permission
	 *
	 * @return array|Permissible[]
	 */
	public function getPermissionSubscriptions(string $permission) : array{
		return $this->permSubs[$permission] ?? [];
	}

	/**
	 * @param bool        $op
	 * @param Permissible $permissible
	 */
	public function subscribeToDefaultPerms(bool $op, Permissible $permissible){
		if($op === true){
			$this->defSubsOp[spl_object_id($permissible)] = $permissible;
		}else{
			$this->defSubs[spl_object_id($permissible)] = $permissible;
		}
	}

	/**
	 * @param bool        $op
	 * @param Permissible $permissible
	 */
	public function unsubscribeFromDefaultPerms(bool $op, Permissible $permissible){
		if($op === true){
			unset($this->defSubsOp[spl_object_id($permissible)]);
		}else{
			unset($this->defSubs[spl_object_id($permissible)]);
		}
	}

	/**
	 * @param bool $op
	 *
	 * @return Permissible[]
	 */
	public function getDefaultPermSubscriptions(bool $op) : array{
		return $this->defSubs;
	}

	/**
	 * @return Permission[]
	 */
	public function getPermissions() : array{
		return $this->permissions;
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return bool
	 */
	public function isPluginEnabled(Plugin $plugin) : bool{
		if($plugin instanceof Plugin and isset($this->plugins[$plugin->getDescription()->getName()])){
			return $plugin->isEnabled();
		}else{
			return false;
		}
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return PluginCommand[]
	 */
	protected function parseYamlCommands(Plugin $plugin) : array{
		$pluginCmds = [];

		foreach($plugin->getDescription()->getCommands() as $key => $data){
			if(strpos($key, ":") !== false){
				$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.commandError", [$key, $plugin->getDescription()->getFullName()]));
				continue;
			}
			if(is_array($data)){
				$newCmd = new PluginCommand($key, $plugin);
				if(isset($data["description"])){
					$newCmd->setDescription($data["description"]);
				}

				if(isset($data["usage"])){
					$newCmd->setUsage($data["usage"]);
				}

				if(isset($data["aliases"]) and is_array($data["aliases"])){
					$aliasList = [];
					foreach($data["aliases"] as $alias){
						if(strpos($alias, ":") !== false){
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.aliasError", [$alias, $plugin->getDescription()->getFullName()]));
							continue;
						}
						$aliasList[] = $alias;
					}

					$newCmd->setAliases($aliasList);
				}

				if(isset($data["permission"])){
					if(is_bool($data["permission"])){
						$newCmd->setPermission($data["permission"] ? "true" : "false");
					}elseif(is_string($data["permission"])){
						$newCmd->setPermission($data["permission"]);
					}else{
						throw new \InvalidArgumentException("Permission must be a string or boolean, " . gettype($data["permission"] . " given"));
					}
				}

				if(isset($data["permission-message"])){
					$newCmd->setPermissionMessage($data["permission-message"]);
				}

				$pluginCmds[] = $newCmd;
			}
		}

		return $pluginCmds;
	}

	public function disablePlugins(){
		foreach($this->getPlugins() as $plugin){
			$this->disablePlugin($plugin);
		}
	}

	public function clearPlugins(){
		$this->disablePlugins();
		$this->plugins = [];
		$this->fileAssociations = [];
		$this->permissions = [];
		$this->defaultPerms = [];
		$this->defaultPermsOp = [];
	}

	/**
	 * Calls an event
	 * Use Event::call instead
	 * 
	 * @deprecated
	 *
	 * @param Event $event
	 */
	public function callEvent(Event $event){
		$event->call();
	}

    /**
     * Extracts one-line tags from the doc-comment
     *
     * @param string $docComment
     * @return string[] an array of tagName => tag value. If the tag has no value, an empty string is used as the value.
     */
    public static function parseDocComment(string $docComment) : array{
        preg_match_all('/^[\t ]*\* @([a-zA-Z]+)(?:[\t ]+(.+))?[\t ]*$/m', $docComment, $matches);
        return array_combine($matches[1], array_map("trim", $matches[2]));
    }

    /**
     * @param Listener $listener
     * @param Plugin $plugin
     * @return void
     * @throws \ReflectionException
     */
    public function registerEvents(Listener $listener, Plugin $plugin){
        if(!$plugin->isEnabled()){
            throw new PluginException("Plugin attempted to register " . get_class($listener) . " while not enabled");
        }

        $reflection = new \ReflectionClass(get_class($listener));
        foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            if(!$method->isStatic() and $method->getDeclaringClass()->implementsInterface(Listener::class)){
                $tags = self::parseDocComment((string) $method->getDocComment());
                if(isset($tags["notHandler"])){
                    continue;
                }

                $parameters = $method->getParameters();
                if(count($parameters) !== 1){
                    continue;
                }
                try{
                    $paramType = $parameters[0]->getType();
                    //isBuiltin() returns false for builtin classes ..................
                    if($paramType instanceof \ReflectionNamedType && !$paramType->isBuiltin()){
                        /** @phpstan-var class-string $paramClass */
                        $paramClass = $paramType->getName();
                        $eventClass = new \ReflectionClass($paramClass);
                    }else{
                        $eventClass = null;
                    }
                }catch(\ReflectionException $e){ //class doesn't exist
                    if(isset($tags["softDepend"]) && !isset($this->plugins[$tags["softDepend"]])){
                        $this->server->getLogger()->debug("Not registering @softDepend listener " . get_class($listener) . "::" . $method->getName() . "(" . $parameters[0]->getType()->getName() . ") because plugin \"" . $tags["softDepend"] . "\" not found");
                        continue;
                    }

                    throw $e;
                }
                if($eventClass === null or !$eventClass->isSubclassOf(Event::class)){
                    continue;
                }

                try{
                    $priority = isset($tags["priority"]) ? EventPriority::fromString($tags["priority"]) : EventPriority::NORMAL;
                }catch(\InvalidArgumentException $e){
                    throw new PluginException("Event handler " . get_class($listener) . "->" . $method->getName() . "() declares invalid/unknown priority \"" . $tags["priority"] . "\"");
                }

                $ignoreCancelled = false;
                if(isset($tags["ignoreCancelled"])){
                    switch(strtolower($tags["ignoreCancelled"])){
                        case "true":
                        case "":
                            $ignoreCancelled = true;
                            break;
                        case "false":
                            $ignoreCancelled = false;
                            break;
                        default:
                            throw new PluginException("Event handler " . get_class($listener) . "->" . $method->getName() . "() declares invalid @ignoreCancelled value \"" . $tags["ignoreCancelled"] . "\"");
                    }
                }

                $this->registerEvent($eventClass->getName(), $listener, $priority, new MethodEventExecutor($method->getName()), $plugin, $ignoreCancelled);
            }
        }
    }

	/**
	 * @param string        $event Class name that extends Event
	 * @param Listener      $listener
	 * @param int           $priority
	 * @param EventExecutor $executor
	 * @param Plugin        $plugin
	 * @param bool          $ignoreCancelled
	 *
	 * @throws PluginException
	 */
	public function registerEvent(string $event, Listener $listener, int $priority, EventExecutor $executor, Plugin $plugin, bool $ignoreCancelled = false){
		if(!is_subclass_of($event, Event::class)){
			throw new PluginException($event . " is not an Event");
		}
		$class = new \ReflectionClass($event);
		if($class->isAbstract()){
			throw new PluginException($event . " is an abstract Event");
		}

		if(!$class->hasProperty("handlerList") or ($property = $class->getProperty("handlerList"))->getDeclaringClass()->getName() !== $event){
			throw new PluginException($event . " does not have a valid handler list");
		}
		if(!$property->isStatic()){
			throw new PluginException($event . " handlerList property is not static");
		}
		if(!$property->isPublic()){
			throw new PluginException($event . " handlerList property is not public");
		}

		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin attempted to register " . $event . " while not enabled");
		}

		$timings = new TimingsHandler("Plugin: " . $plugin->getDescription()->getFullName() . " Event: " . get_class($listener) . "::" . ($executor instanceof MethodEventExecutor ? $executor->getMethod() : "???") . "(" . (new \ReflectionClass($event))->getShortName() . ")", self::$pluginParentTimer);

		$this->getEventListeners($event)->register(new RegisteredListener($listener, $executor, $priority, $plugin, $ignoreCancelled, $timings));
	}

	/**
	 * @param string $event
	 *
	 * @return HandlerList
	 */
	private function getEventListeners(string $event) : HandlerList{
		if($event::$handlerList === null){
			$event::$handlerList = new HandlerList();
		}

		return $event::$handlerList;
	}

	/**
     * @deprecated
     *
	 * @return bool
	 */
	public function useTimings() : bool{
		return TimingsHandler::isEnabled();
	}

	/**
     * @deprecated
     *
     * @param bool $use
	 */
	public function setUseTimings(bool $use){
        TimingsHandler::setEnabled($use);
	}

}


