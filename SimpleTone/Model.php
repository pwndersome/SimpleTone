<?php

namespace SimpleTone;

use SimpleTone\Config;
use SimpleTone\Database;


class Model
{
	protected static $initConnections = false;
	protected $db = false;

	public function __construct()
	{
		# add database connections
		if(empty(self::$initConnections))
		{
			$config		= Config::get('database');
			$db			= Database::make();

			# add connections
			foreach($config as $key => $value)
				$db->addConnection($key, $value);

			self::$initConnections	= $db;
			$this->db				= $db;
		}
		else
		{
			$this->db	= self::$initConnections;
		}
	}
}
