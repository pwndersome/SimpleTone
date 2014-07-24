<?php

namespace SimpleTone;

use SimpleTone\Request;
use SimpleTone\Config;

/*
	- possible removal for kernel extension point

	+ set error handler
	+ register shutdown function

	HOW TO LOAD
	- log class
	+ request class
	- router class
	+ input class

	- make some config ?
	+ parse the request
	- check sessions ??
	- dispatch ?
	- make additional methods
*/


class Kernel
{
	protected $config;

	protected static $instance;


	# use ::make()
	protected function __construct($config)
	{
		$this->config	= $config;
	}


	public static function make($config)
	{
		# check if exists
		if(empty(static::$instance))
		{
			# make a copy
			static::$instance = new static($config);
		}

		return static::$instance;
	}


	# make necessary configuration before running
	public static function boot($config)
	{
		# create an instance of this class
		$instance	= self::make($config);

		# register autoloader first
		spl_autoload_register(array($instance, 'autoload'));

		# set error level
		$instance->setErrorLevel();

		# register error handler
		set_error_handler(array($instance, 'error'));

		# register shutdown
		register_shutdown_function(array($instance, 'shutdown'));

		# store config in config class
		Config::store($config);

		# register composer autoloader if exists
		if(file_exists(Config::get('vendor_path').'autoload.php'))
			require Config::get('vendor_path').'autoload.php';

		# run application
		$instance->run();
	}


	public function run()
	{
		# get request
		$request	= Request::make();

		# dispatch request
		$response	= Router::dispatch($request);

		# call output
		$this->output($response);
	}


	public function output($response)
	{
		# object
		if(is_object($response) && method_exists($response, '__toString'))
		{
			echo $response;
		}
		# array
		elseif(is_array($response))
		{
			echo print_r($response, 1);
		}
		# object + error / string
		else
		{
			# may be object here without __toString method, which will trigger an error
			echo $response;
		}
	}


	public function setErrorLevel()
	{
		# this shit is not working...
		error_reporting(Config::get('error_reporting'));
		ini_set('display_errors',	Config::get('display_errors'));
	}


	public static function error($error_number, $error_string, $error_file, $error_line, $error_context)
	{
		$error		= error_get_last();

		# Show last error
		if(!empty($error))
		{
			$template	= print_r($error, 1).PHP_EOL;
			$log_name	= Config::get('logs_path').'kernel_error.log';

			# output if not dev
			if(Config::get('is_development_mode'))
			{
				echo "<pre>";
				print_r($error);
				echo "</pre>";
			}
			else
			{
				# write to log if in production mode
				file_put_contents($log_name, $template, FILE_APPEND);
			}
		}

		# return false for default php error handler
		return false;
	}


	# in progress
	public static function shutdown()
	{
		# Getting last error
		$error	= error_get_last();

		# Show last error
		if(!empty($error))
		{
			$template	= print_r($error, 1).PHP_EOL;
			$log_name	= Config::get('logs_path').'kernel_shutdown.log';

			# output if not dev
			if(Config::get('is_development_mode'))
			{
				echo "<pre>";
				print_r($error);
				echo "</pre>";
			}
			else
			{
				# write to log if in production mode
				file_put_contents($log_name, $template, FILE_APPEND);
			}
		}
	}


	public static function autoload($namespace)
	{
		# make path
		$filepath	= ROOT_PATH.str_replace('\\', '/', $namespace).'.php';

		# require path if exists, else it may be autoloaded via another autoloader
		if(file_exists($filepath))
			require $filepath;
	}
}
