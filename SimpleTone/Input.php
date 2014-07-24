<?php

namespace SimpleTone;

	/*
		+ detect if cli or not
		+ get url segments
			+ exclude base directory out of segments
		+ get parameter segments
		- check to see if it works for nginx
		- ???
		- profit
	*/

class Input
{
	protected static $segments;
	protected static $get;
	protected static $parsedUrl = false;
	protected static $siteUrl;


	# check to see if input is from cli
	public static function isCli()
	{
		return defined('STDIN');
	}


	# return segments based on enviroment
	public static function segments()
	{
		if(self::isCli())
			return self::cliSegments();

		return self::webSegments();
	}


	# return values from $_POST array
	public static function post($key = '')
	{
		if(!empty($key))
			return empty($_POST[$key]) ? false : $_POST[$key];

		return $_POST;
	}


	# return values from $_GET array
	public static function get($key = '')
	{
		if(!empty($key))
			return empty($_GET[$key]) ? false : $_GET[$key];

		return $_GET;
	}


	protected static function parseUrl()
	{
		# skip this step if url was already parsed
		if(self::$parsedUrl)
			return;

		# check uri segments
		$uri			= $_SERVER['REQUEST_URI'];
		$script_name	= $_SERVER['SCRIPT_NAME'];

		# Check if the SCRIPT_NAME appears in the REQUEST_URI as a whole
		if(strpos($uri, $script_name) === 0)
		{
			# Take the part that appears after the script_name
			$uri = substr($uri, strlen($script_name));
		}
		elseif(strpos($uri, dirname($script_name)) === 0)
		{
			# Check if the directory name of SCRIPT_NAME appears in the REQUEST_URI
			# Take the part that appears after the script_name
			$uri = substr($uri, strlen(dirname($script_name)));
		}

		# Test for urls like index.php?/class/method/param?a=b&c=d
		# These kind of urls are used for fastcgi and godaddy servers!!
		# Also on nginx
		if (strncmp($uri, '?/', 2) === 0)
			$uri = substr($uri, 2);


		# Now split at the ?
		# The first part will be uri
		# The Second part will be actual query string
		$parts	= preg_split('#\?#i', $uri, 2);
		$uri	= $parts[0];

		# get $_GET
		if(isset($parts[1]))
		{
			$_SERVER['QUERY_STRING']	= $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}
		else
		{
			$_SERVER['QUERY_STRING']	= '';
			$_GET						= array();
		}


		if($uri == '/' || empty($uri))
			$segments	= array('/');
		else
		{
			$uri		= trim($uri, '/');
			$segments	= explode('/', $uri);
		}

		# store info
		self::$segments	= $segments;
		self::$get		= $_GET;
	}


	protected static function webSegments()
	{
		# parse web url
		self::parseUrl();

		return self::$segments;
	}


	protected static function cliSegments()
	{
		$segments	= array();

		# check arguments count
		if(!empty($_SERVER['argc']) && $_SERVER['argc'] > 1)
		{
			# unset script argument
			unset($_SERVER['argv'][0]);

			# get segments if passed with /
			if(!empty($_SERVER['argv']))
			{
				foreach($_SERVER['argv'] as $value)
				{
					# check to see if cli arguments contain slashes
					if(stripos($value, '/') !== false)
					{
						$value		= trim($value, '/');
						$parts		= explode('/', $value);
						$segments	= array_merge($segments, $parts);
					}
					else
					{
						# simply add this segment
						$segments[]	= $value;
					}
				}
			}
		}
		else
		{
			# default to / segment
			$segments	= array('/');
		}


		return $segments;
	}


	public static function siteUrl()
	{
		# return stored value
		if(!empty(self::$siteUrl))
			return self::$siteUrl;

		# cli
		if(empty($_SERVER['HTTP_HOST']))
			self::$siteUrl	= dirname($_SERVER['PHP_SELF']).'/';
		# web server
		else
		{
			$url			= $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
			self::$siteUrl	= '//'.str_replace('//', '/', $url);
		}

		return self::$siteUrl;
	}


	/*
		- this methods treats only paths like /a/b/c/
	*/
	public static function pathToSegments($path)
	{
		# trim stuff
		$path	= trim($path, '/');

		# return default if empty
		if(empty($path))
			return array('/');

		# get segments
		$segments	= explode('/', $path);

		return $segments;
	}
}
