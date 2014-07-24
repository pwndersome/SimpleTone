<?php

/*
	M O R P H I N E / SimpleTone

	+ central kernel based
	+ hmvc
	+ modules everywhere
	+ public and protected modules
	+ configurable folder structure of assets and methods
	+ system overwrite
	+ modular scripts
	+ cli support for modules
	+ restful

		+ module components are loaded relative to module path
			ex:
				- views
				- models
				- how to load libraries / vendors ?

*/

# cli and web will have the same directory
chdir(dirname(__FILE__));

# define root path. ensure this is with realpath
$rootpath					= realpath(getcwd().'/../').'/';
define('ROOT_PATH',			str_replace('\\', '/', $rootpath));

# define application path
define('APPLICATION_PATH',	ROOT_PATH.'application/');

# load a configuration profile
require APPLICATION_PATH.'config/default/config.php';

# get the kernel class
require $config['core_path'].'Kernel.php';

# specify kernel namespace path
$kernel	= $config['core_namespace'].'\Kernel';

# boot
$kernel::boot($config);


