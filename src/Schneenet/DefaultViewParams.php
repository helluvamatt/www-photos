<?php
namespace Schneenet;

class DefaultViewParams extends \Slim\Middleware
{
	public function call()
	{
		$this->app->hook('slim.before.dispatch', array($this, 'onBeforeDispatch'));
		$this->next->call();
	}
	
	public function onBeforeDispatch()
	{
		$route = $this->app->router()->getCurrentRoute();
		$current_route = $route->getName();
		$parms = $route->getParams();
		if (count($parms) > 0)
		{
			foreach ($parms as $name => $value)
			{
				$current_route .= "|" . $name . "=" . $value;
			}
		}
		
		$this->app->view->appendData(array(
			'current_route' => $current_route,
		));
	}
	
	public static function createUrlFor(\Slim\Slim $app, $redirect)
	{
		$parts = explode('|', $redirect);
		$params = array();
		$name = array_shift($parts);
		foreach ($parts as $part)
		{
			list($key, $value) = explode('=', $part, 2);
			$params[$key] = $value;
		}
		return $app->urlFor($name, $params);
	}
}
