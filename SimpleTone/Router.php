<?php

namespace SimpleTone;

use SimpleTone\Config;
use SimpleTone\Request;


class Router
{
	public static function dispatch(Request $request)
	{
		$folders	= Config::get('modules.public');
		$segments	= $request->segments;


		if(!empty($folders))
		{
			$segment	= array_shift($segments);

			# default routing
			if($segment == '/')
			{
				$module		= Config::get('modules.default_module');
				$action		= Config::get('modules.default_action');
				$params		= array();
			}
			else
			{
				$module		= $segment;
				$action		= array_shift($segments);
				$params		= $segments;

				# for links like /home/ add default action
				if(empty($action))
					$action	= Config::get('modules.default_action');
			}

			$module		= strtolower($module);
			$action		= strtolower($action);


			# find module
			foreach($folders as $path)
			{
				$filepath		= $path.$module.'/'.$module.'.php';
				$namespace		= self::getModuleNamespace($filepath);

				if(file_exists($filepath))
				{
					$module			= new $namespace($namespace, $filepath);
					$is_restful		= empty($module::$restful) ? false : true;

					# call __remap function for custom routing
					if(method_exists($module, '__remap'))
						$module->__remap();

					if(method_exists($module, 'init'))
						$module->init();

					# restful routing for actions
					if($is_restful)
					{
						$action		= strtolower($request->method).$action;
					}
					else
					{
						$action		= $action.Config::get('modules.default_action_suffix');
					}


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
						# no need for exit when returning
					}
				}
			}
		}

		# redirect to index if all failed
		$request::redirect();
	}


	protected static function getModuleNamespace($path)
	{
		$path		= str_replace('.php', '', $path);
		$namespace	= str_replace(Config::get('modules_path'), Config::get('modules.namespace').'\\', $path);
		$namespace	= str_replace('/', '\\', $namespace);

		return $namespace;
	}
}
