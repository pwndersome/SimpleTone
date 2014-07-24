<?php

namespace SimpleTone;

/*
	- where to extend the view ? bootstrap file ? / probably extend the class and make rewrites
	+ how to load common header and footer ( make a new module, and call that where needed )
	- how to load scripts

*/
class View extends \ArrayObject
{
	protected $_path;
	protected $_folder	= 'views';


	public function __construct($module_path, $view_name, $params = array())
	{
		# set different flags for ArrayObject
		parent::__construct();	# is this ok here ?
		parent::setFlags(parent::ARRAY_AS_PROPS);

		$this->_path	= $this->path($module_path, $view_name);
	}


	protected function path($module_path, $view_name, $extension = 'php')
	{
		$module_path	= dirname($module_path).'/';

		return $module_path.$this->_folder.'/'.$view_name.'.'.$extension;
	}


	public static function make($module_path, $view_name, $params = array())
	{
		return new static($module_path, $view_name, $params);
	}


	# should this be implemented when returning a view ???
	public function __toString()
	{
		# start output buffering
		ob_start();

		# extract parameters
		extract($this->params());

		# require view
		require $this->_path;

		# get the view content
		$contents	= ob_get_contents();

		# stop buffering
		ob_end_clean();

		# return contents
		return $contents;
	}


	public function params()
	{
		$params		= array();

		foreach($this as $key => $value)
			$params[$key]	= $value;

		return $params;
	}
}
