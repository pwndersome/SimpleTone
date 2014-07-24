<?php

namespace SimpleTone;

use SimpleTone\Input;
use SimpleTone\Router;


/*
	- Request is a layer above of Input, but should Request extend Input ?
*/
class Request
{
	public $segments	= array();
	public $method		= 'get';

	protected function __construct()
	{

	}


	public static function make($segments = array(), $method = 'get')
	{
		if(empty($segments))
			$segments	= self::segments();

		$request	= new self;

		# set request info
		$request->segments	= $segments;
		$request->method	= strtolower($method);

		return $request;
	}


	public static function segments()
	{
		return Input::segments();
	}


	public static function segment($number)
	{
		$segments	= Input::segments();

		return empty($segments[$number-1]) ? false : $segments[$number-1];
	}


	public static function siteUrl()
	{
		return Input::siteUrl();
	}


	public static function isAjax()
	{
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
			return true;

		return false;
	}


	public static function method()
	{
		return empty($_SERVER['REQUEST_METHOD']) ? 'get' : strtolower($_SERVER['REQUEST_METHOD']);
	}


	public static function isMethod($name)
	{
		return self::method() == strtolower($name);
	}


	/*
		- maybe include some redirection headers ?
	*/
	public static function redirect($location = false)
	{
		if(empty($location))
			$location	= self::siteUrl();

		header('Location: '.$location);
		exit;
	}


	# go to internal link
	/*
		- internal links should be routed through Router class
		- modules should return the request back to Kernel::run(), is exit ok here ?
		- use as "return Request::action('/module/action/', $params);
	*/
	public static function action($path, $params = array(), $http_verb = 'GET')
	{
		die('in progress');
		$segments	= Input::pathToSegments($path);

		Router::dispatch($segments, $params, $http_verb);

		# stop any execution after this
		exit;
	}
}
