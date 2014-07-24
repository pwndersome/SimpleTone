<?php

namespace SimpleTone;


/*
	- assets are a general class, loading resources for modules
	- resources can be
		- views
		- models
		- libraries
		- whatever you want

*/
class Asset
{
	public static function load($module_path, $resource, $namespaced = false)
	{
		# asset path
		$asset_path		= dirname($module_path).'/'.$resource.'.php';

		# namespaced resource
		if($namespaced)
		{
			# get class namespace
			$class	= self::getNamespaceFromPath($asset_path);

			# let the autoloader handle stuff here
			return new $class;
		}
		else
		{
			$class	= basename($resource);

			# require the file
			require $asset_path;

			# return instance of class
			if(class_exists($class))
				return new $class;
		}
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
