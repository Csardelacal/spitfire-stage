<?php namespace spitfire\core\router;

use Closure;
use spitfire\collection\Collection;
use spitfire\mvc\middleware\MiddlewareInterface;

/**
 * Routers are tools that allow your application to listen on alternative urls and
 * attach controllers to different URLs than they would normally do. Please note
 * that enabling a route to a certain controller does not disable it's canonical
 * URL.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Router extends Routable
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
	private $namespace;
	
	/**
	 * The middleware this router applies to it's routes and children. Middleware is 
	 * applied once the Intent object is created and being returned.
	 * 
	 * This means that more specific middleware is applied first when handling a request,
	 * and later when handling a response.
	 * 
	 * @var Collection<MiddlewareInterface>
	 */
	private $middleware;
	
	/**
	 * These routers inherit from this. Whenever this router is tasked with handling a 
	 * request that it cannot satisfy itself, the router will delegate this request to
	 * it's children.
	 * 
	 * This behavior implies that routes defined in the parent take precedence over the
	 * routes defined by it's children.
	 * 
	 * Also note: whenever you call the scope() method, the router generates a NEW Router
	 * for your scope. This means that you can have routers that manage routes within
	 * the same scope but have different middleware.
	 * 
	 * @var Collection<Router>
	 */
	private $children;
	
	public function __construct($namespace)
	{
		$this->namespace = $namespace;
		$this->middleware = new Collection();
		parent::__construct();
	}
	
	/**
	 * This rewrites a request into a Path (or in given cases, a Response). This 
	 * allows Spitfire to use the data from the Router to accordingly find a 
	 * controller to handle the request being thrown at it.
	 * 
	 * Please note that Spitfire is 'lazy' about it's routes. Once it found a valid
	 * one that can be used to respond to the request it will stop looking for
	 * another possible rewrite.
	 * 
	 * @todo The extension should be passed down to the servers (and therefore 
	 * the routes) to allow the routes to respond to different requests properly.
	 * 
	 * @param string $route
	 * @param string $method
	 * @param string $protocol
	 * @return Path|Response
	 */
	public function rewrite ($url, $method, $protocol) 
	{
		
		#Combine routes from the router and server
		$routes = $this->getRoutes()->toArray();
		
		if (\spitfire\utils\Strings::endsWith($url, '/')) {
			$url     = pathinfo($url, PATHINFO_DIRNAME) . '/' . pathinfo($url, PATHINFO_BASENAME);
			$ext     = 'php';
		} 
		else {
			$ext     = pathinfo($url, PATHINFO_EXTENSION);
			$url     = pathinfo($url, PATHINFO_DIRNAME) . '/' . pathinfo($url, PATHINFO_FILENAME);
		}
		
		#Test the routes
		foreach ($routes as $route) { /*@var $route Route*/
			
			#Verify whether the route is valid at all
			if (!$route->test($url, $method, $protocol)) { continue; }
			
			#Check whether the route can rewrite the request
			$rewrite = $route->rewrite($url, $method, $protocol, $ext);

			if ( $rewrite instanceof Path || $rewrite instanceof Response) { return $rewrite; }
			if ( $rewrite !== false)         { $url = $rewrite; }
		}
		
		#Implicit else.
		return false;
		throw new \spitfire\exceptions\PublicException('No such route', 404);
	}
	
	/**
	 * 
	 * 
	 * @var string $scope
	 * @var Closure $do
	 * @return Router
	 */
	public function scope(string $scope, Closure $do = null) : Router
	{
		$child = new Router(rtrim($this->namespace, '/') . ltrim($scope, '/'));
		$do($child);
		
		$this->children->push($child);
		
		return $child;
	}
	
	/**
	 * Allows the router to act with a singleton pattern. This allows your app to
	 * share routes across several points of it.
	 * 
	 * @deprecated since v0.2
	 * @staticvar Router $instance
	 * @return Router
	 */
	public static function getInstance() {
		static $instance = null;
		if ($instance) { return $instance; }
		else           { return $instance = new Router(); }
	}

}
