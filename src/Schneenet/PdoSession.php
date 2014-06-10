<?php
namespace Schneenet;

use Slim\Middleware\SessionCookie;
use Slim\Exception;

class PdoSession extends SessionCookie
{

	/**
	 * Session ID
	 */
	protected $session_id;

	/**
	 * PDO
	 */
	protected $pdo;

	/**
	 * Constructor
	 *
	 * @param array $settings        	
	 */
	public function __construct($settings = array())
	{
		parent::__construct($settings);
		
		$defaults = array(
			'validate_ip' => false,
			'validate_user_agent' => false,
			'table' => 'sessions', // FIXME table: This is not used
			'save_function' => 'save_sessions' // FIXME save_function: This is not used
		);
		$this->settings = array_merge($defaults, $this->settings);
		
		if (is_array($this->settings['pdo']))
		{
			$this->settings['pdo'] = new \PDO($this->settings['pdo']['dsn'], $this->settings['pdo']['username'], $this->settings['pdo']['password']);
		}
		
		if (isset($this->settings['pdo']) && is_a($this->settings['pdo'], 'PDO'))
		{
			$this->pdo = $this->settings['pdo'];
		}
		else
		{
			throw new \Exception("Invalid PDO object in PdoSession");
		}
	}

	/**
	 * Load session
	 */
	protected function loadSession()
	{
		if (session_id() === '')
		{
			session_start();
		}
		
		// get session id from cookie
		$this->session_id = $this->app->getCookie($this->settings['name']);
		if (! isset($this->session_id))
		{
			$this->session_id = session_id();
		}
		
		// get request context
		$req = $this->app->request;
		
		// lookup session information
		$statement = $this->pdo->prepare('SELECT * FROM sessions WHERE session_id = ?');
		$statement->execute(array(
			$this->session_id
		));
		$row = $statement->fetch();
		
		// is the session valid?
		if ($row !== FALSE && (! $this->settings['validate_ip'] || $row['ip_address'] == $req->getIp()) && (! $this->settings['validate_user_agent'] || $row['user_agent'] == $req->getUserAgent()))
		{
			try
			{
				$_SESSION = json_decode($row['user_data'], true);
			}
			catch (\Exception $e)
			{
				$this->app->getLog()->error('Error unserializing session data: ' . $e->getMessage());
				throw $e;
			}
		}
		else
		{
			$_SESSION = array();
		}
		
		// cleanup
		$statement->closeCursor();
	}

	/**
	 * Save session
	 */
	protected function saveSession()
	{
		// encode session data
		$session_data = json_encode($_SESSION);
		
		// get request context
		$req = $this->app->request;
		
		// save the session
		$statement = $this->pdo->prepare('SELECT save_session(?, ?, ?, ?, ?)');
		$statement->execute(array($this->session_id, $req->getIp(), $req->getUserAgent(), time(), $session_data));
		$statement->closeCursor();
		
		// save the cookie
		$this->app->setCookie($this->settings['name'], $this->session_id, $this->settings['expires'], $this->settings['path'], $this->settings['domain'], $this->settings['secure'], $this->settings['httponly']);
		
		// session_destroy();
	}
}