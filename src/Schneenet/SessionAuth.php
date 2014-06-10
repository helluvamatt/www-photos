<?php
namespace Schneenet;

use Schneenet\Photos\Models\User;

class SessionAuth extends \Slim\Middleware
{
	public function call()
	{
		// append user data object if available
		if (isset($_SESSION['user']))
		{
			$this->app->user = User::find($_SESSION['user']);
			$this->app->view->appendData(array('user' => $this->app->user));
		}
		
		// call down to next middleware
		$this->next->call();
	}
}