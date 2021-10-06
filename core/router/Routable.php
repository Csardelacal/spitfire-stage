<?php namespace spitfire\core\router;

use Closure;
use spitfire\collection\Collection;
use spitfire\core\http\request\handler\RouterActionRequestHandler;
use spitfire\core\http\request\handler\RouterClosureRequestHandler;

/**
 * The routable class is the base class for both routers and router servers, 
 * which can both accept routes and store them. In order to do so, there is a 
 * predefined set of functions that are meant to be kept the way they are and 
 * a single abstract one that adds the route to the element the way this one 
 * decides.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 * @last-revision 2013-10-18
 */
abstract class Routable
{
	
	/**
	 * This is the url prefix the router is connected to. Every time the addRoute
	 * method is invoked, this router will prefix the route to scope it to the
	 * router.
	 * 
	 * Please note that forcing a router to accept a route that is not inside it's
	 * scope is likely to cause undefined behavior. Mostly because the router will
	 * reject every request that doesn't match it's namespace, but may generate
	 * urls that are outside it's scope.
	 * 
	 * @var string
	 */
	private $prefix;
	
	private $routes;
	
	public function __construct(string $prefix) 
	{
		$this->prefix = $prefix;
		$this->routes = new Collection();
	}
	
	/**
	 * This method adds a route for any request that is sent either via GET or POST
	 * this are the most standard and common behaviors and the ones recommended
	 * over PUT or DELETE due to it's behavior.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string|string[]|Closure $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function request($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_GET|Route::METHOD_POST|Route::METHOD_HEAD);
	}
	
	/**
	 * This method adds a route that will only rewrite the request if it's method 
	 * is GET. Otherwise the request will be ignored.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function get($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_GET);
	}
	
	/**
	 * Adds a route to reply to OPTIONS requests, these are most commonly used by 
	 * user-agents when they attempt to perform pre-flight CORS tests.
	 * 
	 * Options requests should therefore not be treated the same way as GET, POST 
	 * or HEAD requests, since they do not assume that the user agent is enforcing
	 * CORS policies when sending them.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function options($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_OPTIONS);
	}
	
	
	/**
	 * This method adds a route that will only rewrite the request if it's method 
	 * is PUT. Otherwise the request will be ignored.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function put($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_PUT);
	}
	
	
	/**
	 * This method adds a route that will only rewrite the request if it's method 
	 * is DELETE. Otherwise the request will be ignored.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function delete($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_DELETE);
	}
	
	
	/**
	 * This method adds a route that will only rewrite the request if it's method 
	 * is POST. Otherwise the request will be ignored.
	 * 
	 * @param string $pattern A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param string $target  Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @return Route The route that is generated by this
	 */
	public function post($pattern, $target) {
		return $this->addRoute($pattern, $target, Route::METHOD_POST);
	}
	
	public function getPrefix() 
	{
		return $this->prefix;
	}
	
	/**
	 * This method adds a route to the current object. You can use this to customize
	 * a certain set of methods for the route to rewrite.
	 * 
	 * @param string $pattern  A route valid pattern @link http://www.spitfirephp.com/wiki/index.php/Router/Patterns
	 * @param Closure|string[] $target   Where the content will be redirected to @link http://www.spitfirephp.com/wiki/index.php/Router/Target
	 * @param int    $method   The current method, default: Route::METHOD_GET | Route::METHOD_POST
	 * @param int    $protocol Whether the request s sent HTTP or HTTPS
	 * 
	 * @return Route The route that is generated by this
	 */
	public function addRoute($pattern, $target, $method = 0x03, $protocol = 0x03) {
		
		$match = URIPattern::make($pattern);
		
		/**
		 * We always wrap our target in a function to be invoked if the router
		 * matched the method. I am not a fan of this way of handling the code,
		 * since we've wrapped a closure in a closure so it doesn't get executed
		 * immediately.
		 * 
		 * @todo Replace with requesthandlerinterfaces
		 */
		if (is_array($target)) {
			assert(isset($target[0]) && isset($target[1]));
			$handler = new RouterActionRequestHandler($match, $target[0], $target[1]);
		}
		
		elseif ($target instanceof Closure) {
			$handler = new RouterClosureRequestHandler($match, $target);
		}
		
		return $this->routes->push(new Route($match, $handler, $method, $protocol)); 
	}
	
	public function getRoutes() {
		return $this->routes;
	}
}
