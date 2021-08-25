<?php namespace spitfire\io\session;

use spitfire\App;
use spitfire\support\arrays\DotNotationAccessor;

/**
 * The Session class allows your application to write data to a persistent space
 * that automatically expires after a given time. This class allows you to quickly
 * and comfortably select the persistence mechanism you want and continue working.
 * 
 * This class is a <strong>singleton</strong>. I've been working on reducing the
 * amount of single instance objects inside of spitfire, but this class is somewhat
 * special. It represents a single and global resource inside of PHP and therefore
 * will only make the system unstable by allowing several instances.
 */
class Session
{
	
	/**
	 * All the sessions within Spitfire applications are implicitly namespaced, this
	 * allows several modules to share a single session safely without writing into
	 * each other's data.
	 * 
	 * You're recommended to instance a new namespaced session for your application
	 * if you're not using Spitfire's automated session scoping that will do it for you.
	 * 
	 * @var string
	 */
	private $namespace;
	
	/**
	 * The accessor allows applications to access their keys like "hello.world" instead
	 * of having to perform recursive or nested calls for accessing structured data 
	 * within the session.
	 * 
	 * @var DotNotationAccessor|null
	 */
	private $accessor;
	
	/**
	 * 
	 * @param string $namespace
	 */
	public function __construct(string $namespace = '_')
	{
		$this->namespace = $namespace;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set(string $key, $value) : void 
	{
		$this->start();
		$this->accessor->set($key, $value);
	}
	
	/**
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $key, $default = null) 
	{
		/**
		 * Check if the user is bringing a session along at all, if they're not, there's no point
		 * in trying to read it.
		 */
		if (!isset($_COOKIE[session_name()])) { 
			return $default; 
		}
		
		/**
		 * Otherwise, we consider the session started and go right into it
		 */
		$this->start();
		
		return $this->accessor->has($key)? 
			$this->accessor->get($key) : 
			$default;

	}
	
	/**
	 * Initialzes the session if it wasn't yet running.
	 */
	public function start() : void
	{
		if (session_status() === PHP_SESSION_ACTIVE) { return; }
		
		session_start();
		$this->accessor = new DotNotationAccessor($_SESSION[$this->namespace]);
	}
	
	/**
	 * Destroys the session. This code will automatically unset the session cookie,
	 * and delete the file (or whichever mechanism is used).
	 */
	public function destroy() : bool 
	{
		if (session_status() !== PHP_SESSION_ACTIVE) { return true; }
		
		/**
		 * Checks if the session is configured to be using cookies, technically the server
		 * could be using query parameters for handling sessions. That's not recommended
		 * though since it causses links to leak sessions.
		 */
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			
			/**
			 * Unset the lifetime, since this is not actually valid for the setcookie function.
			 * Instead, replace it with the expires key, which allows the application to 
			 * terminate the session by sending a cookie that expired in the past.
			 */
			unset($params['lifetime']);
			$params['expires'] = time() - 1;
			
			/**
			 * Send the cookie to the client so the session is properly terminated.
			 */
			setcookie(session_name(), '', $params);
		}
		
		return session_destroy();
	}
	
	/**
	 * Returns the session ID being used. 
	 * 
	 * Since March 2017 the Spitfire session will validate that the session 
	 * identifier returned is valid. A valid session ID is up to 128 characters
	 * long and contains only alphanumeric characters, dashes and commas.
	 * 
	 * @todo Move to instance
	 * 
	 * @param boolean $allowRegen Allows the function to provide a new SID in case
	 *                            of the session ID not being valid.
	 * 
	 * @return boolean
	 * @throws \Exception
	 */
	public static function sessionId($allowRegen = true){
		
		#Get the session_id the system is using.
		$sid = session_id();
		
		#If the session is valid, we return the ID and we're done.
		if (!$sid || preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $sid)) {
			return $sid;
		}
		
		#Otherwise we'll attempt to repair the broken 
		if (!$allowRegen || !session_regenerate_id()) {
			throw new \Exception('Session ID ' . ($allowRegen? 'generation' : 'validation') . ' failed');
		}
		
		return $sid;
	}
}
