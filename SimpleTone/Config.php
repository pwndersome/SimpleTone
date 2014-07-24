<?php

namespace SimpleTone;

class Config
{
	protected static $config = array();


	# store all config
	public static function store($config)
	{
		self::$config	= $config;
	}


	# get a config key with dot notation ( about 12x times slower than normal referencing )
	public static function get($path = false)
	{
		# parse path
		if(!empty($path))
		{
			# get dot keys
			$keys	= explode('.', $path);
			$array	= self::$config;

			# iterate through keys
			foreach($keys as $k)
			{
                if(isset($array[$k]))
					$array =& $array[$k];
                else
					return false;
			}

			return $array;
        }

		# return all config
		return self::getAll();
	}


	protected static function getAll()
	{
		return self::$config;
	}
}
