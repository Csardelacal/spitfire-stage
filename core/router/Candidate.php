<?php namespace spitfire\core\router;

use spitfire\collection\Collection;
use spitfire\core\Path;
use spitfire\mvc\middleware\MiddlewareInterface;

/**
 * The candidate route will be the defacto return from the Router
 * whenever it evaluates a route that does not immediately resolve
 * in a response.
 * 
 * Instead the route will return a candidate, which the router can 
 * then enrich by adding middleware it requires.
 */
class Candidate
{
	
	/**
	 * @var Collection<MiddlewareInterface>
	 */
	private $middleware;
	
	/**
	 * @var Path
	 */
	private $path;
	
	/**
	 * 
	 * @param Path $path
	 */
	public function __construct(Path $path)
	{
		$this->path = $path;
		$this->middleware = new Collection();
	}
	
	/**
	 * 
	 * @return Path
	 */
	public function getPath() : Path
	{
		return $this->path;
	}
	
	/**
	 * 
	 * @return Collection<MiddlewareInterface>
	 */
	public function getMiddleware() : Collection
	{
		return $this->middleware;
	}
	
	/**
	 * 
	 * @param Collection<MiddlewareInterface> $middleware
	 * @return Candidate
	 */
	public function putMiddleware(Collection $middleware) : Candidate
	{
		$this->middleware->add($middleware);
		return $this;
	}
	
}