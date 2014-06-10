<?php
/*
 * A project bootstrap would load configurations initialize the autoloader
 */

// project base directory
define('BASEDIR', dirname(__FILE__));

// app directory
define('APPDIR', BASEDIR . "/app");

// path to templates
define('TEMPLATEDIR', APPDIR . "/views");

// path to models
define('MODELSDIR', APPDIR . "/models");

// Load the composer autoloader
require BASEDIR . '/vendor/autoload.php';

// Build Slim app
$app = new \Slim\Slim([
	'templates.path' => TEMPLATEDIR,
	'cookies.encrypt ' => true,
	'log.writer' => new \Schneenet\FileLogger(APPDIR . "/logs/application.log"),
	'log.level' => \Slim\Log::DEBUG,
	'view' => new \Slim\Views\Twig(),
]);

$app->view->parserOptions = array(
	'debug' => true
);

$app->view->parserExtensions = array(
	new \Slim\Views\TwigExtension(),
	new Twig_Extension_Debug()
);

// Load configurations
$app->config = json_decode(file_get_contents(APPDIR . '/config.json'), true);

// build database connection
$app->pdo = new \PDO($app->config['database']['dsn'], $app->config['database']['username'], $app->config['database']['password']);
$app->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// build entity manager
$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
	'driver'    => $app->config['database']['driver'],
	'host'      => $app->config['database']['host'],
	'database'  => $app->config['database']['database'],
	'username'  => $app->config['database']['username'],
	'password'  => $app->config['database']['password'],
	'charset'   => 'utf8',
]);
$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container()));
$capsule->bootEloquent();

// Listen and log queries
$capsule->getEventDispatcher()->listen('illuminate.query', function($sql) use ($app) {
	
	//$bt = debug_backtrace();
	//$app->log->debug('========================================================================================');
	$app->log->info('Illuminate SQL: ' . $sql);
	/*
	$app->log->debug('----------------------------------------------------------------------------------------');
	foreach ($bt as $frame)
	{
		$cls = isset($frame['class']) ? sprintf("%s%s", $frame['class'], $frame['type']) : "";
		//$obj = isset($frame['object']) ? sprintf(" as '%s'", $frame['object']) : "";
		$obj = "";
		$file = isset($frame['file']) ? sprintf("%s:%s", $frame['file'], $frame['line']) : '__unknown__';
		//$args = implode(', ', $frame['args']);
		$args = "";
		$app->log->debug(sprintf("%s%s(%s)%s in %s", $cls, $frame['function'], $args, $obj, $file));
	}
	$app->log->debug('========================================================================================');
	*/
});

// Add custom authentication middleware
$app->add(new \Schneenet\SessionAuth());

// Add custom session middleware
$app->add(new \Schneenet\PdoSession(array('name' => 'sessionid', 'pdo' => $app->pdo)));

// Special middleware that sets some default view params
$app->add(new \Schneenet\DefaultViewParams());

// Not Found Handler
$app->notFound(function() use ($app)
{
	if (isset($app->resource))
	{
		$app->response->headers->set('Content-type', 'text/plain');
		$app->response->setBody('404 Not Found');
		$app->response->setStatus(404);
	}
	else 
	{
		$data = array(
			'error_title' => "Oops!",
			'error' => "I wasn't able to find that!",
			'error_image' => '');
		$app->render('error.twig', $data, 404);
	}
});

// Error Handler
$app->error(function(\Exception $e) use ($app) {
	$data = array(
		'error_title' => "Oops!",
		'error' => "Something went wrong!",
		'error_image' => '');
	$app->render('error.twig', $data, 500);
});

// Session helper
function session($key)
{
	return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
}

// Done!
return $app;
