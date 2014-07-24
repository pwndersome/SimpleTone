<?php

namespace SimpleTone;

use SimpleTone\Config;

/*
	- how to load assets
	- load scripts ??
	+ implement modules
*/
class Module
{
	protected $classNamespace;
	protected $filepath;
	protected $realpath;
	protected static $runningModules	= array();
	private $path;


	public function __construct($classNamespace, $filepath)
	{
		# set namespace
		$this->classNamespace						= $classNamespace;

		# set filepath
		$this->filepath								= $filepath;

		# get realpath for filepath
		$this->realpath								= str_replace('\\', '/', realpath($this->filepath));

		# add info to loaded modules
		self::$runningModules[$classNamespace][]	= $this->realpath;
	}


	# return module path
	public function getPath()
	{
		return $this->realpath;
	}


	public function path()
	{
		return $this->realpath;
	}


	public function folderPath()
	{
		return str_replace('\\', '/', dirname($this->realpath)).'/';
	}


	public function getNamespace()
	{
		return $this->classNamespace;
	}


	# check if a module is a public or not
	public function isPublic()
	{

	}


	/*
		- load module without calling a method
	*/
	public static function load($segments)
	{
		$filepath	= self::getFilepath($segments);
		$namespace	= self::getNamespaceFromPath($filepath);

		return new $namespace($namespace, $filepath);
	}


	public static function running()
	{
		return self::$runningModules;
	}


	# same as run but without default method suffix
	public static function call($segments, $params = array())
	{

	}


	/*
		- protected modules do not have suffix for action
		- call module method with params

		Module::run('database/io/', 'index', $params);
		Module::run('database/io/index', $params);
		Module::run('io/index', $params);
	*/
	public static function run($segments, $params = array())
	{
		$filepath	= self::getFilepath($segments, $has_method = true);
		$namespace	= self::getNamespaceFromPath($filepath);
		$action		= basename($segments).Config::get('modules.default_action_suffix');

		$module		= new $namespace($namespace, $filepath);

		# call __remap function for custom routing
		if(method_exists($module, '__remap'))
			$module->__remap();

		if(method_exists($module, $action))
		{
			switch(count($params))
			{
				case 0: return $module->{$action}(); break;
				case 1: return $module->{$action}($params[0]); break;
				case 2: return $module->{$action}($params[0], $params[1]); break;
				case 3: return $module->{$action}($params[0], $params[1], $params[2]); break;
				case 4: return $module->{$action}($params[0], $params[1], $params[2], $params[3]); break;
				default: return call_user_func_array(array($module, $action), $params);  break;
			}
		}

		return false;
	}


	protected static function getFilepath($segments, $has_method = false)
	{
		# get segments array
		$segments		= explode('/', $segments);

		# remove method if specified in segments
		if($has_method)
			$method			= array_pop($segments);

		# get module name
		$module			= array_pop($segments);

		# get module path
		$module_path	= Config::get('modules_path').implode('/', $segments).'/'.$module.'/'.$module.'.php';


		return $module_path;
	}


	protected static function getNamespaceFromPath($filepath)
	{
		# trim extension
		$namespace				= str_replace('.php', '', $filepath);
		
		# ensure all paths are with forward slash
		$namespace				= str_replace('\\', '/', $namespace);

		$modules_path			= Config::get('modules_path');
		$modules_namespace		= Config::get('modules.namespace');

		# get modules namespace
		$namespace				= str_replace($modules_path, $modules_namespace.'\\', $namespace);

		# replace slashes
		$namespace				= str_replace('/', '\\', $namespace);


		return $namespace;
	}
}
