<?php namespace spitfire\core\kernel;

use spitfire\_init\LoadConfiguration;
use spitfire\core\http\request\handler\StaticResponseRequestHandler;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spitfire\mvc\RouterMiddleware;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\core\router\Router;
use spitfire\exceptions\ExceptionHandler;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

/**
 * The web kernel allows the application to interact with a web server and to 
 * select a controller that will provide an adequate response to the request.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class WebKernel implements KernelInterface
{
	
	private $router;
	
	public function __construct() 
	{
		$this->router = new Router();
	}
	
	public function boot()
	{
	}
	
	public function process(Request $request) : Response
	{
		
		try {
			$notfound = new StaticResponseRequestHandler(new Response('Not found', 404));
			$routed   = new DecoratingRequestHandler(new RouterMiddleware(), $notfound);
			
			return $routed->handle($request);
		}
		catch (\Exception $e) {
			$handler = new ExceptionHandler();
			return $handler->handle($e);
		}
		
		/**
		 * @todo Introduce a decorating request handler that wraps around the router's
		 * middleware and generates a response.
		 */
		$intent = $this->router->rewrite($request);
		
		/*
		 * Sometimes the router can provide a shortcut for really small and simple
		 * responses. It will return a response instead of a Intent, which will cause
		 * the application to just emit the response
		 */
		if ($intent instanceof Response) { return $intent; }
		
		# See PHPFIG PSR15
		# TODO: Router should return a middleware stack
		# TODO: The stack needs to be 'decorated' with requesthandlers
		# TODO: Run the stack
		
		#Start debugging output
		ob_start();

		#If the request has no defined controller, action and object it will define
		#those now.
		$path    = $request->getPath();
	}
	
	public function router() : Router
	{
		return $this->router;
	}

	public function initScripts(): array 
	{
		return [
			LoadConfiguration::class
		];
	}
	
}
