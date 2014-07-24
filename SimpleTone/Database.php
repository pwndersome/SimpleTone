<?php

namespace SimpleTone;

class Database
{
	protected static $instance					= null;

	protected $requiredConnectionAttributes		= array('host', 'dbname', 'user', 'password', 'driver');

	protected $connections						= array();

	protected $defaultConnectionPort			= 3306;
	protected $defaultConnectionName			= null;
	protected $temporaryConnectionName			= null;
	protected $lastConnection					= array('name' => null, 'object' => null);
	protected $lastQuery						= null;

	protected $queryParams						= array('where' => array(), 'having' => array(), 'insert' => array(), 'update' => array());

	protected $table;
	protected $distinct;
	protected $select = '*';
	protected $join;
	protected $where;
	protected $groupBy;
	protected $orderBy;
	protected $limit;
	protected $insert;
	protected $update;
	protected $having;
	protected $result;


	/*
		Do not allow instantiation of this class

		@access		protected
	*/
	protected final function __construct()
	{

	}


	/*
		Create a single object regardless of number of calls

		@access		public
		@return		self
	*/
	public static function make()
	{
		if(empty(self::$instance))
			self::$instance = new self;

		return self::$instance;
	}


	/*
		Add a connection - connections details are added but the connections is not made until it's used

		@access		public
		@param		string	$connectionName
		@param		array	$connectionDetails
		@return		boolean
	*/
	public function addConnection($connectionName, $connectionDetails)
	{
		# check all attributes exist
		foreach($this->requiredConnectionAttributes as $value)
		{
			if(!isset($connectionDetails[$value]))
				throw new Exception("Missing attribute '".$value."' in addConnection('".$connectionName.").");
		}

		# do not add another connection with the same name
		if(empty($this->connections[$connectionName]))
		{
			# add new connection
			$this->connections[$connectionName]	= $connectionDetails;

			# always set last connection as default
			$this->setDefaultConnection($connectionName);

			return true;
		}
		else
			throw new Exception("Connection '".$connectionName."' already exists.");

		return false;
	}


	/*
		Set default connection

		@access		public
		@param		string	$connectionName
		@return		boolean
	*/
	public function setDefaultConnection($connectionName)
	{
		# connection exists
		if(!empty($this->connections[$connectionName]))
		{
			# set default connection
			$this->defaultConnectionName = $connectionName;

			return true;
		}
		else
			throw new Exception("Cannot set default connection. Connection '".$connectionName."' does not exist.");

		return false;
	}


	/*
		Use a connection for a query without changing default connection

		@access		public
		@param		string	$connectionName
		@return		$this
	*/
	public function onConnection($connectionName)
	{
		# connection exists
		if(!empty($this->connections[$connectionName]))
		{
			# set temporary connection
			$this->temporaryConnectionName = $connectionName;
		}
		else
			throw new Exception("Cannot set temporary connection. Connection '".$connectionName."' does not exist.");

		return $this;
	}


	/*
		Remove a connection by name

		@access		public
		@param		string	$connectionName
		@return		boolean
	*/
	public function removeConnection($connectionName)
	{
		# if connection exists, remove it
		if(!empty($this->connections[$connectionName]))
		{
			$this->connections[$connectionName] = null;

			unset($this->connections[$connectionName]);

			return true;
		}

		return false;
	}


	/*
		Build the query

		@access		protected
		@return		array
	*/
	protected function _buildQuery()
	{
		$params		= array();

		switch($this->action)
		{
			case 'SELECT':
									$query		= 'SELECT '.$this->distinct.' '.$this->select.' FROM '.$this->table.' '.$this->join.' '.$this->where.' '.$this->groupBy.' '.$this->orderBy.' '.$this->having.' '.$this->limit;
									$params		= array_merge($this->queryParams['where'], $this->queryParams['having']);
									break;

			case 'INSERT INTO':		$query		= 'INSERT INTO '.$this->table.' '.$this->insert;
									$params		= $this->queryParams['insert'];
									break;

			case 'UPDATE':			$query		= 'UPDATE '.$this->table.' '.$this->join.' '.$this->update.' '.$this->where.' '.$this->limit;
									$params		= array_merge($this->queryParams['update'], $this->queryParams['where']);
									break;

			case 'DELETE FROM':		$query		= 'DELETE FROM '.$this->table.' '.$this->where;
									$params		= $this->queryParams['where'];
									break;

			case 'TRUNCATE':		$query		= 'TRUNCATE '.$this->table.' '.$this->where;
									break;
		}

		# remove additional spaces
		while(stripos($query, '  ') !== false)
			$query = str_replace('  ', ' ', $query);

		return array('query' => $query, 'params' => $params);
	}


	/*
		Run the actual query automatic

		@access		protected
		@param		array	$query_info
		@return		PDOStatement Object | false
	*/
	protected function _runQuery($query_info = array())
	{
		# if ->query() was not called, build the query
		if(empty($query_info))
			$query_info		= $this->_buildQuery();

		# see if a temporary connection exists else run default connection
		if(!empty($this->temporaryConnectionName))
			$connectionName = $this->temporaryConnectionName;
		else
			$connectionName	= $this->defaultConnectionName;

		# make the connection
		$this->_connect($connectionName);


		# make query
		try
		{
			# prepare
			$result	= $this->connections[$connectionName]->prepare($query_info['query']);

			# execute with parameters
			$result->execute($query_info['params']);

			# set last connection
			$this->lastConnection['name']	= $connectionName;
			$this->lastConnection['object']	= $result;

			# set last query
			$this->lastQuery['string']		= $query_info['query'];
			$this->lastQuery['params']		= $query_info['params'];

			# reset object properties
			$this->flush_cache();

			# return result on success
			return $result;
		}
		catch(PDOException $e)
		{
			throw new Exception("Failed to query '".$connectionName."': ".$e->getMessage());
		}

		return false;
	}


	/*
		Make a connection

		@access		protected
		@param		string	$connectionName
	*/
	protected function _connect($connectionName)
	{
		# check if connection exists
		if(empty($this->connections[$connectionName]))
			throw new Exception("Could not connect. Connection '".$connectionName."' does not exist. Please add connections using addConnection() method.");

		# connection already made
		if(is_object($this->connections[$connectionName]))
			return true;

		$connectionDetails	= $this->connections[$connectionName];
		$port				= !empty($connectionDetails['port']) ? $connectionDetails['port'] : $this->defaultConnectionPort;

		# build dsn
		$dsn = $connectionDetails['driver'].':host='.$connectionDetails['host'].';port='.$port.';dbname='.$connectionDetails['dbname'].';';

		# try to connect
		try
		{
			$connection = new PDO($dsn, $connectionDetails['user'], $connectionDetails['password']);

			# default is throw exceptions
			$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if(!empty($connectionDetails['attributes']) && is_array($connectionDetails['attributes']))
			{
				foreach($connectionDetails['attributes'] as $key => $value)
					$connection->setAttribute($key,$value);
			}

			$this->connections[$connectionName] = $connection;
		}
		catch(PDOException $e)
		{
			throw new Exception("Could not connect to '".$connectionName."'. ".$e->getMessage());
		}
	}


	/*
		@access		public
		@param		string	$query
		@param		array	$params
		@param		string	$connectionName
		@return		PDOStatement Object | false
	*/
	public function query($query, $params = array(), $connectionName = '')
	{
		# set temporary connection if exists
		if(!empty($connectionName))
			$this->onConnection($connectionName);

		return $this->_runQuery(
								array(
									'query'				=> $query,
									'params'			=> $params
								)
		);
	}


	/*
		@access		public
		@return		integer
	*/
	public function rowCount()
	{
		if(!empty($this->lastConnection['object']))
			return $this->lastConnection['object']->rowCount();

		return 0;
	}


	/*
		@access		public
		@param		PDOStatement Object	$result
		@param		boolean	$return_only_value
		@return		array | string
	*/
	public function fetch($result, $return_only_value = false)
	{
		$row = $result->fetch(PDO::FETCH_ASSOC);

		if($return_only_value)
			return @array_shift($row);

		return $row;
	}


	/*
		@access		public
		@param		PDOStatement Object	$result
		@param		string	$value
		@return		array
	*/
	public function toNumArray($result, $value = '')
	{
		$data = array();

		if($value)
		{
			while($row = $result->fetch(PDO::FETCH_ASSOC))
				$data[] = $row[$value];
		}
		else
		{
			while($row = $result->fetch(PDO::FETCH_ASSOC))
				$data[] = $row;
		}

		return $data;
	}


	/*
		@access		public
		@param		PDOStatement Object	$result
		@param		string	$key
		@param		boolean	$overwrite_duplicate_keys
		@return		array
	*/
	public function toArray($result, $key = '', $overwrite_duplicate_keys = true)
	{
		if(!$key)
			return $this->toNumArray($result);

		$data = array();

		if($overwrite_duplicate_keys)
		{
			while($row = $result->fetch(PDO::FETCH_ASSOC))
				$data[$row[$key]] = $row;
		}
		else
		{
			while($row = $result->fetch(PDO::FETCH_ASSOC))
				if(!isset($data[$row[$key]]))
					$data[$row[$key]] = $row;
		}

		return $data;
	}


	/*
		@access		public
		@param		PDOStatement Object	$result
		@param		string	$key
		@param		boolean	$overwrite_duplicate_keys
		@return		array
	*/
	public function toArrayAppend($result, $key = '')
	{
		if(!$key)
			return $this->toNumArray($result);

		$data = array();

		while($row = $result->fetch(PDO::FETCH_ASSOC))
			$data[$row[$key]][] = $row;

		return $data;
	}


	/*
		@access		protected
	*/
	protected function flush_cache()
	{
		$this->queryParams		= array('where' => array(), 'having' => array(), 'insert' => array(), 'update' => array());
		$this->table			= null;
		$this->distinct			= null;
		$this->select			= '*';
		$this->join				= null;
		$this->where			= null;
		$this->groupBy			= null;
		$this->orderBy			= null;
		$this->limit			= null;
		$this->insert			= null;
		$this->update			= null;
		$this->having			= null;
		$this->result			= null;
	}
}
