<?php

namespace application\modules\pages\home;

use SimpleTone\Module;
use SimpleTone\View;


class Home extends Module
{
	public function indexAction()
	{
		$view	= View::make($this->path(), 'home');

		$view->sayHello	= 'Hello world !';

		return $view;
	}
}
