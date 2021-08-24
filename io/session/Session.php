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
	private $namespace;
	
	/**
	 * 
	 * @var DotNotationAccessor
	 */
	private $accessor;
	
	public function __construct($namespace = '_')
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
		$this->accessor->set($this->namespace . '.' . $key, $value);
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
		
		return $this->accessor->has($this->namespace . '.' . $key)? 
			$this->accessor->get($this->namespace . '.' . $key) : 
			$default;

	}
	
	/**
	 * Initialzes the session if it wasn't yet running.
	 */
	public function start() : void
	{
		if (self::sessionId()) { return; }
		session_start();
		
		$this->accessor = new DotNotationAccessor($_SESSION);
	}
	
	/**
	 * Destroys the session. This code will automatically unset the session cookie,
	 * and delete the file (or whichever mechanism is used).
	 */
	public function destroy() : bool 
	{
		$this->start();
		
		setcookie(
			session_name(), 
			'', 
			['expires' => time() -1, 'path' => '/', 'samesite' => 'lax', 'secure' => true, 'httponly' => true]
		);
		
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
