<?php namespace spitfire\io\session;

use spitfire\core\config\Configuration;
use spitfire\core\service\Provider;
use spitfire\exceptions\ApplicationException;

class SessionProvider extends Provider
{
	
	public function init() 
	{
		
	}
	
	public function register()
	{
		/**
		 * For the time being, a dead simple, but effective approach to session management
		 * is to just configure the cookie the way we want and then start the session accordingly
		 * whenever the session is requested for a route using the middleware
		 */
		$lifetime = config('session.lifetime', 86300);
		session_set_cookie_params(['expires' => time() + $lifetime, 'path' => '/', 'samesite' => 'lax', 'secure' => true, 'httponly' => true]);
		
		/**
		 * Register a session that is ready to initialize the user's session whenever the
		 * user is ready to do so. PLease note that spitfire is lazy with handling sessions,
		 * this means that if you don't use it, the system will not lock the filesystem.
		 */
		$this->container->set(Session::class, new Session());
	}
	
}
