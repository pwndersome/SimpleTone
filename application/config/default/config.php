<?php

$config	= array(
					# application stuff
					'core_namespace'		=>	'SimpleTone',
					'core_path'				=>	ROOT_PATH.'SimpleTone/',

					'error_reporting'		=>	E_ALL | E_STRICT,		# not working
					'display_errors'		=>	true,					# not working

					'is_development_mode'	=>	true,

					# modules paths
					'cache_path'			=>	ROOT_PATH.'files/cache/',
					'modules_path'			=>	APPLICATION_PATH.'modules/',
					'assets_path'			=>	APPLICATION_PATH.'assets/',
					'js_path'				=>	APPLICATION_PATH.'assets/js/',
					'vendor_path'			=>	APPLICATION_PATH.'vendor/',
					'logs_path'				=>	ROOT_PATH.'files/logs/',
);

# module configuration
$config['modules']	=	array(
								'namespace'				=> 'application\modules',
								'default_module'		=> 'home',
								'default_action'		=> 'index',
								'default_action_suffix'	=> 'Action',

								# public module folders are accessed from url
								'public'				=> array(
																	$config['modules_path'].'pages/',

								),
);

$config['database']['default']	= array(
										'host'			=> 'localhost',
										'dbname'		=> 'database',
										'user'			=> 'root',
										'password'		=> 'password',
										'driver'		=> 'mysql',
										'attributes'	=> array(
																	PDO::MYSQL_ATTR_INIT_COMMAND =>	"SET NAMES 'utf8'"
										)
);

require 'custom_config.php';
